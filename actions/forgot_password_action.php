<?php
session_start();
include('../includes/db.php');
include('../includes/csrf.php');

// Validate CSRF
requireCsrf();

$email = trim($_POST['email'] ?? '');
$is_resend = isset($_POST['resend']);

if (empty($email)) {
    $_SESSION['fp_error'] = 'Please enter your email address.';
    header('Location: ../auth/forgot_password.php');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['fp_error'] = 'Please enter a valid email address.';
    header('Location: ../auth/forgot_password.php');
    exit;
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['fp_error'] = 'No account found with that email address.';
    header('Location: ../auth/forgot_password.php');
    exit;
}

// Rate limiting: Check if OTP was sent recently (within 60 seconds)
if (!$is_resend) {
    // Since we set expiry to 10 mins, if token_expiry is > NOW + 9 mins, it was sent < 60s ago
    $stmt = $pdo->prepare("SELECT token_expiry FROM users WHERE email = ? AND token_expiry > (NOW() + INTERVAL 9 MINUTE)");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $_SESSION['fp_error'] = 'Please wait 60 seconds before requesting another code.';
        header('Location: ../auth/forgot_password.php');
        exit;
    }
}

// Generate 6-digit OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Hash the OTP for storage (security best practice)
$otp_hash = password_hash($otp, PASSWORD_DEFAULT);

// Store OTP with 10-minute expiry
$stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = NOW() + INTERVAL 10 MINUTE WHERE email = ?");
$stmt->execute([$otp_hash, $email]);

// Store email in session for step 2
$_SESSION['otp_email'] = $email;
$_SESSION['otp_attempts'] = 0;

// --- 📧 SEND REAL EMAIL ---
include_once('../includes/mailer_helper.php');
$subject = "Your SwiftBite Verification Code: $otp";
$body = "
    <div style='background: #fff8f0; padding: 30px; font-family: sans-serif; border-radius: 12px;'>
        <h2 style='color: #ff4f00; font-family: Syne, sans-serif;'>Hi, " . htmlspecialchars(explode(' ', $user['name'])[0]) . "!</h2>
        <p style='color: #3d2600; font-size: 1.1rem;'>Use the code below to reset your SwiftBite password. This code will expire in 10 minutes.</p>
        <div style='background: #1a1004; color: #ff4f00; padding: 20px; text-align: center; font-size: 2.5rem; font-weight: bold; border-radius: 12px; letter-spacing: 5px; margin: 20px 0;'>
            $otp
        </div>
        <p style='color: #8b6a44; font-size: 0.85rem;'>If you didn't request a password reset, please ignore this email.</p>
    </div>
";

// We try to send it, but we won't stop the flow if it fails (since user hasn't put credentials yet)
sendSwiftBiteEmail($email, $subject, $body);

// For localhost development/fallback (Optional: removed display)

if ($is_resend) {
    $_SESSION['otp_success'] = 'A new OTP has been sent!';
}

// Redirect to OTP verification page
header('Location: ../auth/verify_otp.php');
exit;
?>