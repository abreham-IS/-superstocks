<?php
require_once 'api/config.php';
require_once 'api/db.php';
require_once 'api/response.php';

try {
    $conn = get_conn();
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    $row = mysqli_fetch_assoc($result);
    api_respond(['status' => 'Database connected', 'users_count' => $row['count']]);
} catch (Exception $e) {
    api_error(500, 'Database error: ' . $e->getMessage());
}
?>