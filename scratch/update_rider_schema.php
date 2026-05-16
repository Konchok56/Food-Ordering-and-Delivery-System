<?php
include(__DIR__ . '/../core/db.php');
try {
    // 1. Add delivery_partner_id
    $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_partner_id INT AFTER user_id");
    echo "Added delivery_partner_id to orders table.\n";
    
    // 2. Update status enum
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','assigned','preparing','picked_up','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    echo "Updated status ENUM to include 'assigned'.\n";
    
    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
