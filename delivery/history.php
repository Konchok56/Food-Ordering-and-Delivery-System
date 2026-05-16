<?php
session_start();
include('../core/db.php');

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }

$roleStmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$rider = $roleStmt->fetch(PDO::FETCH_ASSOC);
if (!$rider || $rider['role'] !== 'delivery_partner') {
    echo "<h2 style='color:red;text-align:center;margin-top:80px;'>⛔ Access Denied.</h2>"; exit;
}

// Stats for THIS rider (by delivery_partner_name)
$riderName = $rider['name'];
$totalStmt = $pdo->prepare("SELECT
    COUNT(CASE WHEN status='delivered' THEN 1 END) AS total_delivered,
    COUNT(CASE WHEN status='cancelled' THEN 1 END) AS total_cancelled,
    COUNT(CASE WHEN status IN ('confirmed','preparing','out_for_delivery') THEN 1 END) AS active_count,
    COALESCE(SUM(CASE WHEN status='delivered' THEN total ELSE 0 END), 0) AS revenue,
    COUNT(CASE WHEN status='delivered' AND DATE(updated_at) = CURDATE() THEN 1 END) AS today
    FROM orders WHERE delivery_partner_name = ?");
$totalStmt->execute([$riderName]);
$stats = $totalStmt->fetch(PDO::FETCH_ASSOC);

// History
$history = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status IN ('delivered','cancelled') AND o.delivery_partner_name = ?
    GROUP BY o.id
    ORDER BY o.updated_at DESC
    LIMIT 80
");
$history->execute([$riderName]);
$history = $history->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
    <script>(function(){var t=localStorage.getItem('sb-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script src="../assets/js/theme.js"></script>
    <style>
        :root { --orange: #ff4f00; --dark: #1a0a00; }
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; margin: 0; transition: background 0.3s, color 0.3s; }

        html:not([data-theme='dark']) body  { background: #fff8f0; }
        html:not([data-theme='dark']) .main { background: #fff8f0; }
        html:not([data-theme='dark']) .stat-card { background: #fff; border-color: rgba(255,79,0,0.12); }
        html:not([data-theme='dark']) .stat-num  { color: #1a0a00; }
        html:not([data-theme='dark']) .stat-label { color: #8b6a44; }
        html:not([data-theme='dark']) .page-title { color: #1a0a00; }
        html:not([data-theme='dark']) .page-sub   { color: #8b6a44; }
        html:not([data-theme='dark']) .tbl-wrap   { background: #fff; border-color: rgba(255,79,0,0.1); }
        html:not([data-theme='dark']) .htbl th    { background: #fff8f0; color: #8b6a44; border-bottom-color: rgba(0,0,0,0.07); }
        html:not([data-theme='dark']) .htbl td    { color: #3d2600; border-bottom-color: rgba(0,0,0,0.05); }
        html:not([data-theme='dark']) .htbl tr:hover td { background: rgba(255,79,0,0.04); }
        html:not([data-theme='dark']) .filter-bar input { background: #fff; border-color: #e2d5c5; color: #1a0a00; }
        html:not([data-theme='dark']) .empty-msg { color: #8b6a44; }

        [data-theme='dark'] body  { background: #0f0500; }
        [data-theme='dark'] .main { background: #0f0500; }
        [data-theme='dark'] .stat-card { background: #1a0a00; border-color: rgba(255,255,255,0.06); }
        [data-theme='dark'] .stat-num  { color: #fff; }
        [data-theme='dark'] .stat-label { color: #c9a07d; }
        [data-theme='dark'] .page-title { color: #fff; }
        [data-theme='dark'] .page-sub   { color: #c9a07d; }
        [data-theme='dark'] .tbl-wrap   { background: #1a0a00; border-color: rgba(255,255,255,0.06); }
        [data-theme='dark'] .htbl th    { background: #1a0a00; color: #8b6a44; border-bottom-color: rgba(255,255,255,0.06); }
        [data-theme='dark'] .htbl td    { color: #e8d5c0; border-bottom-color: rgba(255,255,255,0.04); }
        [data-theme='dark'] .htbl tr:hover td { background: rgba(255,79,0,0.05); }
        [data-theme='dark'] .filter-bar input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #fff; }
        [data-theme='dark'] .empty-msg { color: #8b6a44; }

        /* Layout */
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #1a0a00; padding: 28px 20px; display: flex; flex-direction: column; gap: 8px; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; overflow-y: auto; }
        .sidebar-logo { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--orange); margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 10px; }
        .rider-card { background: rgba(255,79,0,0.1); border: 1px solid rgba(255,79,0,0.2); border-radius: 16px; padding: 16px; margin-bottom: 16px; }
        .rider-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--orange), #ff2400); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .rider-name { font-weight: 700; color: #fff; font-size: 0.95rem; }
        .rider-role { color: var(--orange); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 14px; text-decoration: none; color: #c9a07d; font-weight: 600; transition: all .2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,79,0,0.15); color: var(--orange); }
        .nav-icon { font-size: 1.1rem; width: 22px; text-align: center; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.07); }

        .main { margin-left: 260px; padding: 32px; flex: 1; }

        /* Topbar */
        .topbar { margin-bottom: 28px; }
        .page-title { font-family: 'Syne', sans-serif; font-size: 1.9rem; color: #fff; margin: 0 0 4px; }
        .page-sub { color: #c9a07d; font-size: 0.9rem; margin: 0; }

        /* Stats */
        .stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 28px; }
        .stat-card { background: #1a0a00; border: 1px solid rgba(255,79,0,0.15); border-radius: 20px; padding: 20px; position: relative; overflow: hidden; }
        .stat-card::before { content:''; position:absolute; top:0; right:0; width:60px; height:60px; background: rgba(255,79,0,0.07); border-radius:50%; transform:translate(15px,-15px); }
        .stat-emoji { font-size: 1.5rem; margin-bottom: 10px; }
        .stat-num  { font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 0.82rem; font-weight: 600; margin-top: 6px; }

        /* Filter */
        .filter-bar { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
        .filter-bar input {
            padding: 10px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem; outline: none;
            background: rgba(255,255,255,0.05); color: #fff; flex: 1; min-width: 180px;
        }
        .filter-bar input::placeholder { color: #8b6a44; }
        .filter-tab { padding: 9px 18px; border-radius: 12px; border: 1px solid rgba(255,79,0,0.25); background: transparent; color: #c9a07d; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all .2s; }
        .filter-tab.active, .filter-tab:hover { background: rgba(255,79,0,0.15); color: var(--orange); border-color: rgba(255,79,0,0.5); }

        /* Table */
        .tbl-wrap { background: #1a0a00; border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; overflow: hidden; }
        .htbl { width: 100%; border-collapse: collapse; }
        .htbl th { background: #1a0a00; color: #8b6a44; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.6px; padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); font-weight: 700; }
        .htbl td { padding: 15px 18px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.88rem; vertical-align: middle; transition: background 0.15s; }
        .htbl tr:last-child td { border-bottom: none; }
        .htbl tr:hover td { background: rgba(255,79,0,0.04); }
        .htbl tr.hidden-row { display: none; }

        .order-id { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 0.95rem; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .b-delivered { background: rgba(52,199,89,0.15); color: #30d158; }
        .b-cancelled  { background: rgba(255,59,48,0.12); color: #ff6b6b; }

        .empty-msg { text-align: center; padding: 50px 20px; color: #8b6a44; }
        .empty-msg .big { font-size: 3rem; margin-bottom: 12px; }
        .empty-msg h3 { font-family: 'Syne', sans-serif; font-size: 1.2rem; margin: 0 0 6px; }

        @media (max-width: 1100px) { .stats-row { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 900px) { .stats-row { grid-template-columns: 1fr 1fr; } .sidebar { width: 220px; } .main { margin-left: 220px; } }
        @media (max-width: 640px) { .sidebar { display: none; } .main { margin-left: 0; } .stats-row { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-motorcycle"></i> SwiftBite
            <button id="theme-toggle" class="theme-toggle-btn" style="margin-left:auto;width:36px;height:36px;font-size:0.95rem;border:1.5px solid rgba(255,79,0,0.35);background:rgba(255,79,0,0.12);" title="Toggle theme">
                <span class="theme-icon theme-icon-sun">&#9728;</span>
                <span class="theme-icon theme-icon-moon"><i class="fa-solid fa-moon"></i></span>
            </button>
        </div>
        <div class="rider-card">
            <div class="rider-avatar"><?php echo strtoupper(substr($rider['name'], 0, 1)); ?></div>
            <div class="rider-name"><?php echo htmlspecialchars($rider['name']); ?></div>
            <div class="rider-role">🟢 Delivery Partner</div>
        </div>
        <a class="nav-item" href="dashboard.php"><span class="nav-icon"><i class="fa-solid fa-box"></i></span> Active Orders</a>
        <a class="nav-item active" href="history.php"><span class="nav-icon">📋</span> Delivery History</a>
        <div class="sidebar-footer">
            <a class="nav-item" href="../auth/logout.php"><span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span> Logout</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1 class="page-title">📋 Delivery History</h1>
            <p class="page-sub">Your personal delivery record — all completed and cancelled runs</p>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-emoji">🟡</div>
                <div class="stat-num"><?php echo (int)($stats['active_count'] ?? 0); ?></div>
                <div class="stat-label">Active Now</div>
            </div>
            <div class="stat-card">
                <div class="stat-emoji"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i></div>
                <div class="stat-num"><?php echo (int)($stats['total_delivered'] ?? 0); ?></div>
                <div class="stat-label">Total Delivered</div>
            </div>
            <div class="stat-card">
                <div class="stat-emoji">🗓️</div>
                <div class="stat-num"><?php echo (int)($stats['today'] ?? 0); ?></div>
                <div class="stat-label">Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-emoji"><i class="fa-solid fa-coins"></i></div>
                <div class="stat-num" style="font-size:1.4rem;">Rs.<?php echo number_format((float)($stats['revenue'] ?? 0), 0); ?></div>
                <div class="stat-label">Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-emoji"><i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i></div>
                <div class="stat-num"><?php echo (int)($stats['total_cancelled'] ?? 0); ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <input type="text" id="search-input" placeholder="🔍  Search by order ID, customer or address…">
            <button class="filter-tab active" data-filter="all">All</button>
            <button class="filter-tab" data-filter="delivered"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Delivered</button>
            <button class="filter-tab" data-filter="cancelled"><i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> Cancelled</button>
        </div>

        <!-- Table -->
        <div class="tbl-wrap">
            <?php if (empty($history)): ?>
                <div class="empty-msg">
                    <div class="big">📭</div>
                    <h3>No history yet</h3>
                    <p>Your completed deliveries will appear here.</p>
                </div>
            <?php else: ?>
            <table class="htbl" id="history-table">
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
                    <?php foreach ($history as $o): ?>
                    <tr data-status="<?php echo $o['status']; ?>"
                        data-search="<?php echo strtolower($o['id'] . ' ' . $o['customer_name'] . ' ' . $o['delivery_address'] . ' ' . $o['delivery_city']); ?>">
                        <td><span class="order-id" style="color:var(--orange);">#<?php echo str_pad($o['id'], 5, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <?php echo htmlspecialchars($o['customer_name']); ?><br>
                            <span style="color:#8b6a44;font-size:0.78rem;"><?php echo htmlspecialchars($o['customer_phone']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($o['delivery_address']); ?>, <?php echo htmlspecialchars($o['delivery_city']); ?></td>
                        <td style="text-align:center;"><?php echo (int)$o['item_count']; ?></td>
                        <td style="color:var(--orange);font-weight:700;">Rs. <?php echo number_format((float)$o['total'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $o['status'] === 'delivered' ? 'b-delivered' : 'b-cancelled'; ?>">
                                <?php echo $o['status'] === 'delivered' ? '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Delivered' : '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> Cancelled'; ?>
                            </span>
                        </td>
                        <td style="color:#8b6a44;font-size:0.82rem;white-space:nowrap;">
                            <?php echo date('M d, Y', strtotime($o['updated_at'])); ?><br>
                            <span style="font-size:0.75rem;"><?php echo date('h:i A', strtotime($o['updated_at'])); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
// ── Filter tabs ──
document.querySelectorAll('.filter-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filterTable();
    });
});

// ── Search ──
document.getElementById('search-input')?.addEventListener('input', filterTable);

function filterTable() {
    const filter = document.querySelector('.filter-tab.active')?.dataset.filter || 'all';
    const search = document.getElementById('search-input')?.value.toLowerCase() || '';
    document.querySelectorAll('#history-table tbody tr').forEach(row => {
        const statusMatch = filter === 'all' || row.dataset.status === filter;
        const searchMatch = !search || row.dataset.search.includes(search);
        row.classList.toggle('hidden-row', !(statusMatch && searchMatch));
    });
}
</script>
</body>
</html>
