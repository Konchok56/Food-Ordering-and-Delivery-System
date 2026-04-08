<?php
require_once '../core/bootstrap.php';

// Clear remember me cookie and database token if exists
if (isset($_COOKIE['remember_token'])) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    setcookie("remember_token", "", time() - 3600, "/");
}

// Destroy session
session_unset();
session_destroy();

// Redirect to homepage using the new helper
redirect('index.php');
