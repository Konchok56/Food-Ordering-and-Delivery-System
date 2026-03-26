<?php
session_start();
include('../includes/db.php');

$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check if email exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

if($stmt->rowCount() > 0){
    echo "Email already exists!";
    exit;
}

// Insert user
$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $password]);

// 🔥 REDIRECT FIX
header("Location: ../auth/login.php");
exit;  // ✅ THIS LINE IS VERY IMPORTANT
?>
