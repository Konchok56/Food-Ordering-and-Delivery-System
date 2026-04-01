<?php
session_start();
include('../includes/db.php');
include('../includes/csrf.php');

// Validate CSRF
requireCsrf();

// Ensure session has the email
if (!isset($_SESSION['otp_email'])) {
    header('Location: ../auth/forgot_password.php');
    exit;
}

$email = $_SESSION['otp_email'];
$otp = trim($_POST['otp'] ?? '');

// Validate OTP format
if (empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
    $_SESSION['otp_error'] = 'Please enter a valid 6-digit code.';
    header('Location: ../auth/verify_otp.php');
    exit;
}

// Check attempt limit (max 5 tries)
$_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
if ($_SESSION['otp_attempts'] > 5) {
    // Too many attempts - clear everything
    unset($_SESSION['otp_email'], $_SESSION['otp_attempts']);
    
    // Invalidate the OTP in DB
    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, token_expiry = NULL WHERE email = ?");
    $stmt->execute([$email]);
    
    $_SESSION['fp_error'] = 'Too many failed attempts. Please request a new OTP.';
    header('Location: ../auth/forgot_password.php');
    exit;
}

// Fetch stored OTP hash and check expiry
$stmt = $pdo->prepare("SELECT reset_token, token_expiry FROM users WHERE email = ? AND token_expiry > NOW()");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['otp_error'] = 'OTP has expired. Please request a new one.';
    header('Location: ../auth/verify_otp.php');
    exit;
}

// Verify OTP against hash
if (!password_verify($otp, $user['reset_token'])) {
    $remaining = 5 - $_SESSION['otp_attempts'];
    $_SESSION['otp_error'] = "Invalid OTP code. You have $remaining attempt(s) remaining.";
    header('Location: ../auth/verify_otp.php');
    exit;
}

// ✅ OTP verified successfully!
$_SESSION['otp_verified'] = true;
$_SESSION['otp_attempts'] = 0;

// Redirect to reset password page
header('Location: ../auth/reset_password.php');
exit;
?>
