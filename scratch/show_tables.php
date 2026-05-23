<?php
require_once 'core/db.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in DB:\n";
foreach ($tables as $table) {
    echo " - $table\n";
    // Also describe table
    $desc = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($desc as $col) {
        echo "   * {$col['Field']} ({$col['Type']}) - Null: {$col['Null']}, Key: {$col['Key']}\n";
    }
}
