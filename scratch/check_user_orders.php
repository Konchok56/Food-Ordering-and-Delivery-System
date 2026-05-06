<?php
include('core/db.php');
$email = 'sheetpillo@gmail.com';
$stmt = $pdo->prepare("SELECT id, status, customer_name, customer_email FROM orders WHERE customer_email = ?");
$stmt->execute([$email]);
$orders = $stmt->fetchAll();
print_r($orders);

$stmt2 = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
$stmt2->execute([$email]);
$user = $stmt2->fetch();
print_r($user);
