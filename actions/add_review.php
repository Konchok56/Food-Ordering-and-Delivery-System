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
    $rating = (int)$_POST['rating'];
    $comment = sanitize($_POST['comment'] ?? '');

    // Validate
    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    try {
        // Check if user already reviewed this food
        $checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE food_id = ? AND user_id = ? LIMIT 1");
        $checkStmt->execute([$food_id, $user_id]);
        if ($checkStmt->fetch()) {
            // Already reviewed, redirect back with error or just skip
            header("Location: ../food_detail.php?id=$food_id&already_reviewed=1");
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

