<?php
/**
 * CSRF Token Helper
 * Generates and validates CSRF tokens to protect forms.
 */

function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

function validateCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf() {
    if (!validateCsrfToken()) {
        // For AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
            exit;
        }
        http_response_code(403);
        die('<h2 style="color:red;text-align:center;margin-top:50px;">⚠️ Security error: Invalid form token. Please go back and try again.</h2>');
    }
}
