<?php
// core/recommendation_helper.php

function getRecommendations($pdo, $user_id, $limit = 4) {
    // 1. Get the categories of foods the user has ordered most
    $stmt = $pdo->prepare("
        SELECT f.category, COUNT(oi.id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN foods f ON oi.food_id = f.id
        WHERE o.user_id = ?
        GROUP BY f.category
        ORDER BY order_count DESC
        LIMIT 2
    ");
    $stmt->execute([$user_id]);
    $topCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Get all food IDs the user has already ordered
    $stmtIds = $pdo->prepare("
        SELECT DISTINCT food_id 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND food_id IS NOT NULL
    ");
    $stmtIds->execute([$user_id]);
    $orderedFoodIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

    $recommendations = [];

    // 3. If they have top categories, find highly rated new items from those categories
    if (!empty($topCategories)) {
        $placeholders = implode(',', array_fill(0, count($topCategories), '?'));
        
        $sql = "SELECT * FROM foods WHERE category IN ($placeholders)";
        $params = $topCategories;

        if (!empty($orderedFoodIds)) {
            $idPlaceholders = implode(',', array_fill(0, count($orderedFoodIds), '?'));
            $sql .= " AND id NOT IN ($idPlaceholders)";
            $params = array_merge($params, $orderedFoodIds);
        }

        $sql .= " ORDER BY rating DESC, is_featured DESC LIMIT ?";
        $params[] = $limit;

        $stmtRecs = $pdo->prepare($sql);
        
        // Bind parameters carefully because of LIMIT
        foreach ($params as $key => $val) {
            if ($key === count($params) - 1) {
                $stmtRecs->bindValue($key + 1, (int)$val, PDO::PARAM_INT);
            } else {
                $stmtRecs->bindValue($key + 1, $val);
            }
        }
        $stmtRecs->execute();
        $recommendations = $stmtRecs->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Fallback if recommendations are not enough
    if (count($recommendations) < $limit) {
        $needed = $limit - count($recommendations);
        $excludeIds = array_merge($orderedFoodIds, array_column($recommendations, 'id'));
        
        $sql = "SELECT * FROM foods";
        $params = [];
        if (!empty($excludeIds)) {
            $idPlaceholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql .= " WHERE id NOT IN ($idPlaceholders)";
            $params = $excludeIds;
        }
        $sql .= " ORDER BY is_featured DESC, rating DESC LIMIT ?";
        $params[] = $needed;

        $stmtFallback = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key === count($params) - 1) {
                $stmtFallback->bindValue($key + 1, (int)$val, PDO::PARAM_INT);
            } else {
                $stmtFallback->bindValue($key + 1, $val);
            }
        }
        $stmtFallback->execute();
        $fallbacks = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);

        $recommendations = array_merge($recommendations, $fallbacks);
    }

    return $recommendations;
}
