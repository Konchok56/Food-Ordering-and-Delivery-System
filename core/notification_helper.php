<?php
/**
 * SwiftBite — Notification Helper
 * Functions to create, fetch, and manage user notifications.
 */

/**
 * Create a notification for a user.
 *
 * @param PDO    $pdo
 * @param int    $userId
 * @param string $type       e.g. 'order_placed', 'order_cancelled', 'order_delivered', 'password_changed', 'profile_updated'
 * @param string $title      Short heading
 * @param string $message    Longer description
 * @param string $icon       Emoji icon
 * @param string|null $imagePath  Optional food/order image path
 * @param string|null $link       Optional link to navigate to
 */
function addNotification(PDO $pdo, int $userId, string $type, string $title, string $message, string $icon = '<i class="fa-solid fa-bell"></i>', ?string $imagePath = null, ?string $link = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, icon, image_path, link, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$userId, $type, $title, $message, $icon, $imagePath, $link]);
}

/**
 * Get unread notification count for a user.
 */
function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get all notifications for a user (most recent first).
 */
function getNotifications(PDO $pdo, int $userId, int $limit = 50): array {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mark all notifications as read for a user.
 */
function markAllNotificationsRead(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
}

/**
 * Mark a single notification as read.
 */
function markNotificationRead(PDO $pdo, int $notificationId, int $userId): void {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
}
