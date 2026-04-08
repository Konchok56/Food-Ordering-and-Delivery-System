<?php
session_start();
include('../core/db.php');
include('../core/cart_helper.php');

// 🔒 Check login
if(!isset($_SESSION['user_id'])){
    // AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => '/food/swiftbite_php_starter/auth/login.php']);
        exit;
    }
    header("Location: ../auth/login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$food_id   = isset($_POST['food_id']) ? (int) $_POST['food_id'] : null;
$food_name = $_POST['food_name'] ?? '';
$price     = isset($_POST['price']) ? (float) $_POST['price'] : 0;
$quantity  = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
$redirect  = isset($_POST['redirect']) ? $_POST['redirect'] : '../orders/cart.php';

// Lookup food details for image_path and emoji if food_id provided
$image_path = '';
$emoji = '🍔';
if ($food_id) {
    $foodStmt = $pdo->prepare("SELECT name, image_path, emoji FROM foods WHERE id = ?");
    $foodStmt->execute([$food_id]);
    $foodRow = $foodStmt->fetch(PDO::FETCH_ASSOC);
    if ($foodRow) {
        if (empty($food_name)) $food_name = $foodRow['name'];
        $image_path = $foodRow['image_path'] ?? '';
        $emoji = $foodRow['emoji'] ?? '🍔';
    }
} elseif ($food_name) {
    // Fallback: lookup by name
    $foodStmt = $pdo->prepare("SELECT id, image_path, emoji FROM foods WHERE name = ? LIMIT 1");
    $foodStmt->execute([$food_name]);
    $foodRow = $foodStmt->fetch(PDO::FETCH_ASSOC);
    if ($foodRow) {
        $food_id = (int) $foodRow['id'];
        $image_path = $foodRow['image_path'] ?? '';
        $emoji = $foodRow['emoji'] ?? '🍔';
    }
}

// Check if already in cart (by food_id or food_name)
if ($food_id) {
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND food_id = ?");
    $stmt->execute([$user_id, $food_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND food_name = ?");
    $stmt->execute([$user_id, $food_name]);
}

if($stmt->rowCount() > 0){
    // Increase quantity
    if ($food_id) {
        $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND food_id = ?")
            ->execute([$quantity, $user_id, $food_id]);
    } else {
        $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND food_name = ?")
            ->execute([$quantity, $user_id, $food_name]);
    }
} else {
    // Insert new — include food_id, image_path, emoji
    $pdo->prepare("INSERT INTO cart (user_id, food_id, food_name, image_path, emoji, price, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$user_id, $food_id, $food_name, $image_path, $emoji, $price, $quantity]);
}

// Get updated cart count
$cartCount = getCartCount($pdo, $user_id);

// Detect AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$wantsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

if ($isAjax || $wantsJson) {
    header('Content-Type: application/json');
    echo json_encode([
        'success'    => true,
        'message'    => htmlspecialchars($food_name) . ' added to cart!',
        'cart_count'  => $cartCount,
        'food_name'  => $food_name,
    ]);
    exit;
}

// Fallback: redirect for non-AJAX
if (strpos($redirect, '../') === 0) {
    header("Location: $redirect");
} else {
    header("Location: ../$redirect");
}
exit;

