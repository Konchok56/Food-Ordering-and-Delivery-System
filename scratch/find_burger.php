<?php
require_once 'core/bootstrap.php';
$stmt = $pdo->prepare("SELECT name, image_path FROM foods WHERE name LIKE '%veg patties%'");
$stmt->execute();
print_r($stmt->fetchAll());
