<?php
// ============================================================
//  login.php — User Login Endpoint (Procedural MySQLi)
// ============================================================

require_once __DIR__ . '/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_once __DIR__ . '/db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';

if ($username === '' || $password === '') {
    api_error(400, 'Username and password are required.');
}

/** @var mysqli $conn */
$conn = get_conn();

$stmt = mysqli_prepare($conn, "SELECT id, username, password_hash, role FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || !password_verify($password, $user['password_hash'])) {
    api_error(401, 'Invalid username or password.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

api_respond([
    'username' => $user['username'],
    'role'     => $user['role'],
]);
