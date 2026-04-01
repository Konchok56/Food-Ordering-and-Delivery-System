<?php
/**
 * Cart Helper — DB-based cart count
 * Replaces the broken $_SESSION['cart'] counting approach.
 */

function getCartCount($pdo, $userId) {
    if (!$userId) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
