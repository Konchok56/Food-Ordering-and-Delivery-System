<?php

/**
 * Calculates the great-circle distance between two points in kilometers.
 */
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) return 999999;
    
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

/**
 * Automatically assigns the nearest online delivery rider to an order.
 * Currently uses the restaurant location as the source point for pick-up.
 */
function assignNearestRider($pdo, $order_id, $addressString = '') {
    try {
        // 1. Get the order's restaurant location
        $stmt = $pdo->prepare("
            SELECT r.latitude, r.longitude, r.name as rest_name 
            FROM orders o 
            JOIN restaurants r ON o.restaurant_id = r.id 
            WHERE o.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $orderInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orderInfo || !$orderInfo['latitude'] || !$orderInfo['longitude']) {
            // Fallback: search for order-specific location if geocoding was available,
            // but for now, we rely on restaurant location.
            return false;
        }
        
        $restLat = (float)$orderInfo['latitude'];
        $restLng = (float)$orderInfo['longitude'];
        
        // 2. Find all online riders (active and online, regardless of coordinates initially)
        $riderStmt = $pdo->prepare("
            SELECT id, name, phone, last_lat, last_lng 
            FROM users 
            WHERE role = 'delivery_partner' 
              AND availability_status = 'online' 
              AND status = 'active'
        ");
        $riderStmt->execute();
        $riders = $riderStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($riders)) return false;
        
        // 3. Find the nearest one with valid coordinates within 50km
        $nearestRider = null;
        $minDist = 999999;
        
        foreach ($riders as $rider) {
            if ($rider['last_lat'] !== null && $rider['last_lng'] !== null) {
                $dist = haversineDistance($restLat, $restLng, (float)$rider['last_lat'], (float)$rider['last_lng']);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $nearestRider = $rider;
                }
            }
        }
        
        $assignedRider = null;
        if ($nearestRider && $minDist < 50) { // Only assign if within 50km (sanity check)
            $assignedRider = $nearestRider;
        } else {
            // Fallback: Pick the first available online rider in the list
            $assignedRider = $riders[0];
        }
        
        if ($assignedRider) {
            // 4. Assign the rider — status set to 'assigned' so rider can accept/reject
            $assignStmt = $pdo->prepare("
                UPDATE orders
                SET assigned_rider_id = ?,
                    delivery_partner_name = ?,
                    delivery_partner_phone = ?,
                    status = 'assigned'
                WHERE id = ?
            ");
            $assignStmt->execute([
                $assignedRider['id'],
                $assignedRider['name'],
                $assignedRider['phone'],
                $order_id
            ]);

            // Notify the rider of the new assignment
            if (function_exists('addNotification')) {
                addNotification(
                    $pdo,
                    $assignedRider['id'],
                    'order_assigned',
                    'New Order Assigned!',
                    'Order #' . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ' has been assigned to you. Accept or reject it from your dashboard.',
                    '<i class="fa-solid fa-motorcycle" style="color:#ff4f00"></i>',
                    null,
                    '/food/delivery/dashboard.php'
                );
            }

            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Rider Assignment Error: " . $e->getMessage());
        return false;
    }
}
