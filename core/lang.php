<?php
/**
 * SwiftBite — Language & Localization Helper
 * Manages language switching, cookie/session caching, and fallback strings.
 */

// 1. Language switching detection via GET parameter
if (isset($_GET['lang'])) {
    $lang = preg_replace('/[^a-z]/', '', strtolower($_GET['lang']));
    $allowed_langs = ['en', 'ne', 'ja'];
    
    if (in_array($lang, $allowed_langs, true)) {
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/'); // Persist for 1 year
    }
    
    // Redirect back to the HTTP referer to avoid query param cluttering
    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    $parsed_url = parse_url($referer);
    $path = $parsed_url['path'] ?? 'index.php';
    $query_params = [];
    
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
        unset($query_params['lang']); // Ensure 'lang' parameter doesn't cause redirect loop
    }
    
    $new_url = $path;
    if (!empty($query_params)) {
        $new_url .= '?' . http_build_query($query_params);
    }
    
    header("Location: $new_url");
    exit;
}

// 2. Fetch the active language (default to English 'en')
$current_lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';

// Load language translation arrays
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
$translations = [];
if (file_exists($lang_file)) {
    $translations = include $lang_file;
}

// Fallback to English dictionary if the active language is not English
$fallback_translations = [];
if ($current_lang !== 'en') {
    $en_file = __DIR__ . '/../lang/en.php';
    if (file_exists($en_file)) {
        $fallback_translations = include $en_file;
    }
}

/**
 * Translate helper function.
 * 
 * @param string $key The translation lookup key.
 * @param string $default Optional default fallback text if lookup fails.
 * @return string The translated text.
 */
function __($key, $default = '') {
    global $translations, $fallback_translations;
    
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    if (isset($fallback_translations[$key])) {
        return $fallback_translations[$key];
    }
    
    return !empty($default) ? $default : $key;
}

/**
 * Translate standard digits to active language's script (e.g. Nepali digits).
 * 
 * @param mixed $number String or numeric to translate.
 * @return string Translated string.
 */
function t_num($number) {
    $current_lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
    if ($current_lang === 'ne') {
        $en_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $ne_digits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return str_replace($en_digits, $ne_digits, (string)$number);
    }
    return (string)$number;
}

/**
 * Helper to translate delivery time strings like "20-25 min".
 * 
 * @param string $time
 * @return string
 */
function t_delivery_time($time) {
    $time_translated = t_num($time);
    $current_lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
    if ($current_lang === 'ne') {
        return str_replace(['min', 'mins'], ['मिनेट', 'मिनेट'], $time_translated);
    }
    if ($current_lang === 'ja') {
        return str_replace(['min', 'mins'], ['分', '分'], $time_translated);
    }
    return $time_translated;
}

// 3. Define activeLang for inline JavaScript compatibility
$activeLang = $current_lang;


