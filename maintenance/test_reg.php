<?php
include('core/db.php');

$name = "Test Restaurant Owner";
$email = "testowner@example.com";
$password = password_hash('password123', PASSWORD_DEFAULT);
$role = "restaurant";
$is_approved = 0;

try {
    $pdo->beginTransaction();
    
    // 1. Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_approved) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role, $is_approved]);
    $user_id = $pdo->lastInsertId();
    echo "User created with ID: $user_id\n";

    // 2. Insert restaurant
    $stmt = $pdo->prepare("INSERT INTO restaurants (name, cuisine_type, city, phone, owner_id, is_open, is_featured) VALUES (?, ?, ?, ?, ?, 0, 0)");
    $stmt->execute(["The Test Diner", "Mixed", "Kathmandu", "9876543210", $user_id]);
    echo "Restaurant created.\n";

    $pdo->commit();
    echo "Success!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
