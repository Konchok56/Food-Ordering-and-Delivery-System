<?php
/**
 * AJAX endpoint: returns unread notification count as JSON.
 */
session_start();
include('../core/db.php');
include('../core/notification_helper.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$count = getUnreadNotificationCount($pdo, $userId);

// Fetch latest unread notifications to push as toasts in frontend
$stmt = $pdo->prepare("
    SELECT id, type, title, message, icon, image_path, link, created_at 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'count' => $count,
    'notifications' => $notifications
]);
