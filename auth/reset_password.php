<?php
include('../includes/db.php');

$token = $_GET['token'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token=? AND token_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired token");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<form action="../actions/reset_password.php" method="POST">
    <input type="hidden" name="token" value="<?= $token ?>">
    <input type="password" name="password" placeholder="New Password" required>
    <button>Reset Password</button>
</form>

</body>
</html>
