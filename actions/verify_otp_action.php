<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');

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

// ── GDODS-43: DB-based attempt tracking (not session-based) ──
// Increment attempt counter in the database so clearing session/cookies
// does NOT reset the counter. This prevents brute-force attacks.
$pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE email = ?")
    ->execute([$email]);

$attStmt = $pdo->prepare("SELECT otp_attempts FROM users WHERE email = ?");
$attStmt->execute([$email]);
$attRow = $attStmt->fetch();
$attempts = (int)($attRow['otp_attempts'] ?? 0);

if ($attempts > 5) {
    // Too many attempts — invalidate OTP and lock out
    unset($_SESSION['otp_email'], $_SESSION['otp_attempts']);

    $pdo->prepare("UPDATE users SET reset_token = NULL, token_expiry = NULL, otp_attempts = 0 WHERE email = ?")
        ->execute([$email]);

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
    $remaining = 5 - $attempts;
    $_SESSION['otp_error'] = "Invalid OTP code. You have $remaining attempt(s) remaining.";
    header('Location: ../auth/verify_otp.php');
    exit;
}

// <i class="fa-solid fa-circle-check" style="color:#22c55e"></i> OTP verified successfully — reset counter
$pdo->prepare("UPDATE users SET otp_attempts = 0 WHERE email = ?")
    ->execute([$email]);

$_SESSION['otp_verified'] = true;
unset($_SESSION['otp_attempts']); // clean up legacy session key

// Redirect to reset password page
header('Location: ../auth/reset_password.php');
exit;
?>

