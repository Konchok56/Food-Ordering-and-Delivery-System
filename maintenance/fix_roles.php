<?php
include('core/db.php');
// Fix Ronal Shrestha
$pdo->exec("UPDATE users SET role='restaurant', is_approved=0 WHERE email='ronalshrestha123@gmail.com'");
// Fix test user 9
$pdo->exec("UPDATE users SET role='restaurant', is_approved=0 WHERE id=9");
echo "Fixed existing restaurant roles to 'restaurant'.\n";
?>
