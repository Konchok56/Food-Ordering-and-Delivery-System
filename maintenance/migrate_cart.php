<?php
/**
 * Cart Table Migration
 * Run this ONCE via browser: http://localhost/food/swiftbite_php_starter/migrate_orders/cart.php
 * Then delete this file.
 */
include('core/db.php');

$results = [];

// 1. Add food_id column if it doesn't exist
try {
    $cols = $pdo->query("SHOW COLUMNS FROM cart LIKE 'food_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE cart ADD COLUMN food_id INT NULL AFTER user_id");
        $results[] = "✅ Added food_id column to cart table";
    } else {
        $results[] = "ℹ️ food_id column already exists";
    }
} catch (Exception $e) {
    $results[] = "❌ food_id column: " . $e->getMessage();
}

// 2. Add image_path column if it doesn't exist
try {
    $cols = $pdo->query("SHOW COLUMNS FROM cart LIKE 'image_path'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE cart ADD COLUMN image_path VARCHAR(500) NULL AFTER food_name");
        $results[] = "✅ Added image_path column to cart table";
    } else {
        $results[] = "ℹ️ image_path column already exists";
    }
} catch (Exception $e) {
    $results[] = "❌ image_path column: " . $e->getMessage();
}

// 3. Add emoji column if it doesn't exist
try {
    $cols = $pdo->query("SHOW COLUMNS FROM cart LIKE 'emoji'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE cart ADD COLUMN emoji VARCHAR(50) DEFAULT '🍔' AFTER image_path");
        $results[] = "✅ Added emoji column to cart table";
    } else {
        $results[] = "ℹ️ emoji column already exists";
    }
} catch (Exception $e) {
    $results[] = "❌ emoji column: " . $e->getMessage();
}

// 4. Add indexes
try {
    $indexes = $pdo->query("SHOW INDEX FROM cart WHERE Key_name = 'idx_cart_user'")->fetchAll();
    if (empty($indexes)) {
        $pdo->exec("ALTER TABLE cart ADD INDEX idx_cart_user (user_id)");
        $results[] = "✅ Added user_id index";
    } else {
        $results[] = "ℹ️ user_id index already exists";
    }
} catch (Exception $e) {
    $results[] = "ℹ️ Index: " . $e->getMessage();
}

try {
    $indexes = $pdo->query("SHOW INDEX FROM cart WHERE Key_name = 'idx_cart_food'")->fetchAll();
    if (empty($indexes)) {
        $pdo->exec("ALTER TABLE cart ADD INDEX idx_cart_food (food_id)");
        $results[] = "✅ Added food_id index";
    } else {
        $results[] = "ℹ️ food_id index already exists";
    }
} catch (Exception $e) {
    $results[] = "ℹ️ Index: " . $e->getMessage();
}

// 5. Backfill food_id for existing cart items
try {
    $stmt = $pdo->query("SELECT c.id, c.food_name, f.id as fid FROM cart c LEFT JOIN foods f ON c.food_name = f.name WHERE c.food_id IS NULL");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    foreach ($rows as $row) {
        if ($row['fid']) {
            $pdo->prepare("UPDATE cart SET food_id = ? WHERE id = ?")->execute([$row['fid'], $row['id']]);
            $updated++;
        }
    }
    $results[] = "✅ Backfilled food_id for $updated existing cart items";
} catch (Exception $e) {
    $results[] = "ℹ️ Backfill: " . $e->getMessage();
}

// Output results
echo "<html><head><title>Cart Migration</title><style>
    body { font-family: 'Segoe UI', sans-serif; max-width: 600px; margin: 60px auto; padding: 20px; }
    h1 { color: #ff4f00; }
    li { padding: 8px 0; font-size: 1.1rem; }
</style></head><body>";
echo "<h1>🛒 Cart Table Migration</h1><ul>";
foreach ($results as $r) {
    echo "<li>$r</li>";
}
echo "</ul>";
echo "<p style='margin-top:24px; color:#888;'>✅ Migration complete! You can delete this file now.</p>";
echo "</body></html>";
