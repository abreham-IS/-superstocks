<?php
//  response.php — Unified JSON response helpers

// Catch PHP warnings/notices and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (ob_get_level()) ob_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'error' => 'PHP Error: ' . $errstr,
        'file'  => basename($errfile),
        'line'  => $errline,
    ]);
    exit;
});

// Catch fatal errors and return as JSON
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'error' => 'Fatal: ' . $e['message'],
            'file'  => basename($e['file']),
            'line'  => $e['line'],
        ]);
    }
});

// Buffer output so stray whitespace/warnings don't break JSON
if (!ob_get_level()) ob_start();

/**
 * Send a JSON success response and exit.
 * @param array|mixed $payload
 * @param int         $status  HTTP status code
 */
function api_respond($payload, $status = 200) {
    if (ob_get_level()) ob_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

/**
 * Send a JSON error response and exit.
 * @param int    $status  HTTP status code
 * @param string $message Error message
 * @param array  $extra   Extra fields to include
 */
function api_error($status, $message, $extra = []) {
    $payload = array_merge(['error' => $message], $extra);
    api_respond($payload, $status);
}
