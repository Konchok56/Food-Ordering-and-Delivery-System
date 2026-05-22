<?php
/**
 * SwiftBite — Syntax-Safe Emoji → Font Awesome replacement script using PHP Tokenizer (AST-like)
 * Run from project root: php scratch/syntax_safe_replace.php
 */

$replacements = [
    // ── Status / UI ──────────────────────────────────────────
    '✅'  => '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i>',
    '❌'  => '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>',
    '⚠️' => '<i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i>',
    '⚠'  => '<i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i>',
    'ℹ️' => '<i class="fa-solid fa-circle-info" style="color:#3b82f6"></i>',
    'ℹ'  => '<i class="fa-solid fa-circle-info" style="color:#3b82f6"></i>',
    '⏳' => '<i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i>',
    '⏰' => '<i class="fa-regular fa-clock"></i>',
    '🎉' => '<i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i>',
    '🔔' => '<i class="fa-solid fa-bell"></i>',

    // ── Navigation / User ─────────────────────────────────────
    '👤'   => '<i class="fa-solid fa-user"></i>',
    '👨‍💼' => '<i class="fa-solid fa-user-tie"></i>',
    '👨‍🍳' => '<i class="fa-solid fa-kitchen-set"></i>',
    '🚪'   => '<i class="fa-solid fa-right-from-bracket"></i>',
    '📊'   => '<i class="fa-solid fa-chart-bar"></i>',
    '📦'   => '<i class="fa-solid fa-box"></i>',
    '📱'   => '<i class="fa-solid fa-mobile-screen"></i>',
    '🏠'   => '<i class="fa-solid fa-house"></i>',

    // ── Food / Delivery ───────────────────────────────────────
    '🍔'  => '<i class="fa-solid fa-burger"></i>',
    '🍕'  => '<i class="fa-solid fa-pizza-slice"></i>',
    '🍣'  => '<i class="fa-solid fa-fish"></i>',
    '🍛'  => '<i class="fa-solid fa-bowl-food"></i>',
    '🍜'  => '<i class="fa-solid fa-bowl-food"></i>',
    '🥘'  => '<i class="fa-solid fa-bowl-food"></i>',
    '🥡'  => '<i class="fa-solid fa-bowl-rice"></i>',
    '🌮'  => '<i class="fa-solid fa-bowl-food"></i>',
    '🥟'  => '<i class="fa-solid fa-bowl-food"></i>',
    '🥩'  => '<i class="fa-solid fa-drumstick-bite"></i>',
    '☕'  => '<i class="fa-solid fa-mug-hot"></i>',
    '🧁'  => '<i class="fa-solid fa-cake-candles"></i>',
    '🦐'  => '<i class="fa-solid fa-fish"></i>',
    '🍴'  => '<i class="fa-solid fa-utensils"></i>',
    '🍽️' => '<i class="fa-solid fa-utensils"></i>',
    '🍽'  => '<i class="fa-solid fa-utensils"></i>',
    '🥗'  => '<i class="fa-solid fa-leaf"></i>',
    '🛵'  => '<i class="fa-solid fa-motorcycle"></i>',
    '🚚'  => '<i class="fa-solid fa-truck"></i>',
    '🚲'  => '<i class="fa-solid fa-bicycle"></i>',
    '🏍️' => '<i class="fa-solid fa-motorcycle"></i>',
    '🏍'  => '<i class="fa-solid fa-motorcycle"></i>',

    // ── General ───────────────────────────────────────────────
    '⭐'  => '<i class="fa-solid fa-star" style="color:#f59e0b"></i>',
    '✨'  => '<i class="fa-solid fa-wand-magic-sparkles" style="color:#f59e0b"></i>',
    '🔥'  => '<i class="fa-solid fa-fire" style="color:#ef4444"></i>',
    '❤️' => '<i class="fa-solid fa-heart" style="color:#ef4444"></i>',
    '❤'  => '<i class="fa-solid fa-heart" style="color:#ef4444"></i>',
    '🤍'  => '<i class="fa-regular fa-heart"></i>',
    '🛒'  => '<i class="fa-solid fa-cart-shopping"></i>',
    '📍'  => '<i class="fa-solid fa-location-dot"></i>',
    '💰'  => '<i class="fa-solid fa-coins"></i>',
    '🗑️' => '<i class="fa-solid fa-trash"></i>',
    '🗑'  => '<i class="fa-solid fa-trash"></i>',
    '🔐'  => '<i class="fa-solid fa-lock"></i>',
    '🔑'  => '<i class="fa-solid fa-key"></i>',
    '📧'  => '<i class="fa-solid fa-envelope"></i>',
    '📞'  => '<i class="fa-solid fa-phone"></i>',
    '🚀'  => '<i class="fa-solid fa-rocket"></i>',
    '▶️' => '<i class="fa-brands fa-youtube"></i>',
    '☰'  => '<i class="fa-solid fa-bars"></i>',

    // ── Missing from V2 ───────────────────────────────────────
    '⚡'  => '<i class="fa-solid fa-bolt"></i>',
    '🌟'  => '<i class="fa-solid fa-star"></i>',
    '→'  => '<i class="fa-solid fa-arrow-right"></i>',
    '💬'  => '<i class="fa-solid fa-comment"></i>',
    '🧐'  => '<i class="fa-solid fa-magnifying-glass"></i>',
    '😋'  => '<i class="fa-solid fa-face-smile"></i>',
    '🤖'  => '<i class="fa-solid fa-robot"></i>',
    '🎧'  => '<i class="fa-solid fa-headset"></i>',
    '🕒'  => '<i class="fa-solid fa-clock"></i>',
    '😊'  => '<i class="fa-solid fa-face-smile"></i>',
    '👋'  => '<i class="fa-solid fa-hand-wave"></i>',

    // ── HTML entity versions ──────────────────────────────────
    '&#9728;&#65039;'    => '<i class="fa-solid fa-sun"></i>',
    '&#127769;'          => '<i class="fa-solid fa-moon"></i>',
    '&#x1F37D;&#xFE0F;' => '<i class="fa-solid fa-utensils"></i>',
    '&#x1F37D;'          => '<i class="fa-solid fa-utensils"></i>',
    '&#x1F550;'          => '<i class="fa-regular fa-clock"></i>',
    '&#x1F50D;'          => '<i class="fa-solid fa-magnifying-glass"></i>',
    '&#x2764;&#xFE0F;'  => '<i class="fa-solid fa-heart" style="color:#ef4444"></i>',
    '&#x1F90D;'          => '<i class="fa-regular fa-heart"></i>',
    '&#9733;'            => '<i class="fa-solid fa-star" style="color:#f59e0b"></i>',
    '&#10003;'           => '<i class="fa-solid fa-check"></i>',
];

function is_double_quoted_literal($text) {
    $firstChar = strtolower(substr($text, 0, 1));
    if ($firstChar === '"') {
        return true;
    }
    if ($firstChar === 'b' && substr($text, 1, 1) === '"') {
        return true;
    }
    return false;
}

function get_safe_replacement($original_replacement, $escape_quotes) {
    if ($escape_quotes) {
        return str_replace('"', '\"', $original_replacement);
    }
    return $original_replacement;
}

function process_token_text($text, $replacements, $escape_quotes) {
    $search = array_keys($replacements);
    $replace = [];
    foreach ($replacements as $key => $val) {
        $replace[] = get_safe_replacement($val, $escape_quotes);
    }
    return str_replace($search, $replace, $text);
}

function process_php_file($path, $replacements) {
    $code = file_get_contents($path);
    $tokens = token_get_all($code);
    
    $in_double_quote = false;
    $in_heredoc = false;
    
    $modified = false;
    $reconstructed = '';
    
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $id = $token[0];
            $text = $token[1];
            
            // Track Heredoc boundaries
            if ($id === T_START_HEREDOC) {
                $in_heredoc = true;
            } elseif ($id === T_END_HEREDOC) {
                $in_heredoc = false;
            }
            
            // Context-aware replacement
            if ($id === T_INLINE_HTML) {
                // HTML: No escaping needed
                $new_text = process_token_text($text, $replacements, false);
                if ($new_text !== $text) {
                    $text = $new_text;
                    $modified = true;
                }
            } elseif ($id === T_CONSTANT_ENCAPSED_STRING) {
                // String literal ('foo' or "foo")
                $escape = is_double_quoted_literal($text);
                $new_text = process_token_text($text, $replacements, $escape);
                if ($new_text !== $text) {
                    $text = $new_text;
                    $modified = true;
                }
            } elseif ($id === T_ENCAPSED_AND_WHITESPACE) {
                // Part of dynamic string ("foo $bar")
                $escape = !$in_heredoc; // Escape inside double-quoted string, but not in heredoc
                $new_text = process_token_text($text, $replacements, $escape);
                if ($new_text !== $text) {
                    $text = $new_text;
                    $modified = true;
                }
            }
            
            $reconstructed .= $text;
        } else {
            // Single character tokens like ; = . "
            if ($token === '"' && !$in_heredoc) {
                $in_double_quote = !$in_double_quote;
            }
            $reconstructed .= $token;
        }
    }
    
    if ($modified) {
        file_put_contents($path, $reconstructed);
        return true;
    }
    return false;
}

// -------------------------------------------------------------
// Script Execution
// -------------------------------------------------------------

$projectRoot = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS)
);

$totalChanged = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getRealPath();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) continue;
    
    // Skip scratch folder except our specifically created test file
    if (str_contains($path, DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR)) {
        if (basename($path) !== 'test_syntax_safety.php') {
            continue;
        }
    }
    
    if (process_php_file($path, $replacements)) {
        echo "Successfully updated (syntax-safe): " . basename($path) . " ($path)\n";
        $totalChanged++;
    }
}

echo "\n✔ Done! $totalChanged files processed and updated safely.\n";
