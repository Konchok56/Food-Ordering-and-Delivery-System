<?php
/**
 * Delivery tracking migration
 * Run once: http://localhost/your-project-folder/migrate_delivery_features.php
 */
session_start();
include('core/db.php');

$results = [];

function addColumnSafe(PDO $pdo, string $table, string $column, string $definition, array &$results): void {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $results[] = "ℹ️ Column <code>{$column}</code> already exists in <code>{$table}</code>";
            return;
        }

        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` {$definition}");
        $results[] = "✅ Added column <code>{$column}</code> to <code>{$table}</code>";
    } catch (Exception $e) {
        $results[] = "⚠️ Failed adding <code>{$column}</code>: " . htmlspecialchars($e->getMessage());
    }
}

function addIndexSafe(PDO $pdo, string $table, string $indexName, string $columns, array &$results): void {
    try {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
        $stmt->execute([$indexName]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $results[] = "ℹ️ Index <code>{$indexName}</code> already exists on <code>{$table}</code>";
            return;
        }

        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ({$columns})");
        $results[] = "✅ Added index <code>{$indexName}</code> on <code>{$table}</code>";
    } catch (Exception $e) {
        $results[] = "⚠️ Failed adding index <code>{$indexName}</code>: " . htmlspecialchars($e->getMessage());
    }
}

$results[] = '<h3>🛵 Delivery Tracking Columns</h3>';
addColumnSafe($pdo, 'orders', 'delivery_partner_name', "VARCHAR(120) DEFAULT '' AFTER status", $results);
addColumnSafe($pdo, 'orders', 'delivery_partner_phone', "VARCHAR(20) DEFAULT '' AFTER delivery_partner_name", $results);
addColumnSafe($pdo, 'orders', 'delivery_lat', "DECIMAL(10,7) NULL DEFAULT NULL AFTER delivery_partner_phone", $results);
addColumnSafe($pdo, 'orders', 'delivery_lng', "DECIMAL(10,7) NULL DEFAULT NULL AFTER delivery_lat", $results);
addColumnSafe($pdo, 'orders', 'location_updated_at', "DATETIME NULL DEFAULT NULL AFTER delivery_lng", $results);

$results[] = '<h3>📌 Helpful Indexes</h3>';
addIndexSafe($pdo, 'orders', 'idx_orders_partner', '`delivery_partner_name`', $results);
addIndexSafe($pdo, 'orders', 'idx_orders_location_time', '`location_updated_at`', $results);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Features Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 820px; margin: 60px auto; padding: 24px; background: #fff8f0; color: #1a1004; }
        h1 { color: #ff4f00; }
        h3 { margin-top: 24px; }
        ul { padding-left: 0; }
        li { list-style: none; padding: 8px 0; }
        code { background: #fff0dc; padding: 2px 8px; border-radius: 6px; }
        .done { margin-top: 28px; padding: 16px 18px; border-radius: 12px; background: #eaf8ea; color: #1f6b2b; font-weight: 700; }
    </style>
</head>
<body>
    <h1>Delivery Features Migration</h1>
    <ul>
        <?php foreach ($results as $result): ?>
            <li><?php echo $result; ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="done">✅ Done. Run this once only, then you can delete this file.</div>
</body>
</html>
