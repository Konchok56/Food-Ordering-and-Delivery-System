<?php
require_once '../core/bootstrap.php';

// 🔒 Check login
if(!isset($_SESSION['user_id'])){
    // AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => SITE_BASE_URL . '/auth/login.php']);
        exit;
    }
    header("Location: ../auth/login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$food_id   = isset($_POST['food_id']) ? (int) $_POST['food_id'] : null;
$food_name = $_POST['food_name'] ?? '';
$quantity  = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
$redirect  = isset($_POST['redirect']) ? $_POST['redirect'] : '../orders/cart.php';

// 🔒 Always fetch price + details from the database — never trust frontend price
$image_path = '';
$emoji = '<i class="fa-solid fa-burger"></i>';
$price = 0;
$foodRow = null;

if ($food_id) {
    $foodStmt = $pdo->prepare("SELECT id, name, price, image_path, emoji FROM foods WHERE id = ?");
    $foodStmt->execute([$food_id]);
    $foodRow = $foodStmt->fetch(PDO::FETCH_ASSOC);
} elseif ($food_name) {
    // Fallback: lookup by name
    $foodStmt = $pdo->prepare("SELECT id, name, price, image_path, emoji FROM foods WHERE name = ? LIMIT 1");
    $foodStmt->execute([$food_name]);
    $foodRow = $foodStmt->fetch(PDO::FETCH_ASSOC);
}

// Reject if item doesn't exist in the database
if (!$foodRow) {
    $isAjaxCheck = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($isAjaxCheck) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Food item not found.']);
        exit;
    }
    flash('error', 'Food item not found.');
    header("Location: ../menu.php");
    exit;
}

// Use DB-sourced values only
$food_id    = (int) $foodRow['id'];
$food_name  = $foodRow['name'];
$price      = (float) $foodRow['price'];
$image_path = $foodRow['image_path'] ?? '';
$emoji      = $foodRow['emoji'] ?? '<i class="fa-solid fa-burger"></i>';

// Check if already in cart
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND food_id = ?");
$stmt->execute([$user_id, $food_id]);

if($stmt->rowCount() > 0){
    // Increase quantity
    $pdo->prepare("UPDATE cart SET quantity = quantity + ?, price = ? WHERE user_id = ? AND food_id = ?")
        ->execute([$quantity, $price, $user_id, $food_id]);
} else {
    // Insert new — all values sourced from DB
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

