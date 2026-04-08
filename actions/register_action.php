<?php
require_once '../core/bootstrap.php';

// Validate CSRF
requireCsrf();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = in_array($_POST['role'] ?? '', ['user', 'restaurant']) ? $_POST['role'] : 'user';

// Restaurant-specific fields
$rest_name    = trim($_POST['rest_name'] ?? '');
$rest_city    = trim($_POST['rest_city'] ?? 'Kathmandu');
$rest_cuisine = trim($_POST['rest_cuisine'] ?? 'Mixed');
$rest_phone   = trim($_POST['rest_phone'] ?? '');

// --- Validate common fields ---
$errors = [];
$nameError = validateName($name);
if ($nameError) $errors[] = $nameError;
if (!validateEmail($email)) $errors[] = 'Please enter a valid email address';
$pwdErrors = validatePassword($password);
if (!empty($pwdErrors)) $errors = array_merge($errors, $pwdErrors);

// --- Validate restaurant fields ---
if ($role === 'restaurant' && empty($rest_name)) {
    $errors[] = 'Please enter your restaurant name';
}

if (!empty($errors)) {
    $_SESSION['register_old'] = $_POST;
    flash('error', implode(' ', $errors));
    redirect('auth/register.php');
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['register_old'] = $_POST;
    flash('error', 'An account with this email already exists.');
    redirect('auth/register.php');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// is_approved: users are approved immediately, restaurants need admin approval
$is_approved = ($role === 'restaurant') ? 0 : 1;

try {
    $pdo->beginTransaction();

    // 1. Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_approved) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([sanitize($name), $email, $hashedPassword, $role, $is_approved]);
    $user_id = $pdo->lastInsertId();

    // 2. If restaurant, also create a restaurants row linked to this user
    if ($role === 'restaurant') {
        $pdo->prepare("
            INSERT INTO restaurants (name, cuisine_type, city, phone, owner_id, is_open, is_featured)
            VALUES (?, ?, ?, ?, ?, 0, 0)
        ")->execute([
            sanitize($rest_name),
            sanitize($rest_cuisine),
            sanitize($rest_city),
            sanitize($rest_phone),
            $user_id
        ]);
    }

    $pdo->commit();

    if ($role === 'restaurant') {
        flash('success', 'Restaurant account created! Please wait for admin approval before logging in.');
    } else {
        flash('success', 'Account created successfully! Please log in.');
    }
    
    redirect('auth/login.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Registration failed. Please try again.');
    redirect('auth/register.php');
}
