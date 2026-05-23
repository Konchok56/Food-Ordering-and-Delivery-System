<?php
session_start();
include('../core/config.php');
include('../core/db.php');
include('../core/csrf.php');
include('../core/validation.php');
include('../core/notification_helper.php');
include_once('../core/mailer_helper.php');


error_reporting(E_ALL);
ini_set('display_errors', 1);


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

// 0. Check User Status
$stmtStatus = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmtStatus->execute([$user_id]);
$userStatusRow = $stmtStatus->fetch(PDO::FETCH_ASSOC);

if ($userStatusRow && ($userStatusRow['status'] ?? 'active') === 'inactive') {
    flash('error', 'You need to be active to order. Please update your status in your profile.');
    header("Location: ../orders/checkout.php");
    exit;
}

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
        $order_restaurant_id = (int) $item['food_restaurant_id'];
        break;
    }
}

// 2. Calculate Totals — 🔒 re-verify prices from the foods table (defense in depth)
$subtotal = 0;
foreach ($cart as &$item) {
    if (!empty($item['food_id'])) {
        $priceStmt = $pdo->prepare("SELECT price FROM foods WHERE id = ? LIMIT 1");
        $priceStmt->execute([$item['food_id']]);
        $dbPrice = $priceStmt->fetchColumn();
        if ($dbPrice !== false) {
            $item['price'] = (float) $dbPrice;
        }
    }
    $subtotal += $item['price'] * $item['quantity'];
}
unset($item); // break reference
$deliveryFee = $subtotal > 0 ? 50 : 0;
$total = $subtotal + $deliveryFee;

$promo_code = sanitize($_POST['promo_code'] ?? '');
if (empty($promo_code)) {
    $promo_code = null;
}

$discountAmount = 0;
$promo = null; // keep reference for usage tracking

if ($promo_code !== null) {
    $stmtPromo = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
    $stmtPromo->execute([strtoupper($promo_code)]);
    $promo = $stmtPromo->fetch(PDO::FETCH_ASSOC);
    if ($promo && (empty($promo['expiry_date']) || strtotime($promo['expiry_date']) >= time())) {
        // ── GDODS-40: Check global usage limit ──
        if ($promo['usage_limit'] !== null && (int)$promo['usage_count'] >= (int)$promo['usage_limit']) {
            $promo = null;
            $promo_code = null;
        }
        // ── GDODS-40: Check per-user usage ──
        if ($promo) {
            $usChk = $pdo->prepare("SELECT id FROM promo_usage WHERE promo_code_id = ? AND user_id = ?");
            $usChk->execute([$promo['id'], $user_id]);
            if ($usChk->fetch()) {
                $promo = null;
                $promo_code = null;
            }
        }
        if ($promo) {
            if ($promo['type'] === 'percent') {
                $discountAmount = ($promo['value'] / 100) * $subtotal;
            } else {
                $discountAmount = $promo['value'];
            }
            if ($discountAmount > $subtotal)
                $discountAmount = $subtotal;
            $total -= $discountAmount;
        }
    } else {
        $promo = null;
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
        $user_id,
        $name,
        $email,
        $phone,
        $address,
        $city,
        $payment_method,
        $subtotal,
        $deliveryFee,
        $discountAmount,
        $promo_code,
        $total,
        $order_restaurant_id,
        $notes
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
        $emojiIcon = !empty($item['food_emoji']) ? $item['food_emoji'] : ($item['emoji'] ?? '<i class="fa-solid fa-burger"></i>');
        $itemSubtotal = $item['price'] * $item['quantity'];

        $itemStmt->execute([
            $order_id,
            $item['food_id'] ?? null,
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

    // ── GDODS-40: Record promo usage and increment counter ──
    if ($promo && $promo_code !== null) {
        $pdo->prepare("INSERT INTO promo_usage (promo_code_id, user_id, order_id) VALUES (?, ?, ?)")
            ->execute([$promo['id'], $user_id, $order_id]);
        $pdo->prepare("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE id = ?")
            ->execute([$promo['id']]);
    }

    // Commit
    $pdo->commit();

    // ── GDODS-48: Auto-assign nearest available rider ──
    try {
        include_once('../core/rider_assignment_helper.php');
        assignNearestRider($pdo, $order_id, $address);
    } catch (Exception $e) {
        // Non-critical — order still goes through if no rider available
    }

    // Create notification
    $firstImage = null;
    foreach ($cart as $ci) {
        if (!empty($ci['food_image'])) {
            $firstImage = $ci['food_image'];
            break;
        }
        if (!empty($ci['image_path'])) {
            $firstImage = $ci['image_path'];
            break;
        }
    }
    $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    addNotification(
        $pdo,
        $user_id,
        'order_placed',
        'Order Placed Successfully!',
        'Your order ' . $orderLabel . ' has been placed. Total: Rs. ' . number_format($total, 2) . '. We\'ll start preparing it soon!',
        '🎉',
        $firstImage,
        SITE_BASE_URL . '/orders/order_confirmation.php?id=' . $order_id
    );

    // --- <i class="fa-solid fa-envelope"></i> Send Order Confirmation Email ---
    if (!empty($email)) {
        sendOrderPlacedEmail(
            $email,
            $name,
            $order_id,
            $cart,
            $subtotal,
            $deliveryFee,
            $discountAmount,
            $total,
            $payment_method,
            $address . ', ' . $city
        );
    }

    // Redirect to confirmation or Payment Gateway
    if ($payment_method === 'esewa') {
        header("Location: esewa_request.php?order_id=" . $order_id);
    } else {
        header("Location: ../orders/order_confirmation.php?id=" . $order_id);
    }
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Error placing order: " . htmlspecialchars($e->getMessage()) . "</h2><p><a href='../orders/cart.php'>Back to Cart</a></p>");
}

