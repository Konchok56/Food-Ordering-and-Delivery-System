<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__), RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getRealPath();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) continue;
    if (str_contains($path, DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR)) continue;
    
    $c = file_get_contents($path);
    $old = ' integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA=="';
    if (str_contains($c, $old)) {
        $c = str_replace($old, '', $c);
        file_put_contents($path, $c);
        echo "Removed integrity from: " . basename($path) . "\n";
    }
}
echo "Integrity cleanup complete.\n";
