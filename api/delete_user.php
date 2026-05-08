<?php
// ============================================================
//  delete_user.php — Delete a User Account (Admin only)
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

$session_user = require_auth('Admin');

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    api_error(400, 'A valid user id is required.');
}

if ((int)$id === (int)$session_user['id']) {
    api_error(403, 'You cannot delete your own account.');
}

/** @var mysqli $conn */
$conn = get_conn();

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected === 0) {
    api_error(404, 'User not found.');
}

api_respond(['ok' => true]);
