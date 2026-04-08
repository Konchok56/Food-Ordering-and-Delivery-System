<?php
session_start();
include('../includes/db.php');
include('../includes/csrf.php');
include('../includes/validation.php');

// Validate CSRF
requireCsrf();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get and sanitize inputs
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$city = sanitize($_POST['city'] ?? 'Kathmandu');

$errors = [];

// Validate name
$nameError = validateName($name);
if ($nameError) $errors[] = $nameError;

// Validate phone
$phoneError = validatePhone($phone);
if ($phoneError) $errors[] = $phoneError;

if (!empty($errors)) {
    $_SESSION['profile_error'] = implode('. ', $errors);
    header("Location: ../user/profile.php");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $address, $city, $user_id]);
    
    // Update session name if changed
    $_SESSION['user_name'] = $name;
    
    $_SESSION['profile_success'] = 'Profile updated successfully!';
} catch (Exception $e) {
    $_SESSION['profile_error'] = 'Failed to update profile. Please try again.';
}

header("Location: ../user/profile.php");
exit;
