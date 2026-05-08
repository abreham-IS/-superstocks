<?php
// ============================================================
//  auth_check.php — Session Status Check
// ============================================================

require_once __DIR__ . '/response.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';

$user = require_auth();

api_respond([
    'id'       => $user['id'],
    'username' => $user['username'],
    'role'     => $user['role'],
]);
