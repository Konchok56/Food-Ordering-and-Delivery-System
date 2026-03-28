<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') { die('Access denied'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: ../admin/manage_restaurants.php?error=Invalid restaurant ID");
    exit;
}

try {
    // Get image path to delete file
    $stmt = $pdo->prepare("SELECT image_path FROM restaurants WHERE id = ?");
    $stmt->execute([$id]);
    $rest = $stmt->fetch(PDO::FETCH_ASSOC);

    // Unlink foods from this restaurant
    $pdo->prepare("UPDATE foods SET restaurant_id = NULL WHERE restaurant_id = ?")->execute([$id]);

    // Delete restaurant
    $pdo->prepare("DELETE FROM restaurants WHERE id = ?")->execute([$id]);

    // Delete image file
    if ($rest && !empty($rest['image_path'])) {
        $filepath = '../' . $rest['image_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    header("Location: ../admin/manage_restaurants.php?success=Restaurant deleted successfully!");
} catch (PDOException $e) {
    header("Location: ../admin/manage_restaurants.php?error=" . urlencode($e->getMessage()));
}
exit;
?>
