<?php
// ============================================================
//  delete_product.php — Delete a Single Product
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_auth();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    api_error(400, 'A valid product id is required.');
}

/** @var mysqli $conn */
$conn = get_conn();

$stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected === 0) {
    api_error(404, 'Product not found.');
}

api_respond(['ok' => true]);
