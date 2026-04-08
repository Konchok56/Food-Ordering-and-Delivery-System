<?php
include('core/db.php');
$rest = $pdo->query("SELECT * FROM restaurants ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rest, JSON_PRETTY_PRINT);
?>
