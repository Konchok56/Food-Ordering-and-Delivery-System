<?php
require_once '../core/bootstrap.php';

// Require restaurant role
requireRole('restaurant');

$user_id = $_SESSION['user_id'];

// Get this user's restaurant
$restStmt = $pdo->prepare("SELECT * FROM restaurants WHERE owner_id = ? LIMIT 1");
$restStmt->execute([$user_id]);
$restaurant = $restStmt->fetch();

if (!$restaurant) {
    renderError(403, "No Restaurant Linked", "Your account is not linked to any restaurant yet.");
}

$restaurant_id = $restaurant['id'];

// ── Date Range ────────────────────────────────────────────
$range = $_GET['range'] ?? '7d';
$rangeMap = [
    '7d'  => ['label' => 'Last 7 Days',   'days' => 7],
    '30d' => ['label' => 'Last 30 Days',  'days' => 30],
    '90d' => ['label' => 'Last 90 Days',  'days' => 90],
    'all' => ['label' => 'All Time',       'days' => 9999],
];
if (!isset($rangeMap[$range])) $range = '7d';
$days = $rangeMap[$range]['days'];
$dateFrom = date('Y-m-d', strtotime("-{$days} days"));

// ── Summary Stats ─────────────────────────────────────────
$sumStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END), 0) AS revenue,
        COALESCE(AVG(CASE WHEN status != 'cancelled' THEN total ELSE NULL END), 0) AS avg_order,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) AS delivered,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled,
        COUNT(DISTINCT user_id) AS unique_customers
    FROM orders
    WHERE restaurant_id = ? AND DATE(created_at) >= ?
");
$sumStmt->execute([$restaurant_id, $dateFrom]);
$summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

// ── Revenue by Day (for chart) ────────────────────────────
$revStmt = $pdo->prepare("
    SELECT DATE(created_at) AS day, COALESCE(SUM(total), 0) AS revenue, COUNT(*) AS orders
    FROM orders
    WHERE restaurant_id = ? AND DATE(created_at) >= ? AND status != 'cancelled'
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$revStmt->execute([$restaurant_id, $dateFrom]);
$revenueByDay = $revStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels  = array_map(fn($r) => date('M d', strtotime($r['day'])), $revenueByDay);
$chartRevenue = array_map(fn($r) => (float)$r['revenue'], $revenueByDay);
$chartOrders  = array_map(fn($r) => (int)$r['orders'], $revenueByDay);

// ── Top Selling Items ─────────────────────────────────────
$topStmt = $pdo->prepare("
    SELECT oi.food_name, oi.emoji, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.restaurant_id = ? AND DATE(o.created_at) >= ? AND o.status != 'cancelled'
    GROUP BY oi.food_name, oi.emoji
    ORDER BY total_qty DESC
    LIMIT 8
");
$topStmt->execute([$restaurant_id, $dateFrom]);
$topItems = $topStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Orders by Hour (peak hours) ───────────────────────────
$hourStmt = $pdo->prepare("
    SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
    FROM orders
    WHERE restaurant_id = ? AND DATE(created_at) >= ? AND status != 'cancelled'
    GROUP BY HOUR(created_at)
    ORDER BY hr
");
$hourStmt->execute([$restaurant_id, $dateFrom]);
$hourRows = $hourStmt->fetchAll(PDO::FETCH_ASSOC);

// Fill all 24 hours
$peakData = array_fill(0, 24, 0);
foreach ($hourRows as $h) {
    $peakData[(int)$h['hr']] = (int)$h['cnt'];
}
$peakLabels = array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23));

// ── Order Status Distribution ──────────────────────────────
$statusStmt = $pdo->prepare("
    SELECT status, COUNT(*) AS cnt
    FROM orders
    WHERE restaurant_id = ? AND DATE(created_at) >= ?
    GROUP BY status
");
$statusStmt->execute([$restaurant_id, $dateFrom]);
$statusDist = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = array_map(fn($s) => ucfirst(str_replace('_', ' ', $s['status'])), $statusDist);
$statusCounts = array_map(fn($s) => (int)$s['cnt'], $statusDist);
$statusColors = [];
$colorMap = ['pending'=>'#f59e0b','preparing'=>'#3b82f6','ready'=>'#8b5cf6','delivered'=>'#10b981','cancelled'=>'#ef4444','out_for_delivery'=>'#ff4f00','confirmed'=>'#06b6d4'];
foreach ($statusDist as $s) {
    $statusColors[] = $colorMap[$s['status']] ?? '#6b7280';
}

// ── Recent Orders ──────────────────────────────────────────
$recentStmt = $pdo->prepare("
    SELECT o.id, o.customer_name, o.total, o.status, o.created_at,
           GROUP_CONCAT(oi.food_name SEPARATOR ', ') AS items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.restaurant_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentStmt->execute([$restaurant_id]);
$recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Compute fulfillment rate
$fulfillmentRate = $summary['total_orders'] > 0
    ? round(($summary['delivered'] / $summary['total_orders']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — <?php echo htmlspecialchars($restaurant['name']); ?> | SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --brand: #ff4f00; --dark: #0f0a05; --surface: #1a1208;
            --card: #211a0e; --border: #2e2416; --text: #f5ede0;
            --muted: #a08060; --green: #10b981; --blue: #3b82f6;
            --red: #ef4444; --yellow: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); min-height: 100vh; display: flex; }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); padding: 32px 16px; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 10; }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--brand); margin-bottom: 32px; padding: 0 16px; }
        .nav-links a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: var(--muted); text-decoration: none; border-radius: 14px; font-weight: 600; font-size: 0.92rem; transition: all 0.2s; margin-bottom: 4px; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,79,0,0.1); color: var(--brand); }
        .sidebar-footer { margin-top: auto; border-top: 1px solid var(--border); padding-top: 24px; }

        .main { flex: 1; margin-left: 260px; padding: 36px 40px; }

        /* Header */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; }
        .page-header h1 span { color: var(--brand); }

        /* Range Tabs */
        .range-tabs { display: flex; gap: 8px; }
        .range-tab { padding: 9px 18px; background: var(--card); border: 1px solid var(--border); border-radius: 999px; text-decoration: none; color: var(--muted); font-size: 0.82rem; font-weight: 700; transition: all 0.2s; }
        .range-tab:hover { border-color: var(--brand); color: #fff; }
        .range-tab.active { background: var(--brand); border-color: var(--brand); color: #fff; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 22px 24px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; opacity: 0.08; }
        .stat-card.orange::before { background: var(--brand); }
        .stat-card.green::before { background: var(--green); }
        .stat-card.blue::before { background: var(--blue); }
        .stat-card.yellow::before { background: var(--yellow); }
        .stat-icon { font-size: 1.5rem; margin-bottom: 10px; }
        .stat-label { font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 1.7rem; font-weight: 800; }
        .stat-value.orange { color: var(--brand); }
        .stat-value.green { color: var(--green); }
        .stat-value.blue { color: var(--blue); }
        .stat-value.yellow { color: var(--yellow); }

        /* Chart Grid */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
        .chart-card { background: var(--card); border: 1px solid var(--border); border-radius: 22px; padding: 24px; }
        .chart-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .chart-wrap { position: relative; height: 260px; }

        /* Top Items */
        .top-items-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
        .items-card { background: var(--card); border: 1px solid var(--border); border-radius: 22px; padding: 24px; }
        .item-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .item-row:last-child { border-bottom: none; }
        .item-rank { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0; }
        .item-rank.gold { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .item-rank.silver { background: rgba(156,163,175,0.15); color: #9ca3af; }
        .item-rank.bronze { background: rgba(180,83,9,0.15); color: #b45309; }
        .item-rank.normal { background: rgba(255,255,255,0.05); color: var(--muted); }
        .item-emoji { font-size: 1.4rem; }
        .item-info { flex: 1; }
        .item-name { font-weight: 700; font-size: 0.9rem; }
        .item-meta { font-size: 0.78rem; color: var(--muted); margin-top: 2px; }
        .item-rev { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--brand); font-size: 0.95rem; }

        /* Recent Orders */
        .recent-card { background: var(--card); border: 1px solid var(--border); border-radius: 22px; overflow: hidden; }
        .recent-card .chart-title { padding: 24px 24px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: rgba(0,0,0,0.2); padding: 14px 24px; font-size: 0.72rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
        td { padding: 14px 24px; font-size: 0.85rem; border-bottom: 1px solid var(--border); }
        .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }

        /* Responsive */
        @media (max-width: 1100px) { .charts-grid, .top-items-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">Swift<span>Bite</span></div>
    <nav class="nav-links">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="analytics.php" class="active">📈 Analytics</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem;font-weight:600;">🚪 Logout</a>
    </div>
</aside>

<main class="main">
    <div class="page-header">
        <h1>📈 <span>Analytics</span> — <?php echo htmlspecialchars($restaurant['name']); ?></h1>
        <div class="range-tabs">
            <?php foreach ($rangeMap as $key => $info): ?>
                <a href="analytics.php?range=<?php echo $key; ?>" class="range-tab <?php echo $range === $key ? 'active' : ''; ?>">
                    <?php echo $info['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card orange">
            <div class="stat-icon">💰</div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value orange">Rs. <?php echo number_format($summary['revenue'], 0); ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon">📦</div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value blue"><?php echo (int)$summary['total_orders']; ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-label">Fulfillment Rate</div>
            <div class="stat-value green"><?php echo $fulfillmentRate; ?>%</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">🧾</div>
            <div class="stat-label">Avg Order Value</div>
            <div class="stat-value yellow">Rs. <?php echo number_format($summary['avg_order'], 0); ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon">👥</div>
            <div class="stat-label">Unique Customers</div>
            <div class="stat-value blue"><?php echo (int)$summary['unique_customers']; ?></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">❌</div>
            <div class="stat-label">Cancelled</div>
            <div class="stat-value" style="color:var(--red);"><?php echo (int)$summary['cancelled']; ?></div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-grid">
        <!-- Revenue Trend -->
        <div class="chart-card">
            <div class="chart-title">💰 Revenue Trend</div>
            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
        </div>

        <!-- Order Volume -->
        <div class="chart-card">
            <div class="chart-title">📦 Order Volume</div>
            <div class="chart-wrap"><canvas id="ordersChart"></canvas></div>
        </div>

        <!-- Peak Hours -->
        <div class="chart-card">
            <div class="chart-title">🕐 Peak Hours</div>
            <div class="chart-wrap"><canvas id="peakChart"></canvas></div>
        </div>

        <!-- Status Distribution -->
        <div class="chart-card">
            <div class="chart-title">📊 Order Status Distribution</div>
            <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
        </div>
    </div>

    <!-- Top Items + Recent -->
    <div class="top-items-grid">
        <!-- Top Selling Items -->
        <div class="items-card">
            <div class="chart-title">🏆 Top Selling Items</div>
            <?php if (empty($topItems)): ?>
                <div style="padding: 40px; text-align: center; color: var(--muted);">No data yet</div>
            <?php else: ?>
                <?php foreach ($topItems as $i => $item):
                    $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : 'normal'));
                ?>
                <div class="item-row">
                    <div class="item-rank <?php echo $rankClass; ?>"><?php echo $i + 1; ?></div>
                    <div class="item-emoji"><?php echo htmlspecialchars($item['emoji'] ?? '🍔'); ?></div>
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                        <div class="item-meta"><?php echo (int)$item['total_qty']; ?> sold</div>
                    </div>
                    <div class="item-rev">Rs. <?php echo number_format($item['total_revenue'], 0); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
        <div class="recent-card">
            <div class="chart-title">📋 Recent Orders</div>
            <table>
                <thead>
                    <tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:40px;">No orders yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $o):
                            $sColors = ['pending'=>'#f59e0b','preparing'=>'#3b82f6','ready'=>'#8b5cf6','delivered'=>'#10b981','cancelled'=>'#ef4444','out_for_delivery'=>'#ff4f00'];
                            $sc = $sColors[$o['status']] ?? '#6b7280';
                        ?>
                        <tr>
                            <td style="font-weight:700;color:var(--brand);">#<?php echo str_pad($o['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td style="font-weight:700;">Rs. <?php echo number_format($o['total'], 0); ?></td>
                            <td>
                                <span class="badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $o['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
// Chart defaults
Chart.defaults.color = '#a08060';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'DM Sans', sans-serif";

// ── Revenue Trend Chart ──────────────────────────────────
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: <?php echo json_encode($chartRevenue); ?>,
            borderColor: '#ff4f00',
            backgroundColor: 'rgba(255,79,0,0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: '#ff4f00'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' } }, x: { grid: { display: false } } }
    }
});

// ── Order Volume Chart ───────────────────────────────────
new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Orders',
            data: <?php echo json_encode($chartOrders); ?>,
            backgroundColor: 'rgba(59,130,246,0.6)',
            borderColor: '#3b82f6',
            borderWidth: 1,
            borderRadius: 6,
            maxBarThickness: 32
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.04)' } }, x: { grid: { display: false } } }
    }
});

// ── Peak Hours Chart ─────────────────────────────────────
const peakValues = <?php echo json_encode(array_values($peakData)); ?>;
const maxPeak = Math.max(...peakValues, 1);
const peakColors = peakValues.map(v => {
    const intensity = v / maxPeak;
    return `rgba(255, 79, 0, ${0.2 + intensity * 0.7})`;
});

new Chart(document.getElementById('peakChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($peakLabels); ?>,
        datasets: [{
            label: 'Orders',
            data: peakValues,
            backgroundColor: peakColors,
            borderRadius: 4,
            maxBarThickness: 18
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.04)' } },
            x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } }
        }
    }
});

// ── Status Distribution Doughnut ─────────────────────────
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($statusCounts); ?>,
            backgroundColor: <?php echo json_encode($statusColors); ?>,
            borderWidth: 2,
            borderColor: '#211a0e'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 12, font: { size: 11, weight: 600 } } }
        }
    }
});
</script>

</body>
</html>
