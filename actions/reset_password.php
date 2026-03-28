<?php
include('../includes/db.php');

$token = $_POST['token'];
$newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password=?, reset_token=NULL, token_expiry=NULL WHERE reset_token=?");
$stmt->execute([$newPassword, $token]);

echo "Password updated successfully! 
<br><br><a href='../auth/login.php'>← Back to Login</a>";
?>
