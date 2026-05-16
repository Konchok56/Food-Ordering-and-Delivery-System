<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');
include('../core/validation.php');
include('../core/notification_helper.php');

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
$status = sanitize($_POST['status'] ?? 'active');
if (!in_array($status, ['active', 'inactive'])) $status = 'active';

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
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, status = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $address, $city, $status, $user_id]);
    
    // Update session name if changed
    $_SESSION['user_name'] = $name;
    
    $_SESSION['profile_success'] = 'Profile updated successfully!';

    // Create notification
    addNotification(
        $pdo, $user_id, 'profile_updated',
        'Profile Updated',
        'Your profile information was updated on ' . date('M d, Y \a\t h:i A') . '.',
        '👤',
        null,
        '../user/profile.php'
    );
} catch (Exception $e) {
    $_SESSION['profile_error'] = 'Failed to update profile. Please try again.';
}

header("Location: ../user/profile.php");
exit;

