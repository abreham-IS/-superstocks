<?php
// ============================================================
//  auth.php — Authentication Helper
// ============================================================

/**
 * Checks that the current request has a valid PHP session.
 * Sends 401 if not logged in, 403 if wrong role.
 *
 * @param  string|null $required_role  'Admin' or 'Manager' or null
 * @return array  ['id', 'username', 'role']
 */
function require_auth($required_role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        if (ob_get_level()) ob_clean();
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Not authenticated. Please log in.']);
        exit;
    }

    if ($required_role !== null && $_SESSION['role'] !== $required_role) {
        if (ob_get_level()) ob_clean();
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Access denied. You do not have permission for this action.']);
        exit;
    }

    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
    ];
}
