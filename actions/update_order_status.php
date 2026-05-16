<?php
session_start();
include('../core/config.php');
include('../core/db.php');
include('../core/csrf.php');
include('../core/validation.php');
include('../core/notification_helper.php');
include_once('../core/mailer_helper.php');

requireCsrf();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$currentRole = (string) $roleStmt->fetchColumn();

if (!in_array($currentRole, ['admin', 'delivery_partner'], true)) {
    $_SESSION['delivery_error'] = 'Access denied.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/dashboard.php'));
    exit;
}

$order_id = (int) ($_POST['order_id'] ?? 0);
$status = trim((string) ($_POST['status'] ?? ''));
$deliveryPartnerName = sanitize($_POST['delivery_partner_name'] ?? '');
$deliveryPartnerPhone = sanitize($_POST['delivery_partner_phone'] ?? '');

$allowedStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
if ($order_id <= 0 || !in_array($status, $allowedStatuses, true)) {
    $_SESSION['delivery_error'] = 'Invalid order or status.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/dashboard.php'));
    exit;
}

$phoneError = validatePhone($deliveryPartnerPhone);
if ($phoneError) {
    $_SESSION['delivery_error'] = $phoneError;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/dashboard.php'));
    exit;
}

try {
    $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? LIMIT 1");
    $checkStmt->execute([$order_id]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['delivery_error'] = 'Order not found.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/dashboard.php'));
        exit;
    }

    $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, delivery_partner_name = ?, delivery_partner_phone = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$status, $deliveryPartnerName, $deliveryPartnerPhone, $order_id]);

    // Notify the customer about their order status change
    $orderOwner = $pdo->prepare("SELECT user_id FROM orders WHERE id = ? LIMIT 1");
    $orderOwner->execute([$order_id]);
    $ownerRow = $orderOwner->fetch(PDO::FETCH_ASSOC);
    if ($ownerRow) {
        $statusMessages = [
            'confirmed'        => ['👍', 'Order Confirmed!', 'Your order has been confirmed and will be prepared shortly.'],
            'preparing'        => ['🧑‍🍳', 'Preparing Your Food', 'The kitchen is now preparing your order. Hang tight!'],
            'out_for_delivery' => ['<i class="fa-solid fa-motorcycle"></i>', 'Out for Delivery!', 'Your order is on its way! Delivery partner: ' . $deliveryPartnerName . '.'],
            'delivered'        => ['<i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i>', 'Order Delivered!', 'Your order has been delivered. Enjoy your meal!'],
            'cancelled'        => ['<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>', 'Order Cancelled', 'Your order has been cancelled by the admin.'],
        ];
        $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
        // Get first image from order items
        $oImgStmt = $pdo->prepare("SELECT image_path FROM order_items WHERE order_id = ? AND image_path IS NOT NULL AND image_path != '' LIMIT 1");
        $oImgStmt->execute([$order_id]);
        $oImg = $oImgStmt->fetchColumn() ?: null;

        if (isset($statusMessages[$status])) {
            $sm = $statusMessages[$status];
            $notifType = ($status === 'delivered') ? 'order_delivered' : (($status === 'cancelled') ? 'order_cancelled' : 'order_status');
            addNotification(
                $pdo, (int)$ownerRow['user_id'], $notifType,
                $sm[1] . ' ' . $orderLabel,
                $sm[2],
                $sm[0],
                $oImg,
                SITE_BASE_URL . '/user/order_details.php?id=' . $order_id
            );

            /* 
            // --- <i class="fa-solid fa-envelope"></i> Send Order Status Email ---
            $custEmailStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ? LIMIT 1");
            $custEmailStmt->execute([(int)$ownerRow['user_id']]);
            $custEmailRow = $custEmailStmt->fetch(PDO::FETCH_ASSOC);
            if ($custEmailRow && !empty($custEmailRow['email'])) {
                sendOrderStatusEmail(
                    $custEmailRow['email'],
                    $custEmailRow['name'],
                    $order_id,
                    $status,
                    $deliveryPartnerName
                );
            }
            */
        }
    }

    $_SESSION['delivery_success'] = 'Order status updated successfully.';
} catch (Exception $e) {
    $_SESSION['delivery_error'] = 'Failed to update order status: ' . (APP_ENV === 'development' ? $e->getMessage() : '');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/dashboard.php'));
exit;

