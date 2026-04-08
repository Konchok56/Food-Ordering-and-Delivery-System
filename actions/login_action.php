<?php
require_once '../includes/bootstrap.php';

// Validate CSRF
requireCsrf();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

// Validate inputs
if (!validateEmail($email)) {
    flash('error', 'Please enter a valid email address.');
    redirect('auth/login.php');
}

if (empty($password)) {
    flash('error', 'Password is required.');
    redirect('auth/login.php');
}

// Get user
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Check user and password
if (!$user || !password_verify($password, $user['password'])) {
    flash('error', 'Invalid email or password.');
    redirect('auth/login.php');
}

// Block unapproved restaurant accounts
if ($user['role'] === 'restaurant' && (isset($user['is_approved']) && $user['is_approved'] == 0)) {
    flash('warning', 'Your restaurant account is pending admin approval.');
    redirect('auth/login.php');
}

// Login successful: Regenerate session ID
session_regenerate_id(true);

// Create session
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['role']      = $user['role'];

// Handle Remember Me
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (86400 * REMEMBER_ME_DAYS);
    setcookie('remember_token', $token, $expiry, '/', '', false, true);

    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->execute([$token, $user['id']]);
}

// Redirect based on role
switch ($user['role']) {
    case 'admin':
        redirect('admin/dashboard.php');
        break;
    case 'restaurant':
        redirect('owner/dashboard.php');
        break;
    default:
        redirect('index.php');
}
