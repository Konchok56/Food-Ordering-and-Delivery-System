<?php
/**
 * SwiftBite — Application Configuration
 * Single source of truth for all app-wide settings.
 *
 * Sensitive values (DB credentials, SMTP passwords, API keys) are loaded
 * from the .env file in the project root. See .env.example for the template.
 */

// ── Load environment variables ────────────────────────────
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// ── Environment ────────────────────────────────────────────
// Set to 'production' on a live server to hide error details.
define('APP_ENV', env('APP_ENV', 'development'));

// ── Database ───────────────────────────────────────────────
define('DB_HOST',    env('DB_HOST', 'localhost'));
define('DB_NAME',    env('DB_NAME', 'food_ordering'));
define('DB_USER',    env('DB_USER', 'root'));
define('DB_PASS',    env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

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
define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY', ''));

// ── Mail Configuration ─────────────────────────────────────
define('MAIL_HOST',       env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT',       (int) env('MAIL_PORT', 587));
define('MAIL_USER',       env('MAIL_USER', ''));
define('MAIL_PASS',       env('MAIL_PASS', ''));
define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', env('MAIL_USER', '')));
define('MAIL_FROM_NAME',  env('MAIL_FROM_NAME', 'SwiftBite'));
