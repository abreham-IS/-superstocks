<?php
// ============================================================
//  db.php — MySQLi Procedural Database Connection
//  Compatible with PHP 7.4+
// ============================================================

require_once __DIR__ . '/config.php';

/**
 * Returns a shared MySQLi connection (procedural style).
 * Creates the connection once per request and reuses it.
 *
 * @return mysqli
 */
function get_conn() {
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (!$conn) {
            if (PHP_SAPI === 'cli') {
                echo "Database connection failed: " . mysqli_connect_error() . "\n";
            } else {
                if (ob_get_level()) ob_clean();
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
            }
            exit;
        }

        mysqli_set_charset($conn, 'utf8mb4');
    }

    return $conn;
}
