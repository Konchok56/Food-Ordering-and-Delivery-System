<?php
include('core/db.php');

// Check which columns exist in orders table
$stmt = $pdo->query("DESCRIBE orders");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Existing columns:\n";
print_r($columns);

// Add missing columns if they don't exist
$needed = [
    'delivery_partner_name'  => "ALTER TABLE orders ADD COLUMN delivery_partner_name VARCHAR(150) DEFAULT NULL",
    'delivery_partner_phone' => "ALTER TABLE orders ADD COLUMN delivery_partner_phone VARCHAR(30) DEFAULT NULL",
    'delivery_lat'           => "ALTER TABLE orders ADD COLUMN delivery_lat DECIMAL(10,7) DEFAULT NULL",
    'delivery_lng'           => "ALTER TABLE orders ADD COLUMN delivery_lng DECIMAL(10,7) DEFAULT NULL",
    'location_updated_at'    => "ALTER TABLE orders ADD COLUMN location_updated_at DATETIME DEFAULT NULL",
];

foreach ($needed as $col => $sql) {
    if (!in_array($col, $columns)) {
        $pdo->exec($sql);
        echo "✅ Added column: $col\n";
    } else {
        echo "✔ Column already exists: $col\n";
    }
}

echo "\nDone.\n";
