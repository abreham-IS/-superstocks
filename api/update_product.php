<?php
// ============================================================
//  update_product.php — Edit an Existing Product
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error(405, 'Only POST requests are accepted.');
}

require_auth();

function is_valid_date($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $parts = explode('-', $date);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

$id              = trim($_POST['id']              ?? '');
$name            = trim($_POST['name']            ?? '');
$category_id     = trim($_POST['category_id']     ?? '');
$quantity        = trim($_POST['quantity']         ?? '');
$price           = trim($_POST['price']            ?? '');
$production_date = trim($_POST['production_date']  ?? '');
$expiry_date     = trim($_POST['expiry_date']      ?? '');
$supplier        = trim($_POST['supplier']         ?? '');
$notes           = trim($_POST['notes']            ?? '');

if (!is_numeric($id) || (int)$id <= 0) {
    api_error(400, 'A valid product id is required.');
}

$missing = [];
if ($name === '')            $missing[] = 'name';
if ($category_id === '')     $missing[] = 'category_id';
if ($quantity === '')        $missing[] = 'quantity';
if ($price === '')           $missing[] = 'price';
if ($production_date === '') $missing[] = 'production_date';
if ($expiry_date === '')     $missing[] = 'expiry_date';

if (!empty($missing)) {
    api_error(400, 'Missing required fields: ' . implode(', ', $missing));
}

if (!is_numeric($quantity) || (int)$quantity < 0) {
    api_error(422, 'Quantity must be a non-negative whole number.');
}
if (!is_numeric($price) || (float)$price < 0) {
    api_error(422, 'Price must be a non-negative number.');
}
if (!is_numeric($category_id) || (int)$category_id <= 0) {
    api_error(422, 'category_id must be a positive integer.');
}
if (!is_valid_date($production_date)) {
    api_error(422, 'production_date must be a valid date in YYYY-MM-DD format.');
}
if (!is_valid_date($expiry_date)) {
    api_error(422, 'expiry_date must be a valid date in YYYY-MM-DD format.');
}
if ($expiry_date <= $production_date) {
    api_error(422, 'expiry_date must be after production_date.');
}

/** @var mysqli $conn */
$conn = get_conn();

$chk = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
mysqli_stmt_bind_param($chk, 'i', $category_id);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
if (mysqli_stmt_num_rows($chk) === 0) {
    mysqli_stmt_close($chk);
    api_error(422, 'The selected category does not exist.');
}
mysqli_stmt_close($chk);

$id_int       = (int)$id;
$cat_int      = (int)$category_id;
$qty_int      = (int)$quantity;
$price_flt    = (float)$price;
$supplier_val = $supplier !== '' ? $supplier : null;
$notes_val    = $notes    !== '' ? $notes    : null;

$stmt = mysqli_prepare($conn, "
    UPDATE products SET
        name = ?, category_id = ?, quantity = ?, price = ?,
        production_date = ?, expiry_date = ?, supplier = ?, notes = ?
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, 'siidssssi',
    $name, $cat_int, $qty_int, $price_flt,
    $production_date, $expiry_date, $supplier_val, $notes_val, $id_int
);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected === 0) {
    $chk = mysqli_prepare($conn, "SELECT id FROM products WHERE id = ?");
    mysqli_stmt_bind_param($chk, 'i', $id_int);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    $exists = mysqli_stmt_num_rows($chk) > 0;
    mysqli_stmt_close($chk);
    if (!$exists) {
        api_error(404, 'Product not found.');
    }
}

$stmt = mysqli_prepare($conn, "
    SELECT p.id, p.name, p.category_id, c.name AS category_name,
           p.quantity, p.price, p.production_date, p.expiry_date, p.supplier, p.notes
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id_int);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

api_respond($product);
