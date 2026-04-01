<?php
session_start();
header('Content-Type: application/json');

include('../includes/db.php');
include('../includes/cart_helper.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$count = getCartCount($pdo, $_SESSION['user_id']);
echo json_encode(['success' => true, 'count' => $count]);
