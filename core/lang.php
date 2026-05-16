<?php
/**
 * SwiftBite — Language / Localization Helper
 * ============================================
 * Include this file once via bootstrap.php.
 *
 * Usage in any template or page:
 *   <?= __('nav.home') ?>
 *   <?= __('hero.headline', [], true) ?>   ← set 3rd arg true to allow HTML
 *
 * Change language:
 *   <a href="?lang=ne">नेपाली</a>
 *   <a href="?lang=en">English</a>
 */

// ── 1. Supported languages (code => label) ────────────────────────────────
define('SUPPORTED_LANGS', [
    'en' => 'English',
    'ne' => 'नेपाली',
    'hi' => 'हिन्दी',
    'es' => 'Español',
]);

define('DEFAULT_LANG', 'en');

// ── 2. Detect & persist language choice ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Priority: URL param → session → browser Accept-Language → default
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], SUPPORTED_LANGS)) {
    $_SESSION['app_lang'] = $_GET['lang'];
}

if (!isset($_SESSION['app_lang'])) {
    // Try to detect from browser
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
    $_SESSION['app_lang'] = array_key_exists($browserLang, SUPPORTED_LANGS)
        ? $browserLang
        : DEFAULT_LANG;
}

$GLOBALS['_current_lang'] = $_SESSION['app_lang'];

// ── 3. Load the language strings ──────────────────────────────────────────
$_langFile = __DIR__ . '/../lang/' . $GLOBALS['_current_lang'] . '.php';

if (!file_exists($_langFile)) {
    // Fallback to English if the lang file is missing
    $_langFile = __DIR__ . '/../lang/en.php';
    $GLOBALS['_current_lang'] = 'en';
}

$GLOBALS['_lang_strings'] = require $_langFile;

// Also load English as fallback for missing keys in other languages
if ($GLOBALS['_current_lang'] !== 'en') {
    $GLOBALS['_lang_fallback'] = require __DIR__ . '/../lang/en.php';
} else {
    $GLOBALS['_lang_fallback'] = [];
}

// ── 4. Translation function ───────────────────────────────────────────────
/**
 * Translate a key.
 *
 * @param  string  $key        Dot-notation key e.g. 'nav.home'
 * @param  array   $replace    Placeholder replacements e.g. ['name' => 'John']
 *                             Use :name in the translation string.
 * @param  bool    $allowHtml  If true, value is returned raw (allows <br> etc.)
 * @return string
 */
function __(string $key, array $replace = [], bool $allowHtml = false): string
{
    $strings  = $GLOBALS['_lang_strings']  ?? [];
    $fallback = $GLOBALS['_lang_fallback'] ?? [];

    // Look up in current language, then English fallback, then the key itself
    $value = $strings[$key] ?? $fallback[$key] ?? $key;

    // Handle :placeholder replacements
    foreach ($replace as $placeholder => $replacement) {
        $value = str_replace(':' . $placeholder, htmlspecialchars((string) $replacement), $value);
    }

    return $allowHtml ? $value : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the currently active language code.
 */
function currentLang(): string
{
    return $GLOBALS['_current_lang'] ?? DEFAULT_LANG;
}

/**
 * Check if the current language is RTL.
 * Add RTL language codes here when you add them.
 */
function isRtlLang(): bool
{
    return in_array(currentLang(), ['ar', 'fa', 'he', 'ur'], true);
}

/**
 * Build a URL that switches to the given language,
 * preserving the current page and query string.
 */
function langSwitchUrl(string $langCode): string
{
    $params = $_GET;
    $params['lang'] = $langCode;
    // Remove 'lang' redirect loop from URL after it is stored in session
    return '?' . http_build_query($params);
}
