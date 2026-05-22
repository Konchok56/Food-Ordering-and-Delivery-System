<?php
session_start();
include('../core/config.php');
include('../core/db.php');
include('../core/notification_helper.php');
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if (!in_array($role, ['restaurant', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$order_id  = (int)($_POST['order_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$allowed   = ['preparing', 'ready', 'delivered', 'cancelled'];

if (!$order_id || !in_array($newStatus, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Security: restaurant owners can only update their own restaurant's orders
if ($role === 'restaurant') {
    $restStmt = $pdo->prepare("SELECT id FROM restaurants WHERE owner_id = ? LIMIT 1");
    $restStmt->execute([$_SESSION['user_id']]);
    $restaurant_id = $restStmt->fetchColumn();

    if (!$restaurant_id) {
        echo json_encode(['success' => false, 'message' => 'No restaurant linked']);
        exit;
    }

    // Verify this order belongs to this restaurant
    $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND restaurant_id = ?");
    $checkStmt->execute([$order_id, $restaurant_id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not yours']);
        exit;
    }
}

// Update status
$pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
    ->execute([$newStatus, $order_id]);

// Send rider notification if order accepted by restaurant (preparing)
if ($newStatus === 'preparing') {
    try {
        // Fetch order details
        $infoStmt = $pdo->prepare("
            SELECT o.id, o.total, r.name AS restaurant_name, o.delivery_partner_phone, o.delivery_partner_name
            FROM orders o
            LEFT JOIN restaurants r ON r.id = o.restaurant_id
            WHERE o.id = ? LIMIT 1
        ");
        $infoStmt->execute([$order_id]);
        $orderInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);

        if ($orderInfo) {
            $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
            $riderUser = null;

            // Resolve assigned rider if present
            if (!empty($orderInfo['delivery_partner_phone'])) {
                $riderStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'delivery_partner' AND phone = ? LIMIT 1");
                $riderStmt->execute([$orderInfo['delivery_partner_phone']]);
                $riderUser = $riderStmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$riderUser && !empty($orderInfo['delivery_partner_name'])) {
                $riderStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'delivery_partner' AND name = ? LIMIT 1");
                $riderStmt->execute([$orderInfo['delivery_partner_name']]);
                $riderUser = $riderStmt->fetch(PDO::FETCH_ASSOC);
            }

            // Get first item image if available
            $oImgStmt = $pdo->prepare("SELECT image_path FROM order_items WHERE order_id = ? AND image_path IS NOT NULL AND image_path != '' LIMIT 1");
            $oImgStmt->execute([$order_id]);
            $oImg = $oImgStmt->fetchColumn() ?: null;

            if ($riderUser) {
                // Notify the specific assigned rider
                addNotification(
                    $pdo,
                    (int)$riderUser['id'],
                    'order_preparing',
                    '🧑‍🍳 Order Preparing! ' . $orderLabel,
                    'Order ' . $orderLabel . ' from ' . $orderInfo['restaurant_name'] . ' is now being prepared.',
                    '🧑‍🍳',
                    $oImg,
                    SITE_BASE_URL . '/delivery/dashboard.php'
                );
            } else {
                // Notify all online riders that a new order is accepted and available
                $onlineRidersStmt = $pdo->query("SELECT id FROM users WHERE role = 'delivery_partner' AND availability_status = 'online'");
                $onlineRiders = $onlineRidersStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($onlineRiders as $riderId) {
                    addNotification(
                        $pdo,
                        (int)$riderId,
                        'order_available',
                        '<i class="fa-solid fa-motorcycle"></i> New Delivery Available! ' . $orderLabel,
                        'Order ' . $orderLabel . ' from ' . $orderInfo['restaurant_name'] . ' is being prepared. Grab it now!',
                        '<i class="fa-solid fa-motorcycle"></i>',
                        $oImg,
                        SITE_BASE_URL . '/delivery/dashboard.php'
                    );
                }
            }
        }
    } catch (Exception $e) {
        // Silently ignore to not disrupt the main flow
    }
}

echo json_encode(['success' => true, 'new_status' => $newStatus]);

