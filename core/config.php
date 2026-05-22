<?php
/**
 * SwiftBite — Application Configuration
 * Single source of truth for all app-wide settings.
 */

// ── Environment ────────────────────────────────────────────
// Set to 'production' on a live server to hide error details.
define('APP_ENV', 'development');

// ── Database ───────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'food_ordering');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Site ───────────────────────────────────────────────────
define('SITE_NAME',     'SwiftBite');
define('SITE_TAGLINE',  'Food Delivery, Reinvented');
define('SITE_BASE_URL', '/food'); // no trailing slash

// ── Upload limits ──────────────────────────────────────────
define('MAX_UPLOAD_MB', 2);
define('UPLOAD_DIR',    __DIR__ . '/../uploads/');

// ── Security ───────────────────────────────────────────────
define('SESSION_LIFETIME', 7200);   // 2 hours (seconds)
define('REMEMBER_ME_DAYS', 30);

// ── Delivery ───────────────────────────────────────────────
define('DEFAULT_DELIVERY_FEE', 50);
define('FREE_DELIVERY_ABOVE',  1000);

// ── External APIs ──────────────────────────────────────────
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY_HERE');

// ── Mail Configuration ─────────────────────────────────────
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USER',       'sheetpillo@gmail.com');
define('MAIL_PASS',       'julx gwvm kfhv idlp');
define('MAIL_FROM_EMAIL', 'sheetpillo@gmail.com');
define('MAIL_FROM_NAME',  'SwiftBite');
