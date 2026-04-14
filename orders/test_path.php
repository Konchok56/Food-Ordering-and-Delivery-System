<?php
$path_from_db = "uploads/foods/food_1774698727_018c03fc.webp";
$prepended = dirname(__FILE__) . "/../" . $path_from_db;

echo "Script Dir: " . dirname(__FILE__) . "\n";
echo "Resolved path: $prepended\n";

if (file_exists($prepended)) {
    echo "File exists!\n";
} else {
    echo "File DOES NOT exist.\n";
}
