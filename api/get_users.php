<?php
// ============================================================
//  get_users.php — List All Users (Admin only)
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth('Admin');

/** @var mysqli $conn */
$conn   = get_conn();
$result = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY id ASC");

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
mysqli_free_result($result);

api_respond($users);
