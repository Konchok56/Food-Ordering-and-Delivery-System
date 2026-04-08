<?php
/**
 * Migration: Create restaurants table, alter foods table, seed sample data.
 * Run this file ONCE via browser: http://localhost/food/swiftbite_php_starter/migrate_restaurants.php
 */
include('core/db.php');

$messages = [];

try {
    // 1. Create restaurants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            cuisine_type VARCHAR(100) NOT NULL DEFAULT 'Mixed',
            address VARCHAR(500),
            city VARCHAR(100) DEFAULT 'Kathmandu',
            phone VARCHAR(20),
            rating DECIMAL(2,1) DEFAULT 4.5,
            delivery_time VARCHAR(50) DEFAULT '30-45 min',
            delivery_fee DECIMAL(10,2) DEFAULT 50.00,
            min_order DECIMAL(10,2) DEFAULT 200.00,
            image_path VARCHAR(500) DEFAULT NULL,
            logo_emoji VARCHAR(10) DEFAULT '🍴',
            is_featured TINYINT(1) DEFAULT 1,
            is_open TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = "✅ Created 'restaurants' table.";

    // 2. Add restaurant_id column to foods table (if not exists)
    $cols = $pdo->query("SHOW COLUMNS FROM foods LIKE 'restaurant_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE foods ADD COLUMN restaurant_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE foods ADD CONSTRAINT fk_food_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL");
        $messages[] = "✅ Added 'restaurant_id' column to 'foods' table.";
    } else {
        $messages[] = "ℹ️ 'restaurant_id' column already exists in 'foods' table.";
    }

    // 3. Seed sample restaurants (only if table is empty)
    $count = $pdo->query("SELECT COUNT(*) FROM restaurants")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO restaurants (name, description, cuisine_type, address, city, phone, rating, delivery_time, delivery_fee, min_order, logo_emoji, is_featured, is_open) VALUES
            ('Burger Palace', 'Home of the juiciest smash burgers in town. Premium ingredients, bold flavors, and crispy perfection in every bite.', 'Fast Food', 'Thamel, Kathmandu', 'Kathmandu', '01-4567890', 4.8, '20-30 min', 60.00, 300.00, '🍔', 1, 1),
            ('Nepali Kitchen', 'Authentic Nepali home-style cooking. From dal bhat to momos, experience the true taste of Nepal with traditional recipes.', 'Nepali', 'Patan Durbar Square, Lalitpur', 'Lalitpur', '01-5523456', 4.9, '25-35 min', 40.00, 250.00, '🥘', 1, 1),
            ('Pizza Express', 'Wood-fired pizzas with artisan dough, San Marzano tomatoes, and imported Italian cheeses. Authentic Neapolitan style.', 'Italian', 'Jhamsikhel, Lalitpur', 'Lalitpur', '01-5534567', 4.7, '30-40 min', 80.00, 400.00, '🍕', 1, 1),
            ('Dragon Wok', 'Bold Chinese and Thai flavors. Sizzling stir-fries, hand-pulled noodles, and signature dim sum prepared by expert chefs.', 'Chinese', 'New Road, Kathmandu', 'Kathmandu', '01-4234567', 4.6, '25-35 min', 50.00, 350.00, '🥡', 1, 1),
            ('Sushi Master', 'Premium Japanese cuisine featuring fresh sashimi, hand-rolled maki, and traditional ramen bowls made with imported ingredients.', 'Japanese', 'Lazimpat, Kathmandu', 'Kathmandu', '01-4456789', 4.9, '30-45 min', 100.00, 500.00, '🍣', 1, 1),
            ('Green Bowl', 'Fresh, healthy, and delicious. Organic salads, smoothie bowls, wraps, and plant-based meals for the health-conscious foodie.', 'Healthy', 'Kumaripati, Lalitpur', 'Lalitpur', '01-5545678', 4.7, '15-25 min', 30.00, 200.00, '🥗', 1, 1)
        ");
        $messages[] = "✅ Inserted 6 sample restaurants.";
    } else {
        $messages[] = "ℹ️ Restaurants table already has data ($count rows). Skipping seed.";
    }

} catch (PDOException $e) {
    $messages[] = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html><head><title>Migration — SwiftBite</title>
<style>
    body { font-family: 'DM Sans', sans-serif; background: #fff8f0; padding: 60px; }
    .box { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 36px; box-shadow: 0 8px 40px rgba(255,79,0,0.1); }
    h1 { color: #1a1004; font-size: 1.6rem; margin-bottom: 20px; }
    .msg { padding: 12px 16px; border-radius: 12px; margin-bottom: 10px; font-weight: 600; font-size: 0.92rem; background: #f0fff4; border: 1px solid #c6f6d5; }
    .msg.err { background: #fff5f5; border-color: #fed7d7; }
    a { display: inline-block; margin-top: 20px; padding: 12px 28px; background: #ff4f00; color: #fff; border-radius: 999px; font-weight: 700; text-decoration: none; }
</style>
</head><body>
<div class="box">
    <h1>🗄️ Restaurant Migration</h1>
    <?php foreach ($messages as $msg): ?>
        <div class="msg <?php echo strpos($msg, '❌') !== false ? 'err' : ''; ?>"><?php echo $msg; ?></div>
    <?php endforeach; ?>
    <a href="restaurants.php">→ Go to Restaurants</a>
</div>
</body></html>
