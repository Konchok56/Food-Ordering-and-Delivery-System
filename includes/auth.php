<?php
// Safe session start - prevents duplicate session_start() warning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /food/swiftbite_php_starter/auth/login.php");
    exit;
}
?>