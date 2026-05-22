<?php
session_start();
include('../core/db.php');

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

// Admin check
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') { die('Access denied'); }

$isEdit = isset($_POST['restaurant_id']) && !empty($_POST['restaurant_id']);
$id = $isEdit ? (int)$_POST['restaurant_id'] : null;

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$cuisine_type = trim($_POST['cuisine_type'] ?? 'Mixed');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? 'Kathmandu');
$phone = trim($_POST['phone'] ?? '');
$rating = (float)($_POST['rating'] ?? 4.5);
$delivery_time = trim($_POST['delivery_time'] ?? '30-45 min');
$delivery_fee = (float)($_POST['delivery_fee'] ?? 50.00);
$min_order = (float)($_POST['min_order'] ?? 200.00);
$logo_emoji = trim($_POST['logo_emoji'] ?? '<i class="fa-solid fa-utensils"></i>');
$is_featured = isset($_POST['is_featured']) ? 1 : 0;
$is_open = isset($_POST['is_open']) ? 1 : 0;
$latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

if (empty($name) || empty($description) || empty($cuisine_type)) {
    header("Location: ../admin/manage_restaurants.php?error=Please fill all required fields");
    exit;
}

// Handle image upload
$imagePath = null;
if (!empty($_FILES['restaurant_image']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $file = $_FILES['restaurant_image'];
    
    if (!in_array($file['type'], $allowed)) {
        header("Location: ../admin/manage_restaurants.php?error=Invalid image type");
        exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        header("Location: ../admin/manage_restaurants.php?error=Image must be under 2MB");
        exit;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'rest_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = '../uploads/restaurants/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $destination = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $imagePath = 'uploads/restaurants/' . $filename;
    }
}

try {
    if ($isEdit) {
        $sql = "UPDATE restaurants SET name=?, description=?, cuisine_type=?, address=?, latitude=?, longitude=?, city=?, phone=?, rating=?, delivery_time=?, delivery_fee=?, min_order=?, logo_emoji=?, is_featured=?, is_open=?";
        $params = [$name, $description, $cuisine_type, $address, $latitude, $longitude, $city, $phone, $rating, $delivery_time, $delivery_fee, $min_order, $logo_emoji, $is_featured, $is_open];
        
        if ($imagePath) {
            $sql .= ", image_path=?";
            $params[] = $imagePath;
        }
        $sql .= " WHERE id=?";
        $params[] = $id;
        
        $pdo->prepare($sql)->execute($params);
        header("Location: ../admin/manage_restaurants.php?success=Restaurant updated successfully!");
    } else {
        $pdo->prepare("INSERT INTO restaurants (name, description, cuisine_type, address, latitude, longitude, city, phone, rating, delivery_time, delivery_fee, min_order, logo_emoji, is_featured, is_open, image_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$name, $description, $cuisine_type, $address, $latitude, $longitude, $city, $phone, $rating, $delivery_time, $delivery_fee, $min_order, $logo_emoji, $is_featured, $is_open, $imagePath]);
        header("Location: ../admin/manage_restaurants.php?success=Restaurant added successfully!");
    }
} catch (PDOException $e) {
    header("Location: ../admin/manage_restaurants.php?error=" . urlencode($e->getMessage()));
}
exit;
?>

