<?php
/**
 * Database Indexes + Orders Table Migration
 * Run ONCE: http://localhost/food/swiftbite_php_starter/migrate_indexes.php
 * Then delete this file.
 */
include('core/db.php');
$results = [];

// Helper to safely add an index
function addIndex($pdo, $table, $indexName, $columns, &$results) {
    try {
        $check = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'")->fetchAll();
        if (empty($check)) {
            $pdo->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($columns)");
            $results[] = "✅ Added index <code>$indexName</code> on <code>$table($columns)</code>";
        } else {
            $results[] = "ℹ️ Index <code>$indexName</code> already exists on <code>$table</code>";
        }
    } catch (Exception $e) {
        $results[] = "⚠️ Index <code>$indexName</code>: " . $e->getMessage();
    }
}

// Helper to safely add a column
function addColumn($pdo, $table, $colName, $definition, &$results) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$colName'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$colName` $definition");
            $results[] = "✅ Added column <code>$colName</code> to <code>$table</code>";
        } else {
            $results[] = "ℹ️ Column <code>$colName</code> already exists in <code>$table</code>";
        }
    } catch (Exception $e) {
        $results[] = "⚠️ Column <code>$colName</code>: " . $e->getMessage();
    }
}

$results[] = "<h3>📊 Users Table</h3>";
addIndex($pdo, 'users', 'idx_users_email', 'email', $results);
addIndex($pdo, 'users', 'idx_users_remember', 'remember_token', $results);
addIndex($pdo, 'users', 'idx_users_role', 'role', $results);

// Add phone and address columns to users for profile/checkout
addColumn($pdo, 'users', 'phone', "VARCHAR(20) DEFAULT '' AFTER email", $results);
addColumn($pdo, 'users', 'address', "VARCHAR(500) DEFAULT '' AFTER phone", $results);
addColumn($pdo, 'users', 'city', "VARCHAR(100) DEFAULT 'Kathmandu' AFTER address", $results);
addColumn($pdo, 'users', 'avatar', "VARCHAR(500) DEFAULT '' AFTER city", $results);
addColumn($pdo, 'users', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP", $results);

$results[] = "<h3>🍔 Foods Table</h3>";
addIndex($pdo, 'foods', 'idx_foods_name', 'name', $results);
addIndex($pdo, 'foods', 'idx_foods_category', 'category', $results);
addIndex($pdo, 'foods', 'idx_foods_restaurant', 'restaurant_id', $results);
addIndex($pdo, 'foods', 'idx_foods_featured', 'is_featured', $results);
addIndex($pdo, 'foods', 'idx_foods_price', 'price', $results);

$results[] = "<h3>🏪 Restaurants Table</h3>";
addIndex($pdo, 'restaurants', 'idx_rest_cuisine', 'cuisine_type', $results);
addIndex($pdo, 'restaurants', 'idx_rest_city', 'city', $results);
addIndex($pdo, 'restaurants', 'idx_rest_featured', 'is_featured', $results);
addIndex($pdo, 'restaurants', 'idx_rest_rating', 'rating', $results);

$results[] = "<h3>🛒 Orders Table (New)</h3>";

// Create orders table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `customer_name` VARCHAR(255) NOT NULL,
        `customer_email` VARCHAR(255) NOT NULL,
        `customer_phone` VARCHAR(20) NOT NULL,
        `delivery_address` VARCHAR(500) NOT NULL,
        `delivery_city` VARCHAR(100) NOT NULL DEFAULT 'Kathmandu',
        `payment_method` ENUM('cod','esewa','khalti') NOT NULL DEFAULT 'cod',
        `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
        `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `status` ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_orders_user` (`user_id`),
        INDEX `idx_orders_status` (`status`),
        INDEX `idx_orders_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "✅ Created <code>orders</code> table";
} catch (Exception $e) {
    $results[] = "ℹ️ Orders table: " . $e->getMessage();
}

// Create order_items table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `food_id` INT,
        `food_name` VARCHAR(255) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `subtotal` DECIMAL(10,2) NOT NULL,
        `image_path` VARCHAR(500) DEFAULT '',
        `emoji` VARCHAR(50) DEFAULT '🍔',
        INDEX `idx_oitems_order` (`order_id`),
        INDEX `idx_oitems_food` (`food_id`),
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "✅ Created <code>order_items</code> table";
} catch (Exception $e) {
    $results[] = "ℹ️ Order items table: " . $e->getMessage();
}

// Output
echo "<!DOCTYPE html><html><head><title>DB Migration</title><style>
    body { font-family: 'Segoe UI', sans-serif; max-width: 700px; margin: 60px auto; padding: 20px; background: #fff8f0; }
    h1 { color: #ff4f00; } h3 { color: #1a1004; margin-top: 24px; }
    li { padding: 6px 0; font-size: 0.95rem; list-style: none; }
    code { background: #fff0dc; padding: 2px 8px; border-radius: 6px; font-size: 0.88em; }
    .done { margin-top: 32px; padding: 16px; background: #e8f5e9; border-radius: 12px; color: #2e7d32; font-weight: 600; }
</style></head><body>";
echo "<h1>🔧 Database Migration</h1><ul>";
foreach ($results as $r) echo "<li>$r</li>";
echo "</ul>";
echo "<div class='done'>✅ Migration complete! You can delete this file now.</div>";
echo "</body></html>";
