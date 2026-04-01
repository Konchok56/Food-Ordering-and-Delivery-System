<?php
session_start();
include('../includes/db.php');
include('../includes/csrf.php');
include('../includes/validation.php');

// Validate CSRF
requireCsrf();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
$errors = [];

$nameError = validateName($name);
if ($nameError) $errors[] = $nameError;

if (!validateEmail($email)) $errors[] = 'Please enter a valid email address';

$pwdErrors = validatePassword($password);
if (!empty($pwdErrors)) $errors = array_merge($errors, $pwdErrors);

if (!empty($errors)) {
    $_SESSION['register_error'] = implode('. ', $errors);
    $_SESSION['register_old'] = ['name' => $name, 'email' => $email];
    header("Location: ../auth/register.php");
    exit;
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $_SESSION['register_error'] = 'An account with this email already exists';
    $_SESSION['register_old'] = ['name' => $name, 'email' => $email];
    header("Location: ../auth/register.php");
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([sanitize($name), $email, $hashedPassword]);

$_SESSION['login_success'] = 'Account created successfully! Please log in.';
header("Location: ../auth/login.php");
exit;
