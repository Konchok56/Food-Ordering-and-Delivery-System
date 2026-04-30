<?php
session_start();
include('../core/db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$roleStmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$rider = $roleStmt->fetch(PDO::FETCH_ASSOC);

if (!$rider || $rider['role'] !== 'delivery_partner') {
    echo "<h2 style='color:red;text-align:center;margin-top:80px;'>⛔ Access Denied.</h2>";
    exit;
}

// Completed / cancelled orders
$history = $pdo->query("
    SELECT o.*,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status IN ('delivered', 'cancelled')
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 60
")->fetchAll(PDO::FETCH_ASSOC);

$totalDelivered = array_reduce($history, fn($c, $r) => $c + ($r['status'] === 'delivered' ? 1 : 0), 0);
$totalRevenue   = array_reduce($history, fn($c, $r) => $c + ($r['status'] === 'delivered' ? (float)$r['total'] : 0), 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
    <script>(function(){var t=localStorage.getItem('sb-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script src="../assets/js/theme.js"></script>
    <style>
        :root { --orange: #ff4f00; --dark: #1a0a00; --cream: #fff8f0; --muted: #8b6a44; }
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; margin: 0; transition: background 0.3s ease, color 0.3s ease; }

        /* Light theme */
        html:not([data-theme='dark']) body { background: #fff8f0; }
        html:not([data-theme='dark']) .main { background: #fff8f0; }
        html:not([data-theme='dark']) .stat-box { background: #fff; border-color: rgba(255,79,0,0.12); }
        html:not([data-theme='dark']) .stat-num { color: #1a0a00; }
        html:not([data-theme='dark']) .stat-label { color: #8b6a44; }
        html:not([data-theme='dark']) .topbar h1 { color: #1a0a00; }
        html:not([data-theme='dark']) .topbar p { color: #8b6a44; }
        html:not([data-theme='dark']) .tbl-wrap { background: #fff; border-color: rgba(255,79,0,0.1); }
        html:not([data-theme='dark']) .history-table th { background: #fff8f0; color: #8b6a44; border-bottom-color: rgba(0,0,0,0.08); }
        html:not([data-theme='dark']) .history-table td { color: #3d2600; border-bottom-color: rgba(0,0,0,0.05); }
        html:not([data-theme='dark']) .history-table tr:hover td { background: rgba(255,79,0,0.04); }

        /* Dark theme */
        [data-theme='dark'] body { background: #0f0500; }
        [data-theme='dark'] .main { background: #0f0500; }
        [data-theme='dark'] .stat-box { background: #1a0a00; }
        [data-theme='dark'] .stat-num { color: #fff; }
        [data-theme='dark'] .stat-label { color: #c9a07d; }
        [data-theme='dark'] .topbar h1 { color: #fff; }
        [data-theme='dark'] .topbar p { color: #c9a07d; }
        [data-theme='dark'] .tbl-wrap { background: #1a0a00; border-color: rgba(255,255,255,0.06); }
        [data-theme='dark'] .history-table th { background: #1a0a00; color: #8b6a44; border-bottom-color: rgba(255,255,255,0.06); }
        [data-theme='dark'] .history-table td { color: #e8d5c0; border-bottom-color: rgba(255,255,255,0.04); }
        [data-theme='dark'] .history-table tr:hover td { background: rgba(255,79,0,0.04); }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #1a0a00; padding: 28px 20px; display: flex; flex-direction: column; gap: 8px; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; }
        .sidebar-logo { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--orange); margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.07); }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 14px; text-decoration: none; color: #c9a07d; font-weight: 600; transition: all .2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,79,0,0.15); color: var(--orange); }
        .nav-icon { font-size: 1.2rem; width: 24px; text-align: center; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.07); }

        .main { margin-left: 260px; padding: 32px; flex: 1; }
        .topbar { margin-bottom: 28px; }
        .topbar h1 { font-family: 'Syne', sans-serif; font-size: 1.9rem; color: #fff; margin: 0 0 4px; }
        .topbar p { color: #c9a07d; margin: 0; font-size: 0.9rem; }

        .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .stat-box { background: #1a0a00; border: 1px solid rgba(255,79,0,0.15); border-radius: 20px; padding: 22px 24px; }
        .stat-num { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: #fff; }
        .stat-label { color: #c9a07d; font-size: 0.88rem; margin-top: 6px; font-weight: 600; }

        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { background: #1a0a00; color: #8b6a44; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .history-table td { padding: 16px 18px; border-bottom: 1px solid rgba(255,255,255,0.04); color: #e8d5c0; font-size: 0.9rem; vertical-align: middle; }
        .history-table tr:hover td { background: rgba(255,79,0,0.04); }
        .tbl-wrap { background: #1a0a00; border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; overflow: hidden; }

        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }
        .b-delivered { background: rgba(52,199,89,0.15); color: #30d158; }
        .b-cancelled  { background: rgba(255,59,48,0.12); color: #ff6b6b; }

        @media (max-width: 640px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo" style="display:flex;align-items:center;">
            🛵 SwiftBite
            <button id="theme-toggle" class="theme-toggle-btn" style="margin-left:auto;width:36px;height:36px;font-size:0.95rem;border:1.5px solid rgba(255,79,0,0.35);background:rgba(255,79,0,0.12);" title="Toggle theme" aria-label="Toggle dark/light mode">
              <span class="theme-icon theme-icon-sun">&#9728;</span>
              <span class="theme-icon theme-icon-moon">&#127769;</span>
            </button>
        </div>
        <a class="nav-item" href="dashboard.php"><span class="nav-icon">📦</span> Active Orders</a>
        <a class="nav-item active" href="history.php"><span class="nav-icon">📋</span> Delivery History</a>
        <div class="sidebar-footer">
            <a class="nav-item" href="../auth/logout.php"><span class="nav-icon">🚪</span> Logout</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1>📋 Delivery History</h1>
            <p>All completed and cancelled orders</p>
        </div>

        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num"><?php echo $totalDelivered; ?></div>
                <div class="stat-label">✅ Total Delivered</div>
            </div>
            <div class="stat-box">
                <div class="stat-num">Rs. <?php echo number_format($totalRevenue, 0); ?></div>
                <div class="stat-label">💰 Total Order Value Delivered</div>
            </div>
        </div>

        <div class="tbl-wrap">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#8b6a44;padding:40px;">No history yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $o): ?>
                        <tr>
                            <td style="font-family:'Syne',sans-serif;font-weight:800;color:#fff;">
                                #<?php echo str_pad($o['id'], 5, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?><br>
                                <span style="color:#8b6a44;font-size:0.8rem;"><?php echo htmlspecialchars($o['customer_phone']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($o['delivery_address']); ?>, <?php echo htmlspecialchars($o['delivery_city']); ?></td>
                            <td><?php echo (int)$o['item_count']; ?> item<?php echo $o['item_count'] != 1 ? 's' : ''; ?></td>
                            <td style="color:var(--orange);font-weight:700;">Rs. <?php echo number_format((float)$o['total'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $o['status'] === 'delivered' ? 'b-delivered' : 'b-cancelled'; ?>">
                                    <?php echo $o['status'] === 'delivered' ? '✅ Delivered' : '❌ Cancelled'; ?>
                                </span>
                            </td>
                            <td style="color:#8b6a44;font-size:0.85rem;"><?php echo date('M d, Y h:i A', strtotime($o['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
