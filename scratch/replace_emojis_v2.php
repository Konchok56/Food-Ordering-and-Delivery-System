<?php
$replacements = [
    '⚡' => '<i class="fa-solid fa-bolt"></i>',
    '🌟' => '<i class="fa-solid fa-star"></i>',
    '→' => '<i class="fa-solid fa-arrow-right"></i>',
    '💬' => '<i class="fa-solid fa-comment"></i>',
    '🧐' => '<i class="fa-solid fa-magnifying-glass"></i>',
    '😋' => '<i class="fa-solid fa-face-smile"></i>',
    '🤖' => '<i class="fa-solid fa-robot"></i>',
    '🎧' => '<i class="fa-solid fa-headset"></i>',
    '🕒' => '<i class="fa-solid fa-clock"></i>',
    '😊' => '<i class="fa-solid fa-face-smile"></i>',
    '👋' => '<i class="fa-solid fa-hand-wave"></i>',
];

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
        echo "Updated: " . basename($path) . "\n";
        $totalChanged++;
    }
}

echo "\n✔ Done! $totalChanged files updated with missing icons.\n";
