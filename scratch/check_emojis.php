<?php
$emojis = ['✅','❌','⚠️','ℹ️','⏳','⏰','🎉','🔔','👤','👨‍💼','👨‍🍳','🚪','📊','📦','📱','🏠','🍔','🍕','🍣','🍛','🍜','🥘','🥡','🌮','🥟','🥩','☕','🧁','🦐','🍴','🍽️','🍽','🥗','🛵','🚚','🚲','⭐','✨','🔥','❤️','❤','🤍','🛒','📍','💰','🗑️','🗑','🔐','🔑','📧','📞','🚀','☰'];
$found = [];
$root = dirname(__DIR__);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getRealPath();
    if (str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) continue;
    if (str_contains($path, DIRECTORY_SEPARATOR.'scratch'.DIRECTORY_SEPARATOR)) continue;
    $c = file_get_contents($path);
    foreach ($emojis as $e) {
        if (str_contains($c, $e)) {
            $found[] = basename($path) . ' => ' . $e;
        }
    }
}
if (empty($found)) {
    echo "All emojis replaced successfully!\n";
} else {
    echo "Remaining emojis found:\n";
    foreach ($found as $r) echo "  $r\n";
}
