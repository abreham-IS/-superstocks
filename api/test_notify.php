<?php
// ============================================================
//  test_notify.php — Test Telegram Notification
//  Open in browser to verify Telegram is configured correctly.
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth();

/** @var mysqli $conn */
$conn     = get_conn();
$result   = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
$settings = mysqli_fetch_assoc($result);
mysqli_free_result($result);

$token   = $settings['notification_telegram_token']   ?? '';
$chat_id = $settings['notification_telegram_chat_id'] ?? '';

if (empty($token) || empty($chat_id)) {
    api_respond([
        'ok'          => false,
        'step'        => 'config',
        'message'     => 'Telegram token or chat ID is not set in Settings.',
        'token_set'   => !empty($token),
        'chat_id_set' => !empty($chat_id),
    ]);
}

// Step 1: Verify token with getMe
$get_me_url      = "https://api.telegram.org/bot{$token}/getMe";
$get_me_response = @file_get_contents($get_me_url);

if ($get_me_response === false) {
    api_respond([
        'ok'      => false,
        'step'    => 'getMe',
        'message' => 'Could not reach Telegram API. Check your internet connection.',
    ]);
}

$get_me_data = json_decode($get_me_response, true);

if (!$get_me_data['ok']) {
    api_respond([
        'ok'                 => false,
        'step'               => 'getMe',
        'message'            => 'Invalid bot token. Get a new one from @BotFather on Telegram.',
        'telegram_response'  => $get_me_data,
    ]);
}

// Step 2: Send test message
$api_url   = "https://api.telegram.org/bot{$token}/sendMessage";
$message   = "✅ SuperStock Test Notification\n\nYour Telegram notifications are working!\n\nGenerated: " . date('Y-m-d H:i:s');
$post_data = http_build_query(['chat_id' => $chat_id, 'text' => $message]);

$context  = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $post_data,
        'timeout' => 10,
    ],
]);

$response = @file_get_contents($api_url, false, $context);

if ($response === false) {
    api_respond([
        'ok'       => false,
        'step'     => 'sendMessage',
        'message'  => 'Token is valid but could not send message. Check your Chat ID.',
        'bot_name' => $get_me_data['result']['username'] ?? 'unknown',
    ]);
}

$send_data = json_decode($response, true);

if (!$send_data['ok']) {
    api_respond([
        'ok'                => false,
        'step'              => 'sendMessage',
        'message'           => 'Token valid but message failed. Press START on your bot in Telegram.',
        'bot_name'          => $get_me_data['result']['username'] ?? 'unknown',
        'telegram_response' => $send_data,
    ]);
}

api_respond([
    'ok'       => true,
    'message'  => 'Test message sent successfully! Check your Telegram.',
    'bot_name' => $get_me_data['result']['username'] ?? 'unknown',
    'chat_id'  => $chat_id,
]);
