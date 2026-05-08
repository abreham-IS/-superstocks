<?php
// ============================================================
//  get_settings.php — Read Application Settings
// ============================================================

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth();

/** @var mysqli $conn */
$conn     = get_conn();
$result   = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
$settings = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if (!$settings) {
    api_error(500, 'Settings record not found. Please re-import schema.sql.');
}

api_respond($settings);
