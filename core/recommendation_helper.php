<?php
// core/recommendation_helper.php

function getRecommendations($pdo, $user_id, $limit = 4)
{
    // 1. Get the IDs of the foods the user has already ordered
    $stmt = $pdo->prepare("
        SELECT DISTINCT oi.food_id
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = :user_id
        AND oi.food_id IS NOT NULL
    ");
    $stmt->execute(['user_id' => $user_id]);
    $ordered_food_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Get the categories of foods the user has ordered most
    $stmt = $pdo->prepare("
        SELECT f.category, COUNT(oi.id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN foods f ON oi.food_id = f.id
        WHERE o.user_id = :user_id
        GROUP BY f.category
        ORDER BY order_count DESC
        LIMIT 2
    ");
    $stmt->execute(['user_id' => $user_id]);
    $top_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $recommendations = [];

    // 3. Try to find top-rated foods in favorite categories that the user hasn't tried
    if (!empty($top_categories)) {
        $placeholders = implode(',', array_fill(0, count($top_categories), '?'));
        $query = "SELECT * FROM foods WHERE category IN ($placeholders)";
        $params = $top_categories;

        if (!empty($ordered_food_ids)) {
            $id_placeholders = implode(',', array_fill(0, count($ordered_food_ids), '?'));
            $query .= " AND id NOT IN ($id_placeholders)";
            $params = array_merge($params, $ordered_food_ids);
        }

        $query .= " ORDER BY rating DESC, id DESC LIMIT " . (int) $limit;

        $stmt = $pdo->prepare($query);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->execute();
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Fill with other top-rated foods if we don't have enough recommendations
    if (count($recommendations) < $limit) {
        $needed = $limit - count($recommendations);
        $exclude_ids = $ordered_food_ids;
        foreach ($recommendations as $rec) {
            $exclude_ids[] = $rec['id'];
        }

        $query = "SELECT * FROM foods";
        $params = [];

        if (!empty($exclude_ids)) {
            $id_placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
            $query .= " WHERE id NOT IN ($id_placeholders)";
            $params = $exclude_ids;
        }

        $query .= " ORDER BY rating DESC, id DESC LIMIT " . (int) $needed;

        $stmt = $pdo->prepare($query);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->execute();
        $fallback_recs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $recommendations = array_merge($recommendations, $fallback_recs);
    }

    // 5. Fallback: If STILL empty, just return the top-rated foods overall
    if (empty($recommendations)) {
        $stmt = $pdo->query("SELECT * FROM foods ORDER BY rating DESC, id DESC LIMIT " . (int) $limit);
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $recommendations;
}
