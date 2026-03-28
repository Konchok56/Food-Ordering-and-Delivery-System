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
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../cart.php';

// Check if already in cart
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND food_name = ?");
$stmt->execute([$user_id, $food_name]);

if($stmt->rowCount() > 0){
    // Increase quantity
    $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND food_name = ?")
        ->execute([$quantity, $user_id, $food_name]);
} else {
    // Insert new
    $pdo->prepare("INSERT INTO cart (user_id, food_name, price, quantity) VALUES (?, ?, ?, ?)")
        ->execute([$user_id, $food_name, $price, $quantity]);
}

// Redirect — if it starts with ../ it's relative, otherwise prepend ../
if (strpos($redirect, '../') === 0) {
    header("Location: $redirect");
} else {
    header("Location: ../$redirect");
}
exit;
?>
