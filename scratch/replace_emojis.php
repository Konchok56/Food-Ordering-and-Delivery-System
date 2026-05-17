<?php
/**
 * SwiftBite — Emoji → Font Awesome replacement script
 * Run from project root: php scratch/replace_emojis.php
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
    '👤'     => '<i class="fa-solid fa-user"></i>',
    '👨‍💼'  => '<i class="fa-solid fa-user-tie"></i>',
    '👨‍🍳'  => '<i class="fa-solid fa-kitchen-set"></i>',
    '🚪'     => '<i class="fa-solid fa-right-from-bracket"></i>',
    '📊'     => '<i class="fa-solid fa-chart-bar"></i>',
    '📦'     => '<i class="fa-solid fa-box"></i>',
    '📱'     => '<i class="fa-solid fa-mobile-screen"></i>',
    '🏠'     => '<i class="fa-solid fa-house"></i>',

    // ── Food / Delivery ───────────────────────────────────────
    '🍔'    => '<i class="fa-solid fa-burger"></i>',
    '🍕'    => '<i class="fa-solid fa-pizza-slice"></i>',
    '🍣'    => '<i class="fa-solid fa-fish"></i>',
    '🍛'    => '<i class="fa-solid fa-bowl-food"></i>',
    '🍜'    => '<i class="fa-solid fa-bowl-food"></i>',
    '🥘'    => '<i class="fa-solid fa-bowl-food"></i>',
    '🥡'    => '<i class="fa-solid fa-bowl-rice"></i>',
    '🌮'    => '<i class="fa-solid fa-bowl-food"></i>',
    '🥟'    => '<i class="fa-solid fa-bowl-food"></i>',
    '🥩'    => '<i class="fa-solid fa-drumstick-bite"></i>',
    '☕'    => '<i class="fa-solid fa-mug-hot"></i>',
    '🧁'    => '<i class="fa-solid fa-cake-candles"></i>',
    '🦐'    => '<i class="fa-solid fa-fish"></i>',
    '🍴'    => '<i class="fa-solid fa-utensils"></i>',
    '🍽️'   => '<i class="fa-solid fa-utensils"></i>',
    '🍽'    => '<i class="fa-solid fa-utensils"></i>',
    '🥗'    => '<i class="fa-solid fa-leaf"></i>',
    '🛵'    => '<i class="fa-solid fa-motorcycle"></i>',
    '🚚'    => '<i class="fa-solid fa-truck"></i>',
    '🚲'    => '<i class="fa-solid fa-bicycle"></i>',
    '🏍️'   => '<i class="fa-solid fa-motorcycle"></i>',
    '🏍'    => '<i class="fa-solid fa-motorcycle"></i>',

    // ── General ───────────────────────────────────────────────
    '⭐'    => '<i class="fa-solid fa-star" style="color:#f59e0b"></i>',
    '✨'    => '<i class="fa-solid fa-wand-magic-sparkles" style="color:#f59e0b"></i>',
    '🔥'    => '<i class="fa-solid fa-fire" style="color:#ef4444"></i>',
    '❤️'   => '<i class="fa-solid fa-heart" style="color:#ef4444"></i>',
    '❤'     => '<i class="fa-solid fa-heart" style="color:#ef4444"></i>',
    '🤍'    => '<i class="fa-regular fa-heart"></i>',
    '🛒'    => '<i class="fa-solid fa-cart-shopping"></i>',
    '📍'    => '<i class="fa-solid fa-location-dot"></i>',
    '💰'    => '<i class="fa-solid fa-coins"></i>',
    '🗑️'   => '<i class="fa-solid fa-trash"></i>',
    '🗑'    => '<i class="fa-solid fa-trash"></i>',
    '🔐'    => '<i class="fa-solid fa-lock"></i>',
    '🔑'    => '<i class="fa-solid fa-key"></i>',
    '📧'    => '<i class="fa-solid fa-envelope"></i>',
    '📞'    => '<i class="fa-solid fa-phone"></i>',
    '🚀'    => '<i class="fa-solid fa-rocket"></i>',
    '▶️'   => '<i class="fa-brands fa-youtube"></i>',
    '☰'    => '<i class="fa-solid fa-bars"></i>',

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

// Walk all .php files except vendor and scratch
$projectRoot = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS)
);

$totalChanged = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getRealPath();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) continue;
    if (str_contains($path, DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR)) continue;

    $original = file_get_contents($path);
    $updated  = str_replace(array_keys($replacements), array_values($replacements), $original);

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        echo "Updated: " . basename($path) . " (" . $file->getPathname() . ")\n";
        $totalChanged++;
    }
}

echo "\n✔ Done! $totalChanged files updated.\n";
