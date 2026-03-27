<?php
session_start();

// Clear remember me cookie and database token if exists
if (isset($_COOKIE['remember_token'])) {
    // Delete the token from database
    if (isset($_SESSION['user_id'])) {
        include('../includes/db.php');
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?"); // Changed to remember_token
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // Clear the cookie
    setcookie("remember_token", "", time() - 3600, "/");
}

// Destroy session
session_destroy();

// Redirect to homepage
header("Location: /food/swiftbite_php_starter/index.php");
exit;
?>
