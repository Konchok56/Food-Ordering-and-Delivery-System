<?php
session_start();
include('../includes/db.php');

// 🔒 Check login
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$food_name = $_POST['food_name'];
$price = $_POST['price'];

// Check if already in cart
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND food_name = ?");
$stmt->execute([$user_id, $food_name]);

if($stmt->rowCount() > 0){
    // Increase quantity
    $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND food_name = ?")
        ->execute([$user_id, $food_name]);
} else {
    // Insert new
    $pdo->prepare("INSERT INTO cart (user_id, food_name, price, quantity) VALUES (?, ?, ?, 1)")
        ->execute([$user_id, $food_name, $price]);
}

header("Location: ../cart.php");
exit;
?>
