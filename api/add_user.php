<?php
// ============================================================
//  add_user.php — Create a New User Account (Admin only)
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_auth('Admin');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';
$role     = trim($_POST['role']     ?? '');

$missing = [];
if ($username === '') $missing[] = 'username';
if ($password === '') $missing[] = 'password';
if ($role === '')     $missing[] = 'role';

if (!empty($missing)) {
    api_error(400, 'Missing required fields: ' . implode(', ', $missing));
}

if (!in_array($role, ['Admin', 'Manager'], true)) {
    api_error(422, 'role must be either "Admin" or "Manager".');
}

$password_hash = password_hash($password, PASSWORD_BCRYPT);

/** @var mysqli $conn */
$conn = get_conn();

$stmt = mysqli_prepare($conn, "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $username, $password_hash, $role);

if (!mysqli_stmt_execute($stmt)) {
    if (mysqli_errno($conn) === 1062) {
        mysqli_stmt_close($stmt);
        api_error(409, 'A user with this username already exists.');
    }
    mysqli_stmt_close($stmt);
    api_error(500, 'Database error. Could not create user.');
}

$new_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT id, username, role, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $new_id);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$new_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

api_respond($new_user, 201);
