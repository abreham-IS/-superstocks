<?php
// ============================================================
//  add_category.php — Create a New Category
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_auth();

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    api_error(400, 'Category name is required.');
}

/** @var mysqli $conn */
$conn = get_conn();

$stmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
mysqli_stmt_bind_param($stmt, 's', $name);

if (!mysqli_stmt_execute($stmt)) {
    if (mysqli_errno($conn) === 1062) {
        mysqli_stmt_close($stmt);
        api_error(409, 'A category with this name already exists.');
    }
    mysqli_stmt_close($stmt);
    api_error(500, 'Could not create category.');
}

$new_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

api_respond(['id' => $new_id, 'name' => $name], 201);
