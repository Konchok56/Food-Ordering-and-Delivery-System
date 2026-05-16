<?php
session_start();
include('../core/config.php');
include('../core/db.php');
include('../core/csrf.php');
include('../core/notification_helper.php');
include_once('../core/mailer_helper.php');

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

// Validate CSRF
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

// Fetch the order — must belong to this user
$stmt = $pdo->prepare("SELECT id, status, created_at FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

// Only 'pending' orders may be cancelled by the user
if ($order['status'] !== 'pending') {
    $statusLabels = [
        'confirmed'        => 'already confirmed',
        'preparing'        => 'already being prepared',
        'out_for_delivery' => 'already out for delivery',
        'delivered'        => 'already delivered',
        'cancelled'        => 'already cancelled',
    ];
    $label = $statusLabels[$order['status']] ?? $order['status'];
    echo json_encode(['success' => false, 'message' => "This order cannot be cancelled — it is {$label}."]);
    exit;
}

// Enforce 30-minute cancellation window
define('CANCEL_WINDOW_SECONDS', 30 * 60); // 30 minutes

$orderTime = strtotime($order['created_at']);
$elapsed   = time() - $orderTime;

if ($elapsed > CANCEL_WINDOW_SECONDS) {
    echo json_encode(['success' => false, 'message' => 'The 30-minute cancellation window has expired for this order.']);
    exit;
}

// All checks passed — delete from DB inside a transaction
try {
    // Grab first food image before deleting items
    $imgStmt = $pdo->prepare("SELECT image_path FROM order_items WHERE order_id = ? AND image_path IS NOT NULL AND image_path != '' LIMIT 1");
    $imgStmt->execute([$order_id]);
    $cancelImage = $imgStmt->fetchColumn() ?: null;

    $pdo->beginTransaction();

    // Delete order items first (FK constraint)
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);

    // Delete the order itself
    $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?")->execute([$order_id, $user_id]);

    $pdo->commit();

    // Create cancellation notification
    $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    addNotification(
        $pdo, $user_id, 'order_cancelled',
        'Order Cancelled',
        'Your order ' . $orderLabel . ' has been cancelled and removed successfully.',
        '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>',
        $cancelImage,
        SITE_BASE_URL . '/user/order_history.php'
    );

    // --- <i class="fa-solid fa-envelope"></i> Send Cancellation Email ---
    $userEmailStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ? LIMIT 1");
    $userEmailStmt->execute([$user_id]);
    $userEmailRow = $userEmailStmt->fetch(PDO::FETCH_ASSOC);
    if ($userEmailRow && !empty($userEmailRow['email'])) {
        sendOrderCancelledByCustomerEmail($userEmailRow['email'], $userEmailRow['name'], $order_id);
    }

    echo json_encode(['success' => true, 'message' => 'Your order has been cancelled and removed successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
