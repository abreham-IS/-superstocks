<?php
// ============================================================
//  update_password.php — Change the Logged-In User's Password
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

$session_user     = require_auth();
$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password']     ?? '';

if ($current_password === '') {
    api_error(400, 'current_password is required.');
}
if ($new_password === '') {
    api_error(400, 'new_password is required.');
}

/** @var mysqli $conn */
$conn = get_conn();

$stmt = mysqli_prepare($conn, "SELECT password_hash FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $session_user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    api_error(401, 'User account not found.');
}

if (!password_verify($current_password, $row['password_hash'])) {
    api_error(401, 'Current password is incorrect.');
}

$new_hash = password_hash($new_password, PASSWORD_BCRYPT);

$stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'si', $new_hash, $session_user['id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

api_respond(['ok' => true]);
