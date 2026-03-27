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
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Update user with reset token (THIS WILL OVERWRITE REMEMBER ME TOKEN!)
$stmt = $pdo->prepare("UPDATE users SET reset_token=?, token_expiry=? WHERE email=?");
$stmt->execute([$token, $expiry, $email]);

// Simulate email (IMPORTANT)
echo "Reset Link: <br>";
echo "<a href='../auth/reset_password.php?token=$token'>Click Here to Reset Password</a>";
echo "<br><br><a href='../auth/login.php'>Back to Login</a>";
?>