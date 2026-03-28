<?php
session_start();
include('../includes/db.php');

$email = $_POST['email'];

// Check if email exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "Email not found!";
    exit;
}

// Generate token
$token = bin2hex(random_bytes(50));

// Use MySQL's NOW() so the timezone matches the check in reset_password.php
$stmt = $pdo->prepare("UPDATE users SET reset_token=?, token_expiry = NOW() + INTERVAL 1 HOUR WHERE email=?");
$stmt->execute([$token, $email]);

// Simulate email (IMPORTANT)
echo "Reset Link: <br>";
echo "<a href='../auth/reset_password.php?token=$token'>Click Here to Reset Password</a>";
echo "<br><br><a href='../auth/login.php'>Back to Login</a>";
?>