<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');

// Validate CSRF
requireCsrf();

// Must have verified OTP
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['otp_email'])) {
    header('Location: ../auth/forgot_password.php');
    exit;
}

$email = $_SESSION['otp_email'];
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Validate password
if (empty($password) || strlen($password) < 6) {
    $_SESSION['rp_error'] = 'Password must be at least 6 characters long.';
    header('Location: ../auth/reset_password.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['rp_error'] = 'Passwords do not match.';
    header('Location: ../auth/reset_password.php');
    exit;
}

// Hash new password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Update password and clear reset tokens
$stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
$stmt->execute([$hashedPassword, $email]);

// Clear all OTP session data
unset(
    $_SESSION['otp_email'],
    $_SESSION['otp_verified'],
    $_SESSION['otp_attempts']
);

// Set success message for login page
$_SESSION['login_success'] = 'Password reset successful! Please login with your new password.';

header('Location: ../auth/login.php');
exit;
?>

