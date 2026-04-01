<?php
session_start();
include('../includes/db.php');

// Admin check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user['role'] !== 'admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/manage_foods.php");
    exit;
}

// Collect form data
$food_id       = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
$name          = trim($_POST['name'] ?? '');
$category      = trim($_POST['category'] ?? '');
$description   = trim($_POST['description'] ?? '');
$price         = floatval($_POST['price'] ?? 0);
$delivery_time = trim($_POST['delivery_time'] ?? '');
$rating        = floatval($_POST['rating'] ?? 4.5);
$badge         = trim($_POST['badge'] ?? '');
$emoji         = trim($_POST['emoji'] ?? '🍽️');
$is_featured   = isset($_POST['is_featured']) ? 1 : 0;
$is_favorite   = isset($_POST['is_favorite']) ? 1 : 0;
$restaurant_id = !empty($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : null;

// Validation
if (empty($name) || empty($category) || empty($description) || $price <= 0 || empty($delivery_time)) {
    header("Location: ../admin/manage_foods.php?error=Please fill in all required fields");
    exit;
}

if ($rating < 0 || $rating > 5) {
    header("Location: ../admin/manage_foods.php?error=Rating must be between 0 and 5");
    exit;
}

if (empty($badge)) {
    $badge = null;
}

// Handle image upload
$image_path = null;
$uploadDir = __DIR__ . '/../uploads/foods/';

if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['food_image'];

    // Validate file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        header("Location: ../admin/manage_foods.php?error=Image must be less than 2MB");
        exit;
    }

    // Validate file type
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        header("Location: ../admin/manage_foods.php?error=Only JPG, PNG and WebP images are allowed");
        exit;
    }

    // Generate unique filename
    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg'
    };
    $filename = 'food_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . $filename;

    // Create dir if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $image_path = 'uploads/foods/' . $filename;

        // If editing, delete old image
        if ($food_id > 0) {
            $oldStmt = $pdo->prepare("SELECT image_path FROM foods WHERE id = ?");
            $oldStmt->execute([$food_id]);
            $oldImage = $oldStmt->fetchColumn();
            if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                unlink(__DIR__ . '/../' . $oldImage);
            }
        }
    } else {
        header("Location: ../admin/manage_foods.php?error=Failed to upload image. Please try again.");
        exit;
    }
}

try {
    if ($food_id > 0) {
        // UPDATE existing food
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE foods SET name=?, category=?, description=?, price=?, delivery_time=?,
                rating=?, badge=?, emoji=?, image_path=?, is_featured=?, is_favorite=?, restaurant_id=? WHERE id=?");
            $stmt->execute([$name, $category, $description, $price, $delivery_time,
                $rating, $badge, $emoji, $image_path, $is_featured, $is_favorite, $restaurant_id, $food_id]);
        } else {
            // Update without changing image
            $stmt = $pdo->prepare("UPDATE foods SET name=?, category=?, description=?, price=?, delivery_time=?,
                rating=?, badge=?, emoji=?, is_featured=?, is_favorite=?, restaurant_id=? WHERE id=?");
            $stmt->execute([$name, $category, $description, $price, $delivery_time,
                $rating, $badge, $emoji, $is_featured, $is_favorite, $restaurant_id, $food_id]);
        }
        header("Location: ../admin/manage_foods.php?success=Food item updated successfully!");
    } else {
        // INSERT new food
        $stmt = $pdo->prepare("INSERT INTO foods (name, category, description, price, delivery_time, rating, badge, emoji, image_path, is_featured, is_favorite, restaurant_id)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $description, $price, $delivery_time,
            $rating, $badge, $emoji, $image_path, $is_featured, $is_favorite, $restaurant_id]);
        header("Location: ../admin/manage_foods.php?success=Food item added successfully!");
    }
} catch (PDOException $e) {
    header("Location: ../admin/manage_foods.php?error=Database error: " . urlencode($e->getMessage()));
}
exit;
