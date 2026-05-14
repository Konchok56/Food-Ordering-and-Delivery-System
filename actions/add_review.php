<?php
session_start();
include('../core/db.php');
include('../core/validation.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $food_id = (int)$_POST['food_id'];
    $rating  = (int)$_POST['rating'];
    $comment = trim($_POST['comment'] ?? '');

    // Validate
    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    try {
        // 1. Check if user has actually ordered this item and it was DELIVERED
        $orderCheck = $pdo->prepare("
            SELECT oi.id 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND oi.food_id = ? AND o.status = 'delivered'
            LIMIT 1
        ");
        $orderCheck->execute([$user_id, $food_id]);
        if (!$orderCheck->fetch()) {
            // Not delivered or not ordered
            header("Location: ../food_detail.php?id=$food_id&not_delivered=1");
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO reviews (food_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$food_id, $user_id, $rating, $comment]);

        // Calculate new average rating for the food item
        $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE food_id = ?");
        $avgStmt->execute([$food_id]);
        $avgRow = $avgStmt->fetch(PDO::FETCH_ASSOC);
        $newAvg = round($avgRow['avg_rating'], 1);

        // Update foods table
        $updStmt = $pdo->prepare("UPDATE foods SET rating = ? WHERE id = ?");
        $updStmt->execute([$newAvg, $food_id]);

        $pdo->commit();

        header("Location: ../food_detail.php?id=$food_id&review_added=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error adding review: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit;
}

