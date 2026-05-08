<?php
// ============================================================
//  logout.php — User Logout Endpoint
// ============================================================

require_once __DIR__ . '/response.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

// Write the empty session to disk immediately to prevent any zombie sessions on Windows
session_write_close();

// Restart session to destroy it completely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 86400, '/');
}

session_destroy();

api_respond(['ok' => true]);
