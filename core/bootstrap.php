<?php
/**
 * SwiftBite — Bootstrap
 * Include this ONE file at the top of every page.
 * It handles: session, DB, CSRF, auth helpers, flash messages, security headers.
 */

// ── Config first ───────────────────────────────────────────
require_once __DIR__ . '/config.php';

// ── Error reporting based on environment ──────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Security headers ──────────────────────────────────────
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ── Session ───────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ── Database (singleton) ──────────────────────────────────
if (!isset($GLOBALS['pdo'])) {
    try {
        $GLOBALS['pdo'] = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        if (APP_ENV === 'development') {
            die('<div style="font-family:monospace;color:red;padding:20px">DB Error: ' . htmlspecialchars($e->getMessage()) . '</div>');
        }
        die('<div style="font-family:sans-serif;text-align:center;margin-top:80px"><h2>Service Unavailable</h2><p>We\'re having trouble connecting. Please try again later.</p></div>');
    }
}
$pdo = $GLOBALS['pdo'];

// ── Auto-apply pending DB migrations ─────────────────────
// require_once __DIR__ . '/auto_migrate.php';
// runAutoMigrations($pdo);

// ── Remember-me auto login ────────────────────────────────
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? LIMIT 1");
    $stmt->execute([$_COOKIE['remember_token']]);
    $rememberUser = $stmt->fetch();
    if ($rememberUser) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $rememberUser['id'];
        $_SESSION['user_name'] = $rememberUser['name'];
        $_SESSION['role']      = $rememberUser['role'];
    }
}

// ── Check if logged in user is banned ───────────────────────
if (isset($_SESSION['user_id'])) {
    try {
        $banCheckStmt = $pdo->prepare("SELECT is_banned, ban_reason FROM users WHERE id = ? LIMIT 1");
        $banCheckStmt->execute([$_SESSION['user_id']]);
        $banUserCheck = $banCheckStmt->fetch();
        if ($banUserCheck && isset($banUserCheck['is_banned']) && $banUserCheck['is_banned'] == 1) {
            $_SESSION['banned_reason'] = $banUserCheck['ban_reason'];
            unset($_SESSION['user_id']);
            unset($_SESSION['role']);
            setcookie('remember_token', '', time() - 3600, '/');
            header("Location: " . SITE_BASE_URL . "/auth/banned.php");
            exit;
        }
    } catch (Exception $e) { /* Ignore if schema not migrated yet */ }
}

// ── CSRF helpers ─────────────────────────────────────────
require_once __DIR__ . '/csrf.php';

// ── Cart helper ────────────────────────────────────────────
require_once __DIR__ . '/cart_helper.php';

// ── Notification helper ───────────────────────────────────
require_once __DIR__ . '/notification_helper.php';

// ── Validation helpers ────────────────────────────────────
require_once __DIR__ . '/validation.php';

// ── Auth guard functions ──────────────────────────────────

/**
 * Redirect to login if not logged in.
 */
function requireLogin(string $redirectBack = ''): void {
    if (!isset($_SESSION['user_id'])) {
        $url = SITE_BASE_URL . '/auth/login.php';
        if ($redirectBack) $url .= '?next=' . urlencode($redirectBack);
        header("Location: $url");
        exit;
    }
}

/**
 * Require a specific role. Calls requireLogin first.
 * @param string|array $roles  e.g. 'admin' or ['admin','restaurant']
 */
function requireRole($roles): void {
    requireLogin();
    $roles = (array) $roles;
    $role  = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        die(renderError(403, 'Access Denied', 'You do not have permission to view this page.'));
    }
}

/**
 * Check if the current user has a given role (non-blocking).
 */
function hasRole(string $role): bool {
    return ($_SESSION['role'] ?? '') === $role;
}

/**
 * Check if any user is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// ── Flash message helpers ─────────────────────────────────

/**
 * Set a one-time flash message.
 * @param string $type  'success' | 'error' | 'info' | 'warning'
 */
function flash(string $type, string $message): void {
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Render and clear all pending flash messages as HTML.
 */
function renderFlash(): string {
    if (empty($_SESSION['_flash'])) return '';
    $icons = ['success' => '✅', 'error' => '❌', 'info' => 'ℹ️', 'warning' => '⚠️'];
    $html  = '';
    foreach ($_SESSION['_flash'] as $f) {
        $icon = $icons[$f['type']] ?? 'ℹ️';
        $html .= '<div class="flash-msg flash-' . htmlspecialchars($f['type']) . '">'
               . $icon . ' ' . htmlspecialchars($f['message'])
               . '</div>';
    }
    unset($_SESSION['_flash']);
    return $html;
}

// ── Redirect helper ───────────────────────────────────────
function redirect(string $path, bool $absolute = false): void {
    $url = $absolute ? $path : SITE_BASE_URL . '/' . ltrim($path, '/');
    header("Location: $url");
    exit;
}

// ── Error page renderer ───────────────────────────────────
function renderError(int $code, string $title, string $message): string {
    http_response_code($code);
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>{$code} — SwiftBite</title>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
        <style>
            *{box-sizing:border-box;margin:0;padding:0}
            body{font-family:'DM Sans',sans-serif;background:#fff8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
            .err{text-align:center;max-width:480px}
            .err-code{font-family:'Syne',sans-serif;font-size:6rem;font-weight:800;color:#ff4f00;line-height:1;margin-bottom:12px}
            .err-title{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:#1a1004;margin-bottom:12px}
            .err-msg{color:#8b6a44;margin-bottom:32px;line-height:1.7}
            .err-btn{display:inline-block;padding:14px 32px;background:#ff4f00;color:#fff;border-radius:999px;text-decoration:none;font-weight:700;transition:transform .2s}
            .err-btn:hover{transform:translateY(-3px)}
        </style>
    </head>
    <body>
        <div class="err">
            <div class="err-code">{$code}</div>
            <div class="err-title">{$title}</div>
            <p class="err-msg">{$message}</p>
            <a class="err-btn" href="JavaScript:history.back()">← Go Back</a>
        </div>
    </body>
    </html>
    HTML;
}

// ── Cart count (for navbar badge) ────────────────────────
$cartCount = isLoggedIn() ? getCartCount($pdo, $_SESSION['user_id']) : 0;
