<?php
/**
 * AJAX-only location updater for simulation mode.
 * Returns JSON — no redirects.
 */
session_start();
include('../core/db.php');
include('../core/csrf.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

// Verify role
$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$role = (string)$roleStmt->fetchColumn();
if (!in_array($role, ['admin', 'delivery_partner'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']); exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$lat      = (float)($_POST['lat'] ?? 0);
$lng      = (float)($_POST['lng'] ?? 0);

if ($order_id <= 0 || $lat === 0.0 || $lng === 0.0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']); exit;
}

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']); exit;
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET delivery_lat = ?, delivery_lng = ?, location_updated_at = NOW() WHERE id = ?");
    $stmt->execute([$lat, $lng, $order_id]);
    echo json_encode(['success' => true, 'lat' => $lat, 'lng' => $lng]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
