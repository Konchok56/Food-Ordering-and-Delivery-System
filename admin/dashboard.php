<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'admin') {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied! You are not an Admin.</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SwiftBite</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            margin: 0;
            padding: 40px;
        }
        .dashboard {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 { color: #27ae60; font-size: 2.5em; }
        .welcome { font-size: 1.2em; color: #555; margin: 20px 0; }
        ul { text-align: left; display: inline-block; margin: 30px 0; }
        li { margin: 12px 0; font-size: 1.1em; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn:hover { background: #219653; }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>👨‍💼 Admin Dashboard</h1>
        <p class="welcome">Welcome, <strong>Admin (Hari)</strong>! You have full access.</p>
        
        <hr>
        <h3>Quick Actions:</h3>
        <div style="display:flex; flex-wrap:wrap; gap:16px; justify-content:center; margin:30px 0;">
    <a href="manage_foods.php" class="btn" style="background:linear-gradient(135deg,#ff4f00,#ff7340); font-size:1.1em; padding:16px 36px;">
        🍔 Manage Menu
    </a>

    <a href="delivery_partner.php" class="btn" style="background:linear-gradient(135deg,#2d9cdb,#56ccf2); font-size:1.1em; padding:16px 36px;">
        🚚 Delivery Partner Panel
    </a>

    <a href="../index.php" class="btn" style="background:#1a1004;">
        🏠 View Site
    </a>
</div>
        <h4 style="color:#888; margin-top:10px;">More features coming soon: Orders, Users, Reports</h4>
    </div>
</body>
</html>