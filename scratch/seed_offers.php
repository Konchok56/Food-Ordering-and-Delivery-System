<?php
require_once 'core/db.php';

try {
    $pdo->beginTransaction();

    // 1. Seed SWIFT40 promo code
    $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = 'SWIFT40'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO promo_codes (code, type, value, is_active) VALUES ('SWIFT40', 'percent', 40.00, 1)");
        $insert->execute();
        echo "Promo code SWIFT40 seeded.\n";
    }

    // 2. Seed YUMMY20 promo code
    $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = 'YUMMY20'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO promo_codes (code, type, value, is_active) VALUES ('YUMMY20', 'flat', 20.00, 1)");
        $insert->execute();
        echo "Promo code YUMMY20 seeded.\n";
    }

    // 3. Clear existing offers and seed
    $pdo->exec("DELETE FROM offers");
    
    $insertOffer = $pdo->prepare("INSERT INTO offers (title, description, promo_code, is_active) VALUES (?, ?, ?, 1)");
    
    $insertOffer->execute([
        "Get 40% Off Your First Order",
        "Use code SWIFT40 at checkout. Applicable for first-time orders only. Maximum discount limit applies.",
        "SWIFT40"
    ]);

    $insertOffer->execute([
        "Flat Rs. 20 Off on Burgers",
        "Use code YUMMY20 at checkout. valid on all orders containing burger items. Limited time deal!",
        "YUMMY20"
    ]);

    $pdo->commit();
    echo "Offers seeded successfully!\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error seeding: " . $e->getMessage() . "\n";
}
