<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT name, category, image_path FROM foods WHERE image_path IS NOT NULL AND image_path != '' LIMIT 10");
$foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Image Paths in DB:\n";
foreach ($foods as $f) {
    $exists = file_exists($f['image_path']) ? "EXISTS" : "MISSING";
    echo "[{$exists}] {$f['name']} ({$f['category']}): {$f['image_path']}\n";
}
