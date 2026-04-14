<?php
require_once 'core/bootstrap.php';
$user_id = 5; // Jethalal's ID
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
print_r($stmt->fetchAll());
