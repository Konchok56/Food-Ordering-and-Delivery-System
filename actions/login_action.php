<?php
session_start();
include('../includes/db.php');
include('../includes/csrf.php');
include('../includes/validation.php');

// Validate CSRF
requireCsrf();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
$errors = [];
if (!validateEmail($email)) $errors[] = 'Invalid email address';
if (empty($password)) $errors[] = 'Password is required';

if (!empty($errors)) {
    $_SESSION['login_error'] = implode('. ', $errors);
    header("Location: ../auth/login.php");
    exit;
}

// Get user
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {

    // ✅ Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // ✅ Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    // ✅ Remember me functionality
    if (isset($_POST['remember'])) {
        $token = bin2hex(random_bytes(50));
        setcookie("remember_token", $token, time() + (86400 * 30), "/", "", false, true); // httpOnly
        $stmt = $pdo->prepare("UPDATE users SET remember_token=? WHERE id=?");
        $stmt->execute([$token, $user['id']]);
    }

    // ✅ Redirect to homepage
    header("Location: ../index.php");
    exit;

} else {
    $_SESSION['login_error'] = 'Invalid email or password';
    header("Location: ../auth/login.php");
    exit;
}