<?php
// ============================================================
//  bulk_delete_products.php — Delete Multiple Products
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_auth();

$ids = json_decode($_POST['ids'] ?? '[]', true);
if (!is_array($ids) || empty($ids)) {
    api_error(400, 'ids must be a non-empty JSON array of integers.');
}

$ids = array_values(array_filter(array_map('intval', $ids), function($id) { return $id > 0; }));
if (empty($ids)) {
    api_error(400, 'ids must contain valid positive integers.');
}

/** @var mysqli $conn */
$conn         = get_conn();
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types        = str_repeat('i', count($ids));

$stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id IN ($placeholders)");
mysqli_stmt_bind_param($stmt, $types, ...$ids);
mysqli_stmt_execute($stmt);
$deleted = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

api_respond(['deleted' => $deleted]);
