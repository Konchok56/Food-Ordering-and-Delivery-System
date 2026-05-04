<?php
session_start();
include('../core/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // Fetch order status and location
    $stmt = $pdo->prepare("SELECT id, status, delivery_lat, delivery_lng, location_updated_at, delivery_partner_name FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'status' => $order['status'],
        'lat' => $order['delivery_lat'],
        'lng' => $order['delivery_lng'],
        'updated_at' => $order['location_updated_at'],
        'rider_name' => $order['delivery_partner_name']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
