<?php
/**
 * GDODS-46 — Rider Accept or Reject an Assigned Order
 * Returns JSON. Called via AJAX from delivery/dashboard.php
 */
session_start();
include('../core/config.php');
include('../core/db.php');
include('../core/csrf.php');
include('../core/notification_helper.php');

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']); exit;
}

// CSRF check
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
}

// Role check — only delivery partners
$roleStmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$rider = $roleStmt->fetch(PDO::FETCH_ASSOC);

if (!$rider || $rider['role'] !== 'delivery_partner') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
}

$order_id  = (int)($_POST['order_id'] ?? 0);
$action    = $_POST['action'] ?? ''; // 'accept' or 'reject'
$rider_id  = (int)$_SESSION['user_id'];

if ($order_id <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
}

try {
    // Fetch the order — must be assigned to THIS rider with status 'assigned'
    $stmt = $pdo->prepare("
        SELECT * FROM orders
        WHERE id = ? AND assigned_rider_id = ? AND status = 'assigned'
        LIMIT 1
    ");
    $stmt->execute([$order_id, $rider_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or already processed.']); exit;
    }

    $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);

    if ($action === 'accept') {
        // Accept → move to confirmed
        $pdo->prepare("UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = ?")
            ->execute([$order_id]);

        // Notify the customer
        addNotification(
            $pdo, (int)$order['user_id'], 'order_status',
            'Rider Accepted Your Order!',
            'Great news! ' . htmlspecialchars($rider['name']) . ' has accepted order ' . $orderLabel . ' and is heading to the restaurant.',
            '<i class="fa-solid fa-motorcycle" style="color:#ff4f00"></i>',
            null,
            SITE_BASE_URL . '/user/order_details.php?id=' . $order_id
        );

        echo json_encode(['success' => true, 'action' => 'accept', 'message' => 'Order accepted! Head to the restaurant.']);

    } else {
        // Reject → clear rider assignment, back to pending
        $pdo->prepare("
            UPDATE orders
            SET status = 'pending',
                assigned_rider_id = NULL,
                delivery_partner_name = NULL,
                delivery_partner_phone = NULL,
                updated_at = NOW()
            WHERE id = ?
        ")->execute([$order_id]);

        // Notify the customer that we're finding another rider
        addNotification(
            $pdo, (int)$order['user_id'], 'order_status',
            'Finding Another Rider',
            'Your order ' . $orderLabel . ' is being reassigned to another rider. Sorry for the wait!',
            '<i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i>',
            null,
            SITE_BASE_URL . '/user/order_details.php?id=' . $order_id
        );

        echo json_encode(['success' => true, 'action' => 'reject', 'message' => 'Order rejected and returned to queue.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
