<?php
session_start();
include('../core/db.php');

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

// Count pending restaurant approvals
$pendingCount = 0;
try {
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='restaurant' AND is_approved=0")->fetchColumn();
} catch (Exception $e) { /* is_approved column may not exist yet if migration not run */ }

// Count pending rider approvals
$pendingRiders = 0;
try {
    $pendingRiders = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='delivery_partner' AND is_approved=0")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SwiftBite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #fff8f0, #ffe8d0);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .dashboard {
            max-width: 960px;
            margin: 0 auto;
            background: white;
            padding: 48px 40px;
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(255,79,0,0.12);
            text-align: center;
        }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800; color: #ff4f00; margin-bottom: 8px; }
        h1 { font-family: 'Syne', sans-serif; color: #1a1004; font-size: 2.2rem; margin-bottom: 8px; }
        .welcome { font-size: 1rem; color: #8b6a44; margin: 12px 0 36px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 24px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            color: #fff;
            transition: all 0.2s;
            position: relative;
        }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 12px 36px rgba(0,0,0,0.2); }
        .btn .icon { font-size: 2rem; }
        .btn-orange { background: linear-gradient(135deg, #ff4f00, #ff7340); }
        .btn-green  { background: linear-gradient(135deg, #34c759, #2da44e); }
        .btn-blue   { background: linear-gradient(135deg, #2d9cdb, #56ccf2); }
        .btn-sky    { background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
        .btn-dark   { background: linear-gradient(135deg, #1a1004, #3d2600); }
        .btn-purple { background: linear-gradient(135deg, #6c47ff, #a78bfa); }
        .pending-badge {
            position: absolute;
            top: -8px; right: -8px;
            background: #ff3b30;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 3px 9px;
            border-radius: 999px;
        }
        .footer-note { font-size: 0.85rem; color: #b08060; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="logo">🍽️ SwiftBite</div>
        <h1>👨‍💼 Admin Dashboard</h1>
        <p class="welcome">Welcome back, <strong>Admin</strong>! You have full access to all controls.</p>

        <div class="grid">
            <a href="manage_foods.php" class="btn btn-orange">
                <span class="icon">🍔</span>
                Manage Menu
            </a>

            <a href="manage_restaurants.php" class="btn btn-green">
                <span class="icon">🏪</span>
                Restaurants
            </a>

            <a href="pending_restaurants.php" class="btn btn-purple">
                <span class="icon">🔔</span>
                Approvals
                <?php if ($pendingCount > 0): ?>
                    <span class="pending-badge"><?php echo (int)$pendingCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="manage_promos.php" class="btn btn-blue">
                <span class="icon">💸</span>
                Promos
            </a>

            <a href="delivery_partner.php" class="btn btn-sky">
                <span class="icon">🚚</span>
                Delivery
            </a>

            <a href="../index.php" class="btn btn-dark">
                <span class="icon">🏠</span>
                View Site
            </a>

            <a href="manage_users.php" class="btn" style="background: linear-gradient(135deg, #f43f5e, #be123c);">
                <span class="icon">👥</span>
                Users
            </a>

            <a href="manage_riders.php" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <span class="icon">🛵</span>
                Manage Riders
                <?php if ($pendingRiders > 0): ?>
                    <span class="pending-badge"><?php echo $pendingRiders; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <p class="footer-note">More features coming soon: Order management, Revenue reports, User management</p>
    </div>
</body>
</html>

