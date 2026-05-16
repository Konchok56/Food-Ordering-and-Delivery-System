<?php
/**
 * Legacy endpoint — redirects to the proper OTP-based password reset flow.
 * The old token-based approach was insecure (no CSRF, no session check, 
 * compared raw tokens against hashed tokens).
 */
session_start();
header('Location: ../auth/forgot_password.php');
exit;
