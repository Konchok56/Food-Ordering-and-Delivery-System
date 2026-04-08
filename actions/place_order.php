<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');
include('../core/validation.php');

// Validate CSRF
requireCsrf();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get inputs
$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$city = sanitize($_POST['city'] ?? 'Kathmandu');
$notes = sanitize($_POST['notes'] ?? '');
$payment_method = sanitize($_POST['payment_method'] ?? 'cod');

// Note: In a real app, do stronger validation here (e.g. phone format, empty fields)

// 1. Fetch Cart
$stmt = $pdo->prepare("
    SELECT c.*, 
           f.image_path AS food_image, 
           f.emoji AS food_emoji,
           f.id AS fid,
           f.restaurant_id AS food_restaurant_id
    FROM cart c 
    LEFT JOIN foods f ON c.food_id = f.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart)) {
    header("Location: ../orders/cart.php");
    exit;
}

// Detect restaurant_id from cart items
$order_restaurant_id = null;
foreach ($cart as $item) {
    if (!empty($item['food_restaurant_id'])) {
        $order_restaurant_id = (int)$item['food_restaurant_id'];
        break;
    }
}

// 2. Calculate Totals
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$deliveryFee = $subtotal > 0 ? 50 : 0;
$total = $subtotal + $deliveryFee;

$promo_code = sanitize($_POST['promo_code'] ?? '');
if (empty($promo_code)) {
    $promo_code = null;
}

$discountAmount = 0;

if ($promo_code !== null) {
    $stmtPromo = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
    $stmtPromo->execute([strtoupper($promo_code)]);
    $promo = $stmtPromo->fetch(PDO::FETCH_ASSOC);
    if ($promo && (empty($promo['expiry_date']) || strtotime($promo['expiry_date']) >= time())) {
        if ($promo['type'] === 'percent') {
            $discountAmount = ($promo['value'] / 100) * $subtotal;
        } else {
            $discountAmount = $promo['value'];
        }
        if ($discountAmount > $subtotal) $discountAmount = $subtotal;
        $total -= $discountAmount;
    } else {
        $promo_code = null;
    }
}

try {
    // 3. Begin Transaction
    $pdo->beginTransaction();

    // 4. Insert Order
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (user_id, customer_name, customer_email, customer_phone, delivery_address, delivery_city, payment_method, subtotal, delivery_fee, discount_amount, promo_code, total, status, restaurant_id, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    $stmt->execute([
        $user_id, $name, $email, $phone, $address, $city, $payment_method, 
        $subtotal, $deliveryFee, $discountAmount, $promo_code, $total, $order_restaurant_id, $notes
    ]);
    
    $order_id = $pdo->lastInsertId();

    // 5. Insert Order Items
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items 
        (order_id, food_id, food_name, price, quantity, subtotal, image_path, emoji) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {
        $imgPath = !empty($item['food_image']) ? $item['food_image'] : ($item['image_path'] ?? '');
        $emojiIcon = !empty($item['food_emoji']) ? $item['food_emoji'] : ($item['emoji'] ?? '🍔');
        $itemSubtotal = $item['price'] * $item['quantity'];

        $itemStmt->execute([
            $order_id, 
            $item['fid'] ?? null, 
            $item['food_name'], 
            $item['price'], 
            $item['quantity'], 
            $itemSubtotal, 
            $imgPath, 
            $emojiIcon
        ]);
    }

    // 6. Clear Cart
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);

    // 7. Update User Profile if empty
    $userStmt = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($userRow['phone']) || empty($userRow['address'])) {
        $pdo->prepare("UPDATE users SET phone = ?, address = ?, city = ? WHERE id = ?")->execute([$phone, $address, $city, $user_id]);
    }

    // Commit
    $pdo->commit();

    // Redirect to confirmation or Payment Gateway
    if ($payment_method === 'esewa') {
        header("Location: esewa_request.php?order_id=" . $order_id);
    } else {
        header("Location: ../orders/order_confirmation.php?id=" . $order_id);
    }
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    // Log error in real app
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Error placing order: " . htmlspecialchars($e->getMessage()) . "</h2><p><a href='../orders/cart.php'>Back to Cart</a></p>");
}

