<?php
/**
 * Migration: Add Restaurant Owner Role support.
 * Run ONCE: http://localhost/food/swiftbite_php_starter/migrate_restaurant_role.php
 */
include('core/db.php');
$messages = [];

try {
    // 0. Update users.role enum
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'restaurant') DEFAULT 'user'");
    $messages[] = "✅ Updated 'users.role' ENUM to include 'restaurant'.";

    // 1. Add is_approved to users
    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_approved'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_approved TINYINT(1) NOT NULL DEFAULT 1");
        $messages[] = "✅ Added 'is_approved' to 'users' table.";
    } else {
        $messages[] = "ℹ️ 'is_approved' already exists in 'users'.";
    }

    // 2. Add owner_id to restaurants
    $cols = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'owner_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE restaurants ADD COLUMN owner_id INT DEFAULT NULL");
        try {
            $pdo->exec("ALTER TABLE restaurants ADD CONSTRAINT fk_restaurant_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL");
        } catch (PDOException $e) { /* FK may already exist */ }
        $messages[] = "✅ Added 'owner_id' to 'restaurants' table.";
    } else {
        $messages[] = "ℹ️ 'owner_id' already exists in 'restaurants'.";
    }

    // 3. Add restaurant_id to orders
    $cols2 = $pdo->query("SHOW COLUMNS FROM orders LIKE 'restaurant_id'")->fetchAll();
    if (empty($cols2)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN restaurant_id INT DEFAULT NULL");
        try {
            $pdo->exec("ALTER TABLE orders ADD CONSTRAINT fk_order_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL");
        } catch (PDOException $e) { /* FK may already exist */ }
        $messages[] = "✅ Added 'restaurant_id' to 'orders' table.";
    } else {
        $messages[] = "ℹ️ 'restaurant_id' already exists in 'orders'.";
    }

    $messages[] = "🎉 Migration complete! Next steps:<br>
        &nbsp;&nbsp;1. Go to <a href='admin/manage_restaurants.php'>Admin → Manage Restaurants</a> to approve pending restaurants.<br>
        &nbsp;&nbsp;2. Restaurant owners can now register at <a href='auth/register.php'>Register</a>.";

} catch (PDOException $e) {
    $messages[] = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head><title>Migration — Restaurant Role — SwiftBite</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #fff8f0; padding: 60px; }
    .box { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 36px; box-shadow: 0 8px 40px rgba(255,79,0,0.1); }
    h1 { color: #1a1004; font-size: 1.6rem; margin-bottom: 20px; }
    .msg { padding: 14px 18px; border-radius: 12px; margin-bottom: 12px; font-size: 0.93rem; background: #f0fff4; border: 1px solid #c6f6d5; line-height: 1.8; }
    .msg.err { background: #fff5f5; border-color: #fed7d7; }
    a { color: #ff4f00; font-weight: 700; }
    .btn { display: inline-block; margin-top: 20px; padding: 12px 28px; background: #ff4f00; color: #fff; border-radius: 999px; font-weight: 700; text-decoration: none; }
</style>
</head><body>
<div class="box">
    <h1>🔑 Restaurant Role Migration</h1>
    <?php foreach ($messages as $msg): ?>
        <div class="msg <?php echo strpos($msg, '❌') !== false ? 'err' : ''; ?>"><?php echo $msg; ?></div>
    <?php endforeach; ?>
    <a class="btn" href="owner/dashboard.php">→ Restaurant Dashboard</a>
</div>
</body></html>
