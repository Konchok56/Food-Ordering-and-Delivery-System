<?php
/**
 * SwiftBite — Rider Assignment Helper
 * Handles automatic assignment of delivery riders to orders.
 */

/**
 * Assigns the nearest available rider to an order.
 * Currently simulates "nearest" by picking an online rider with the fewest active tasks.
 * 
 * @param PDO    $pdo
 * @param int    $order_id
 * @param string $customer_address
 * @return bool  True if a rider was assigned, false otherwise.
 */
function assignNearestRider(PDO $pdo, int $order_id, string $customer_address): bool {
    // 1. Find all online and approved delivery partners
    $stmt = $pdo->prepare("
        SELECT id, name, phone, last_lat, last_lng 
        FROM users 
        WHERE role = 'delivery_partner' 
          AND availability_status = 'online' 
          AND status = 'active' 
          AND is_approved = 1 
          AND is_banned = 0
    ");
    $stmt->execute();
    $riders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($riders)) {
        return false;
    }

    // 2. Select the best rider. 
    // Ideally we would use geocoding on $customer_address and compare with rider's last_lat/lng.
    // Since we don't have a geocoding API integrated here, we'll pick the rider 
    // with the fewest currently active orders.
    
    $bestRider = null;
    $minOrders = PHP_INT_MAX;

    foreach ($riders as $rider) {
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE assigned_rider_id = ? 
              AND status IN ('confirmed', 'preparing', 'out_for_delivery')
        ");
        $checkStmt->execute([$rider['id']]);
        $count = (int)$checkStmt->fetchColumn();

        if ($count < $minOrders) {
            $minOrders = $count;
            $bestRider = $rider;
        }
        
        // If we find someone with 0 orders, they are a great candidate, stop searching
        if ($count === 0) break;
    }

    if ($bestRider) {
        // 3. Update the order with rider details
        $updateStmt = $pdo->prepare("
            UPDATE orders 
            SET assigned_rider_id = ?, 
                delivery_partner_name = ?, 
                delivery_partner_phone = ?,
                status = 'confirmed' 
            WHERE id = ?
        ");
        $updateStmt->execute([
            $bestRider['id'],
            $bestRider['name'],
            $bestRider['phone'],
            $order_id
        ]);

        // 4. Notify the rider
        if (function_exists('addNotification')) {
            $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
            addNotification(
                $pdo,
                $bestRider['id'],
                'new_order',
                'New Delivery Assigned! 🚴',
                "You have been assigned a new delivery task: Order $orderLabel. Head to the restaurant to pick it up!",
                '📦',
                null,
                SITE_BASE_URL . '/delivery/dashboard.php'
            );
        }

        return true;
    }

    return false;
}
