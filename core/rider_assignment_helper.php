<?php
/**
 * Helper to automatically assign the nearest available rider to an order.
 */

function assignNearestRider($pdo, $orderId, $customerAddress) {
    // 1. Get the order details (we need the restaurant location to start from)
    $stmt = $pdo->prepare("
        SELECT o.id, r.lat AS rest_lat, r.lng AS rest_lng 
        FROM orders o 
        JOIN restaurants r ON o.restaurant_id = r.id 
        WHERE o.id = ? 
        LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || !$order['rest_lat'] || !$order['rest_lng']) {
        return false;
    }

    $restLat = (float)$order['rest_lat'];
    $restLng = (float)$order['rest_lng'];

    // 2. Find nearest available rider (online and has last_lat/lng)
    // Using Haversine formula (approx) in SQL
    $riderStmt = $pdo->prepare("
        SELECT id, name, phone, last_lat, last_lng,
               (6371 * acos(cos(radians(?)) * cos(radians(last_lat)) * cos(radians(last_lng) - radians(?)) + sin(radians(?)) * sin(radians(last_lat)))) AS distance
        FROM users 
        WHERE role = 'delivery_partner' 
          AND availability_status = 'online' 
          AND last_lat IS NOT NULL 
          AND last_lng IS NOT NULL
        ORDER BY distance ASC 
        LIMIT 1
    ");
    $riderStmt->execute([$restLat, $restLng, $restLat]);
    $rider = $riderStmt->fetch(PDO::FETCH_ASSOC);

    if ($rider) {
        // 3. Assign rider to order
        $update = $pdo->prepare("
            UPDATE orders 
            SET delivery_partner_name = ?, 
                delivery_partner_phone = ?, 
                delivery_lat = ?, 
                delivery_lng = ?,
                status = 'confirmed' 
            WHERE id = ?
        ");
        $update->execute([
            $rider['name'], 
            $rider['phone'], 
            $rider['last_lat'], 
            $rider['last_lng'], 
            $orderId
        ]);
        
        // Notify rider? (In a full app, you'd add a notification here)
        return true;
    }

    return false;
}
