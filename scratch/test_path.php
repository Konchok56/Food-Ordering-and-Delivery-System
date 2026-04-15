<?php
$path_from_db = "uploads/foods/food_1774698727_018c03fc.webp";
$current_file = "orders/cart.php";
$prepended = "../" . $path_from_db;

echo "Path from DB: $path_from_db\n";
echo "Prepended path: $prepended\n";
echo "Resolved Absolute Path: " . realpath(dirname(__FILE__) . "/" . $prepended) . "\n";

if (file_exists($prepended)) {
    echo "File exists at prepended path!\n";
} else {
    echo "File DOES NOT exist at prepended path.\n";
}
