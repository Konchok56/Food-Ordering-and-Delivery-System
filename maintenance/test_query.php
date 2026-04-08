<?php
include('core/db.php');

$pending = $pdo->query("
    SELECT u.id, u.name, u.email, u.created_at,
           r.id AS rest_id, r.name AS rest_name, r.cuisine_type, r.city, r.phone
    FROM users u
    LEFT JOIN restaurants r ON r.owner_id = u.id
    WHERE u.role = 'restaurant' AND u.is_approved = 0
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "Pending Total: " . count($pending) . "\n";
foreach ($pending as $p) {
    echo "ID: " . $p['id'] . " | Name: " . $p['name'] . " | Rest: " . ($p['rest_name'] ?? 'NULL') . "\n";
}
?>
