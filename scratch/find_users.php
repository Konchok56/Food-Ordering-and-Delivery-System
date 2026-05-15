<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT email, role FROM users WHERE role = 'user' LIMIT 3");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Users found:\n";
print_r($users);
