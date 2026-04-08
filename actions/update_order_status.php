<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');
include('../core/validation.php');

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
    header('Location: ../admin/delivery_partner.php');
    exit;
}

$order_id = (int) ($_POST['order_id'] ?? 0);
$status = trim((string) ($_POST['status'] ?? ''));
$deliveryPartnerName = sanitize($_POST['delivery_partner_name'] ?? '');
$deliveryPartnerPhone = sanitize($_POST['delivery_partner_phone'] ?? '');

$allowedStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
if ($order_id <= 0 || !in_array($status, $allowedStatuses, true)) {
    $_SESSION['delivery_error'] = 'Invalid order or status.';
    header('Location: ../admin/delivery_partner.php');
    exit;
}

$phoneError = validatePhone($deliveryPartnerPhone);
if ($phoneError) {
    $_SESSION['delivery_error'] = $phoneError;
    header('Location: ../admin/delivery_partner.php');
    exit;
}

try {
    $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? LIMIT 1");
    $checkStmt->execute([$order_id]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['delivery_error'] = 'Order not found.';
        header('Location: ../admin/delivery_partner.php');
        exit;
    }

    $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, delivery_partner_name = ?, delivery_partner_phone = ? WHERE id = ?");
    $updateStmt->execute([$status, $deliveryPartnerName, $deliveryPartnerPhone, $order_id]);

    $_SESSION['delivery_success'] = 'Order status updated successfully.';
} catch (Exception $e) {
    $_SESSION['delivery_error'] = 'Failed to update order status.';
}

header('Location: ../admin/delivery_partner.php');
exit;

