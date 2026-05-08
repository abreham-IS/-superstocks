<?php
// ============================================================
//  update_settings.php — Update Application Settings
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_auth();

/** @var mysqli $conn */
$conn = get_conn();

$allowed = [
    'store_name'                    => 'string',
    'manager_name'                  => 'string',
    'expiry_threshold_days'         => 'int',
    'low_stock_threshold'           => 'int',
    'notification_email'            => 'string',
    'notification_telegram_token'   => 'string',
    'notification_telegram_chat_id' => 'string',
];

$set_parts = [];
$types     = '';
$values    = [];

foreach ($allowed as $field => $type) {
    if (isset($_POST[$field])) {
        $value = trim($_POST[$field]);

        if ($field === 'expiry_threshold_days') {
            if (!is_numeric($value) || (int)$value < 1 || (int)$value > 365) {
                api_error(422, 'expiry_threshold_days must be between 1 and 365.');
            }
            $value  = (int)$value;
            $types .= 'i';
        } elseif ($field === 'low_stock_threshold') {
            if (!is_numeric($value) || (int)$value < 0) {
                api_error(422, 'low_stock_threshold must be 0 or greater.');
            }
            $value  = (int)$value;
            $types .= 'i';
        } else {
            $value  = ($value === '') ? null : $value;
            $types .= 's';
        }

        $set_parts[] = "$field = ?";
        $values[]    = $value;
    }
}

if (empty($set_parts)) {
    $result   = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
    $settings = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    api_respond($settings);
}

$sql  = "UPDATE settings SET " . implode(', ', $set_parts) . " WHERE id = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$values);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$result   = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
$settings = mysqli_fetch_assoc($result);
mysqli_free_result($result);

api_respond($settings);
