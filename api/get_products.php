<?php
// ============================================================
//  get_products.php — List All Products
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth();

/** @var mysqli $conn */
$conn = get_conn();

$result = mysqli_query($conn, "
    SELECT p.id, p.name, p.category_id,
           c.name AS category_name,
           p.quantity, p.price,
           p.production_date, p.expiry_date,
           p.supplier, p.notes
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    ORDER BY p.id ASC
");

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}
mysqli_free_result($result);

api_respond($products);
