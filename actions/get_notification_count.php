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

$count = getUnreadNotificationCount($pdo, (int)$_SESSION['user_id']);
echo json_encode(['count' => $count]);
