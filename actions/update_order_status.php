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

$allowedStatuses = ['pending', 'assigned', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
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

    // Fetch the existing order details before updating to see if a rider is being assigned
    $origOrderStmt = $pdo->prepare("SELECT delivery_partner_phone, delivery_partner_name, customer_name, total FROM orders WHERE id = ? LIMIT 1");
    $origOrderStmt->execute([$order_id]);
    $origOrder = $origOrderStmt->fetch(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, delivery_partner_name = ?, delivery_partner_phone = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$status, $deliveryPartnerName, $deliveryPartnerPhone, $order_id]);

    // Notify the rider if they are newly assigned
    if ($origOrder) {
        $riderUser = null;
        if (!empty($deliveryPartnerPhone)) {
            $riderStmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'delivery_partner' AND phone = ? LIMIT 1");
            $riderStmt->execute([$deliveryPartnerPhone]);
            $riderUser = $riderStmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$riderUser && !empty($deliveryPartnerName)) {
            $riderStmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'delivery_partner' AND name = ? LIMIT 1");
            $riderStmt->execute([$deliveryPartnerName]);
            $riderUser = $riderStmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($riderUser && ($deliveryPartnerPhone !== ($origOrder['delivery_partner_phone'] ?? '') || $deliveryPartnerName !== ($origOrder['delivery_partner_name'] ?? ''))) {
            $checkNotif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'order_assigned' AND link LIKE ?");
            $checkNotif->execute([$riderUser['id'], "%id=" . $order_id]);
            $alreadyNotified = (int)$checkNotif->fetchColumn();

            if ($alreadyNotified === 0) {
                $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
                $oImgStmt = $pdo->prepare("SELECT image_path FROM order_items WHERE order_id = ? AND image_path IS NOT NULL AND image_path != '' LIMIT 1");
                $oImgStmt->execute([$order_id]);
                $oImg = $oImgStmt->fetchColumn() ?: null;

                addNotification(
                    $pdo,
                    (int)$riderUser['id'],
                    'order_assigned',
                    'New Order Assigned! ' . $orderLabel,
                    'You have been assigned to deliver order ' . $orderLabel . ' for ' . $origOrder['customer_name'] . '. Total: Rs. ' . number_format((float)$origOrder['total'], 2) . '.',
                    '🏍️',
                    $oImg,
                    SITE_BASE_URL . '/delivery/dashboard.php'
                );
            }
        }
    }

    // Notify the customer about their order status change
    $orderOwner = $pdo->prepare("SELECT user_id FROM orders WHERE id = ? LIMIT 1");
    $orderOwner->execute([$order_id]);
    $ownerRow = $orderOwner->fetch(PDO::FETCH_ASSOC);
    if ($ownerRow) {
        $statusMessages = [
            'confirmed'        => ['👍', 'Order Confirmed!', 'Your order has been confirmed and will be prepared shortly.'],
            'preparing'        => ['🧑‍🍳', 'Preparing Your Food', 'The kitchen is now preparing your order. Hang tight!'],
            'out_for_delivery' => ['🛵', 'Out for Delivery!', 'Your order is on its way! Delivery partner: ' . $deliveryPartnerName . '.'],
            'delivered'        => ['🎉', 'Order Delivered!', 'Your order has been delivered. Enjoy your meal!'],
            'cancelled'        => ['❌', 'Order Cancelled', 'Your order has been cancelled by the admin.'],
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

            // --- 📧 Send Order Status Email ---
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
        }
    }

    $_SESSION['delivery_success'] = 'Order status updated successfully.';
} catch (Exception $e) {
    $_SESSION['delivery_error'] = 'Failed to update order status: ' . (APP_ENV === 'development' ? $e->getMessage() : '');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/dashboard.php'));
exit;

