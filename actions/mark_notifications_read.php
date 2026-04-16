<?php
/**
 * AJAX endpoint: marks all notifications as read for the logged-in user.
 */
session_start();
include('../core/db.php');
include('../core/notification_helper.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

markAllNotificationsRead($pdo, (int)$_SESSION['user_id']);
echo json_encode(['success' => true]);
