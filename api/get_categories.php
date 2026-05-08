<?php
// ============================================================
//  get_categories.php — List All Categories
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth();

/** @var mysqli $conn */
$conn = get_conn();

$result     = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}
mysqli_free_result($result);

api_respond($categories);
