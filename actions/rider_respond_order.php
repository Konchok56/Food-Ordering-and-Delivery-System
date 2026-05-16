<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');
include('../core/notification_helper.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

// Verify role
$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$role = $roleStmt->fetchColumn();

if ($role !== 'delivery_partner') {
    echo json_encode(['success' => false, 'message' => 'Access denied']); exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$action   = $_POST['action'] ?? ''; // 'accept' or 'reject'

if ($order_id <= 0 || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']); exit;
}

try {
    // 1. Fetch order - must be assigned to THIS rider and status must be 'assigned'
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND delivery_partner_id = ? AND status = 'assigned' LIMIT 1");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or already processed']); exit;
    }

    if ($action === 'accept') {
        // Accept: Move to 'preparing' (or 'confirmed' if you prefer)
        $update = $pdo->prepare("UPDATE orders SET status = 'preparing', updated_at = NOW() WHERE id = ?");
        $update->execute([$order_id]);
        
        addNotification($pdo, $order['user_id'], 'order_accepted', 'Rider Assigned! 🛵', 'A rider has accepted your order and is heading to the restaurant.', '🛵', null, '../user/order_details.php?id='.$order_id);
        
        echo json_encode(['success' => true, 'message' => 'Order accepted!']);
    } else {
        // Reject: Clear assignment and move back to 'confirmed' for another rider
        $update = $pdo->prepare("UPDATE orders SET delivery_partner_id = NULL, delivery_partner_name = NULL, delivery_partner_phone = NULL, status = 'confirmed', updated_at = NOW() WHERE id = ?");
        $update->execute([$order_id]);
        
        // In a real app, you might trigger assignNearestRider() again here or black-list this rider for this order.
        
        echo json_encode(['success' => true, 'message' => 'Order rejected. It will be assigned to another rider.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
