<?php
session_start();
include('../includes/db.php');

$email = $_POST['email'];
$password = $_POST['password'];

// Get user
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user && password_verify($password, $user['password'])){
    
    // ✅ Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];

    // ✅ Remember me functionality
    if (isset($_POST['remember'])) {
        $token = bin2hex(random_bytes(50));
        setcookie("remember_token", $token, time() + (86400 * 30), "/"); // 30 days
        $stmt = $pdo->prepare("UPDATE users SET remember_token=? WHERE id=?"); // Changed to remember_token
        $stmt->execute([$token, $user['id']]);
    }

    // ✅ Redirect to homepage
    header("Location: ../index.php");
    exit;

} else {
    echo "Invalid email or password!";
}
?>