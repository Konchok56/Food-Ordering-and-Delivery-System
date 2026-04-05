<?php
session_start();
include('../includes/db.php');
include('../includes/csrf.php');

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
$latRaw = trim((string) ($_POST['delivery_lat'] ?? ''));
$lngRaw = trim((string) ($_POST['delivery_lng'] ?? ''));

if ($order_id <= 0 || $latRaw === '' || $lngRaw === '') {
    $_SESSION['delivery_error'] = 'Latitude and longitude are required.';
    header('Location: ../admin/delivery_partner.php');
    exit;
}

if (!is_numeric($latRaw) || !is_numeric($lngRaw)) {
    $_SESSION['delivery_error'] = 'Latitude and longitude must be numeric values.';
    header('Location: ../admin/delivery_partner.php');
    exit;
}

$lat = (float) $latRaw;
$lng = (float) $lngRaw;

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    $_SESSION['delivery_error'] = 'Invalid map coordinates.';
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

    $updateStmt = $pdo->prepare("UPDATE orders SET delivery_lat = ?, delivery_lng = ?, location_updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$lat, $lng, $order_id]);

    $_SESSION['delivery_success'] = 'Delivery location updated successfully.';
} catch (Exception $e) {
    $_SESSION['delivery_error'] = 'Failed to update delivery location.';
}

header('Location: ../admin/delivery_partner.php');
exit;
