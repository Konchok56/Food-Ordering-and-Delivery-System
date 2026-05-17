<?php
require_once '../core/bootstrap.php';

// Require restaurant or admin role
requireRole('restaurant');

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get this user's restaurant
$restStmt = $pdo->prepare("SELECT * FROM restaurants WHERE owner_id = ? LIMIT 1");
$restStmt->execute([$user_id]);
$restaurant = $restStmt->fetch();

// If admin viewing, allow picking any restaurant (fallback)
if (!$restaurant && $user_role === 'admin') {
    $restaurant = $pdo->query("SELECT * FROM restaurants ORDER BY id ASC LIMIT 1")->fetch();
}

if (!$restaurant) {
    renderError(403, "No Restaurant Linked", "Your account is not linked to any restaurant yet. Please ask the admin to link your account.");
}

$restaurant_id = $restaurant['id'];

// ---- Stats ----
$todayStart = date('Y-m-d 00:00:00');
$todayEnd   = date('Y-m-d 23:59:59');

$statStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) AS preparing,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS ready,
        COALESCE(SUM(total), 0) AS revenue
    FROM orders
    WHERE restaurant_id = ? AND created_at BETWEEN ? AND ? AND status != 'cancelled'
");
$statStmt->execute([$restaurant_id, $todayStart, $todayEnd]);
$stats = $statStmt->fetch(PDO::FETCH_ASSOC);

// ---- Orders list ----
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'pending', 'preparing', 'ready', 'delivered', 'cancelled'];
if (!in_array($filter, $allowedFilters)) $filter = 'all';

$orderSql = "SELECT o.*, GROUP_CONCAT(oi.food_name, ' x', oi.quantity ORDER BY oi.id SEPARATOR ', ') AS items_summary
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.restaurant_id = ?";
$params = [$restaurant_id];

if ($filter !== 'all') {
    $orderSql .= " AND o.status = ?";
    $params[] = $filter;
}

$orderSql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT 50";
$orderStmt = $pdo->prepare($orderSql);
$orderStmt->execute($params);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Status badge styling
$statusConfig = [
    'pending'   => ['label' => 'Pending',   'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)'],
    'preparing' => ['label' => 'Preparing', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.1)'],
    'ready'     => ['label' => 'Ready',     'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)'],
    'delivered' => ['label' => 'Delivered', 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,0.1)'],
    'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> Dashboard — SwiftBite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #ff4f00; --dark: #0f0a05; --surface: #1a1208; --card: #211a0e; --border: #2e2416; --text: #f5ede0; --muted: #a08060; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); min-height: 100vh; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); padding: 32px 16px; display: flex; flex-direction: column; position: fixed; height: 100vh; }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--brand); margin-bottom: 32px; padding: 0 16px; }
        .nav-links a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: var(--muted); text-decoration: none; border-radius: 14px; font-weight: 600; font-size: 0.92rem; transition: all 0.2s; margin-bottom: 4px; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,79,0,0.1); color: var(--brand); }
        .sidebar-footer { margin-top: auto; border-top: 1px solid var(--border); padding-top: 24px; }

        .main { flex: 1; margin-left: 260px; padding: 40px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 24px; }
        .stat-label { font-size: 0.78rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--brand); }

        /* Filter Tabs */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-tab { padding: 10px 20px; background: var(--card); border: 1px solid var(--border); border-radius: 999px; text-decoration: none; color: var(--muted); font-size: 0.85rem; font-weight: 700; transition: all 0.2s; }
        .filter-tab:hover { border-color: var(--brand); color: #fff; }
        .filter-tab.active { background: var(--brand); border-color: var(--brand); color: #fff; }

        /* Table */
        .table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: rgba(0,0,0,0.2); padding: 16px 24px; font-size: 0.75rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
        td { padding: 18px 24px; font-size: 0.88rem; border-bottom: 1px solid var(--border); }
        .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; }

        .btn-status { padding: 8px 16px; border-radius: 10px; border: none; cursor: pointer; font-size: 0.8rem; font-weight: 700; transition: all 0.2s; }
        .btn-preparing { background: #3b82f6; color: #fff; }
        .btn-ready { background: #10b981; color: #fff; }
        .btn-delivered { background: #6b7280; color: #fff; }

        .auto-refresh { font-size: 0.78rem; color: var(--muted); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .refresh-dot { width: 8px; height: 8px; background: var(--brand); border-radius: 50%; box-shadow: 0 0 8px var(--brand); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.5; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">Swift<span>Bite</span></div>
    <nav class="nav-links">
        <a href="dashboard.php" class="<?php echo $filter === 'all' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-bar"></i> Dashboard</a>
        <a href="dashboard.php?filter=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>"><i class="fa-solid fa-bell"></i> Pending</a>
        <a href="dashboard.php?filter=preparing" class="<?php echo $filter === 'preparing' ? 'active' : ''; ?>">👩‍🍳 Preparing</a>
        <a href="analytics.php">📈 Analytics</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem;font-weight:600;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>

<main class="main">
    <div class="header">
        <h1>Welcome, <em><?php echo htmlspecialchars($restaurant['name']); ?></em></h1>
        <div class="auto-refresh">
            <span class="refresh-dot"></span> Auto-refreshing every 30s
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Orders Today</div>
            <div class="stat-value"><?php echo (int)$stats['total_orders']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value" style="color:#f59e0b"><?php echo (int)$stats['pending']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Preparing</div>
            <div class="stat-value" style="color:#3b82f6"><?php echo (int)$stats['preparing']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Revenue Today</div>
            <div class="stat-value" style="color:#10b981">Rs. <?php echo number_format($stats['revenue'], 0); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <a href="dashboard.php" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Orders</a>
        <a href="dashboard.php?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="dashboard.php?filter=preparing" class="filter-tab <?php echo $filter === 'preparing' ? 'active' : ''; ?>">Preparing</a>
        <a href="dashboard.php?filter=ready" class="filter-tab <?php echo $filter === 'ready' ? 'active' : ''; ?>">Ready</a>
        <a href="dashboard.php?filter=delivered" class="filter-tab <?php echo $filter === 'delivered' ? 'active' : ''; ?>">Delivered</a>
    </div>

    <!-- Orders Table -->
    <div class="table-wrap">
        <?php if (empty($orders)): ?>
            <div style="padding: 60px; text-align: center; color: var(--muted); font-weight: 600;">No orders found here.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): 
                    $cfg = $statusConfig[$o['status']] ?? ['label' => $o['status'], 'color' => '#fff', 'bg' => '#333'];
                ?>
                <tr>
                    <td style="font-weight:700;color:var(--brand);">#<?php echo str_pad($o['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <div style="font-weight:700;"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                        <div style="font-size:0.75rem;color:var(--muted);"><?php echo htmlspecialchars($o['customer_phone']); ?></div>
                    </td>
                    <td style="max-width:300px; font-size:0.8rem; color:var(--muted);"><?php echo htmlspecialchars($o['items_summary']); ?></td>
                    <td style="font-weight:700;">Rs. <?php echo number_format($o['total'], 0); ?></td>
                    <td>
                        <span class="badge" style="background:<?php echo $cfg['bg']; ?>;color:<?php echo $cfg['color']; ?>">
                            <?php echo $cfg['label']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($o['status'] === 'pending'): ?>
                            <button class="btn-status btn-preparing" onclick="updateStatus(<?php echo $o['id']; ?>, 'preparing')">Accept</button>
                        <?php elseif ($o['status'] === 'preparing'): ?>
                            <button class="btn-status btn-ready" onclick="updateStatus(<?php echo $o['id']; ?>, 'ready')">Ready</button>
                        <?php elseif ($o['status'] === 'ready'): ?>
                            <button class="btn-status btn-delivered" onclick="updateStatus(<?php echo $o['id']; ?>, 'delivered')">Delivered</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>

<script>
function updateStatus(orderId, status) {
    if(!confirm("Change order status to " + status + "?")) return;
    
    fetch('update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=' + orderId + '&status=' + status
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    });
}

// Auto-refresh every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>

</body>
</html>
