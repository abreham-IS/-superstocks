<?php
// ============================================================
//  notify.php — Daily Expiry & Low-Stock Notifier
//              (Procedural MySQLi)
//
//  HOW TO RUN:
//    From the command line:  php notify.php
//    As a daily cron job:    0 7 * * * php /path/to/notify.php
//
//  This script:
//    1. Reads settings from the database (thresholds, notification config)
//    2. Finds expired and expiring-soon products
//    3. Finds low-stock products
//    4. Sends a notification via email and/or Telegram
//    5. Falls back to writing a log file if no channels are configured
//    6. Always appends a summary to logs/notify.log
// ============================================================

// Block browser access — CLI only
$is_cli = (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server' || !isset($_SERVER['HTTP_HOST']));

if (!$is_cli) {
    http_response_code(403);
    echo json_encode(['error' => 'This script can only be run from the command line.']);
    exit(1);
}

require_once __DIR__ . '/api/db.php';

$conn = get_conn();

// ── Step 1: Load settings ────────────────────────────────────
$result   = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
$settings = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if (!$settings) {
    echo "ERROR: Settings row not found. Please import schema.sql first.\n";
    exit(1);
}

$expiry_threshold   = (int)$settings['expiry_threshold_days'];
$low_stock_threshold = (int)$settings['low_stock_threshold'];
$today              = date('Y-m-d');

// ── Check if notification already sent today ─────────────────
$sent_flag_file = __DIR__ . '/logs/last_sent.txt';
if (file_exists($sent_flag_file)) {
    $last_sent = trim(file_get_contents($sent_flag_file));
    if ($last_sent === $today) {
        echo "Notification already sent today ({$today}). Skipping.\n";
        exit(0);
    }
}

// ── Step 2: Find expired and expiring-soon products ──────────
$stmt = mysqli_prepare($conn, "
    SELECT p.name, p.quantity, p.expiry_date, c.name AS category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    WHERE p.expiry_date <= DATE_ADD(?, INTERVAL ? DAY)
    ORDER BY p.expiry_date ASC
");
mysqli_stmt_bind_param($stmt, 'si', $today, $expiry_threshold);
mysqli_stmt_execute($stmt);

$result          = mysqli_stmt_get_result($stmt);
$expiry_products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $expiry_products[] = $row;
}
mysqli_stmt_close($stmt);

// ── Step 3: Find low-stock products ─────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT p.name, p.quantity, c.name AS category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    WHERE p.quantity < ?
    ORDER BY p.quantity ASC
");
mysqli_stmt_bind_param($stmt, 'i', $low_stock_threshold);
mysqli_stmt_execute($stmt);

$result            = mysqli_stmt_get_result($stmt);
$low_stock_products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $low_stock_products[] = $row;
}
mysqli_stmt_close($stmt);

// ── Step 4: Build the plain-text report ─────────────────────
$report  = "==============================================\n";
$report .= "  SuperStock Daily Notification Report\n";
$report .= "  Generated: " . date('Y-m-d H:i:s') . "\n";
$report .= "==============================================\n\n";

$report .= "--- EXPIRY ALERTS (" . count($expiry_products) . " products) ---\n";
if (empty($expiry_products)) {
    $report .= "  No expiry issues found.\n";
} else {
    foreach ($expiry_products as $p) {
        $days_left = (int)((strtotime($p['expiry_date']) - strtotime($today)) / 86400);
        $status    = $days_left < 0
            ? "EXPIRED " . abs($days_left) . " day(s) ago"
            : "Expires in {$days_left} day(s)";
        $report .= "  - {$p['name']} [{$p['category_name']}] | Qty: {$p['quantity']} | {$status} ({$p['expiry_date']})\n";
    }
}

$report .= "\n--- LOW STOCK ALERTS (" . count($low_stock_products) . " products) ---\n";
if (empty($low_stock_products)) {
    $report .= "  No low-stock issues found.\n";
} else {
    foreach ($low_stock_products as $p) {
        $report .= "  - {$p['name']} [{$p['category_name']}] | Qty: {$p['quantity']} (threshold: {$low_stock_threshold})\n";
    }
}

$report .= "\n==============================================\n";

// ── Tracking variables ───────────────────────────────────────
$notifications_sent = 0;
$errors             = [];

// ── Step 5: Send email notification (if configured) ──────────
$notification_email = $settings['notification_email'] ?? '';

if (!empty($notification_email)) {
    $subject = "[SuperStock] Daily Inventory Alert — " . date('Y-m-d');
    $headers = "From: noreply@superstock.com\r\nContent-Type: text/plain; charset=UTF-8";

    $sent = mail($notification_email, $subject, $report, $headers);
    if ($sent) {
        echo "Email sent to: {$notification_email}\n";
        $notifications_sent++;
    } else {
        $errors[] = "mail() returned false for {$notification_email}";
    }
}

// ── Step 6: Send Telegram notification (if configured) ───────
$telegram_token   = $settings['notification_telegram_token']   ?? '';
$telegram_chat_id = $settings['notification_telegram_chat_id'] ?? '';

if (!empty($telegram_token) && !empty($telegram_chat_id)) {
    $api_url  = "https://api.telegram.org/bot{$telegram_token}/sendMessage";
    $message  = mb_substr($report, 0, 4000);

    $post_data = http_build_query([
        'chat_id' => $telegram_chat_id,
        'text'    => $message,
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $post_data,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents($api_url, false, $context);
    if ($response !== false) {
        echo "Telegram message sent to chat ID: {$telegram_chat_id}\n";
        $notifications_sent++;
        file_put_contents($sent_flag_file, $today);
    } else {
        $errors[] = "Telegram: request failed";
    }
}

// ── Step 7: Log-only fallback ────────────────────────────────
if (empty($notification_email) && (empty($telegram_token) || empty($telegram_chat_id))) {
    $log_path = __DIR__ . '/logs/notify.log';
    file_put_contents($log_path, $report . "\n", FILE_APPEND | LOCK_EX);
    echo "No notification channels configured. Report written to logs/notify.log\n";
}

// ── Step 8: Always append a summary line to the log ─────────
$summary  = "[" . date('Y-m-d H:i:s') . "] Run complete. ";
$summary .= "Expiry issues: " . count($expiry_products) . ". ";
$summary .= "Low-stock issues: " . count($low_stock_products) . ". ";
$summary .= "Notifications sent: {$notifications_sent}. ";
$summary .= empty($errors) ? "Errors: none." : "Errors: " . implode('; ', $errors);
$summary .= "\n";

$log_path = __DIR__ . '/logs/notify.log';
file_put_contents($log_path, $summary, FILE_APPEND | LOCK_EX);

echo $summary;
