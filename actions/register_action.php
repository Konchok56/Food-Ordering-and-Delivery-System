<?php
require_once '../core/bootstrap.php';

requireCsrf();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = in_array($_POST['role'] ?? '', ['user', 'restaurant', 'delivery_partner']) ? $_POST['role'] : 'user';

// Restaurant fields
$rest_name    = trim($_POST['rest_name'] ?? '');
$rest_city    = trim($_POST['rest_city'] ?? 'Kathmandu');
$rest_cuisine = trim($_POST['rest_cuisine'] ?? 'Mixed');
$rest_phone   = trim($_POST['rest_phone'] ?? '');

// Rider fields
$rider_phone   = trim($_POST['rider_phone']   ?? '');
$rider_vehicle = trim($_POST['rider_vehicle']  ?? 'Motorcycle');
$rider_address = trim($_POST['rider_address']  ?? '');

// --- Validate common fields ---
$errors = [];
$nameError = validateName($name);
if ($nameError) $errors[] = $nameError;
if (!validateEmail($email)) $errors[] = 'Please enter a valid email address.';
$pwdErrors = validatePassword($password);
if (!empty($pwdErrors)) $errors = array_merge($errors, $pwdErrors);

// --- Validate restaurant fields ---
if ($role === 'restaurant' && empty($rest_name)) {
    $errors[] = 'Please enter your restaurant name.';
}

// --- Validate rider fields ---
if ($role === 'delivery_partner') {
    if (empty($rider_phone)) $errors[] = 'Please enter your phone number.';
    // Photo is optional — nice to have but won't block registration
}

if (!empty($errors)) {
    $_SESSION['register_old'] = $_POST;
    flash('error', implode(' · ', $errors));
    redirect('auth/register.php');
}

// Check email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['register_old'] = $_POST;
    flash('error', 'An account with this email already exists.');
    redirect('auth/register.php');
}

// --- Handle rider photo upload ---
$rider_photo_path = null;
if ($role === 'delivery_partner' && !empty($_FILES['rider_photo']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/riders/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($_FILES['rider_photo']['tmp_name']);
    $size    = $_FILES['rider_photo']['size'];

    if (!in_array($mime, $allowed)) {
        flash('error', 'Photo must be a JPG, PNG, or WebP image.');
        redirect('auth/register.php');
    }
    if ($size > 2 * 1024 * 1024) {
        flash('error', 'Photo must be under 2MB.');
        redirect('auth/register.php');
    }

    $ext  = pathinfo($_FILES['rider_photo']['name'], PATHINFO_EXTENSION);
    $filename = 'rider_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['rider_photo']['tmp_name'], $uploadDir . $filename);
    $rider_photo_path = 'uploads/riders/' . $filename;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// is_approved: only regular users are auto-approved
$is_approved = ($role === 'user') ? 1 : 0;

try {
    $pdo->beginTransaction();

    // Insert user (without profile_photo — handled separately for safety)
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, is_approved, phone, address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        sanitize($name),
        $email,
        $hashedPassword,
        $role,
        $is_approved,
        $role === 'delivery_partner' ? sanitize($rider_phone) : null,
        $role === 'delivery_partner' ? sanitize($rider_address) : null,
    ]);
    $user_id = $pdo->lastInsertId();

    // Save profile photo path if uploaded (column may not exist yet — silently skips)
    if ($rider_photo_path && $user_id) {
        try {
            $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?")->execute([$rider_photo_path, $user_id]);
        } catch (Exception $photoEx) {
            // profile_photo column not yet added — run the migration SQL
            error_log('SwiftBite: profile_photo column missing. Run: ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL;');
        }
    }

    // If restaurant, create restaurants row
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

    // If rider, save extra info (vehicle type) — store in a meta or address field
    if ($role === 'delivery_partner') {
        $pdo->prepare("UPDATE users SET city = ? WHERE id = ?")->execute([sanitize($rider_vehicle), $user_id]);
    }

    $pdo->commit();

    $messages = [
        'user'             => 'Account created successfully! Please log in.',
        'restaurant'       => 'Restaurant account created! Please wait for admin approval before logging in.',
        'delivery_partner' => 'Rider application submitted! An admin will review and approve your account shortly.',
    ];
    flash('success', $messages[$role]);
    redirect('auth/login.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errMsg = (defined('APP_ENV') && APP_ENV === 'development')
        ? 'DB Error: ' . $e->getMessage()
        : 'Registration failed. Please try again.';
    flash('error', $errMsg);
    redirect('auth/register.php');
}
