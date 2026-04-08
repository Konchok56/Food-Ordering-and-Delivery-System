<?php
session_start();
include('../core/db.php');

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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ../admin/manage_foods.php?error=Invalid food item");
    exit;
}

try {
    // Get image path before deleting
    $stmt = $pdo->prepare("SELECT image_path, name FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$food) {
        header("Location: ../admin/manage_foods.php?error=Food item not found");
        exit;
    }

    // Delete the image file if it exists
    if (!empty($food['image_path'])) {
        $imagePath = __DIR__ . '/../' . $food['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->execute([$id]);

    // Also remove from any carts
    $stmt = $pdo->prepare("DELETE FROM cart WHERE food_id = ?");
    $stmt->execute([$id]);

    header("Location: ../admin/manage_foods.php?success=" . urlencode($food['name'] . " has been deleted"));
} catch (PDOException $e) {
    header("Location: ../admin/manage_foods.php?error=Failed to delete: " . urlencode($e->getMessage()));
}
exit;

