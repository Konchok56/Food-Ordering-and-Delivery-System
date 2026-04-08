<?php
session_start();
include('../core/db.php');
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

echo json_encode(['success' => true, 'new_status' => $newStatus]);

