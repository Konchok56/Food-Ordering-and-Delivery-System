<?php
include('core/db.php');
$user = $pdo->query("SELECT * FROM users WHERE id=9")->fetch(PDO::FETCH_ASSOC);
echo json_encode($user, JSON_PRETTY_PRINT);
?>
