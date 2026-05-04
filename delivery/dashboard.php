<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');
include('../core/validation.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Only delivery_partner role allowed
$roleStmt = $pdo->prepare("SELECT role, name, email, phone FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$rider = $roleStmt->fetch(PDO::FETCH_ASSOC);

if (!$rider || $rider['role'] !== 'delivery_partner') {
    echo "<h2 style='color:red;text-align:center;margin-top:80px;'>⛔ Access Denied. Delivery Partners only.</h2>";
    exit;
}

$flash_success = $_SESSION['delivery_success'] ?? '';
$flash_error   = $_SESSION['delivery_error']   ?? '';
unset($_SESSION['delivery_success'], $_SESSION['delivery_error']);

// Stats
$statsRow = $pdo->query("SELECT
    SUM(CASE WHEN status IN ('pending','confirmed','preparing','out_for_delivery') THEN 1 ELSE 0 END) AS active_count,
    SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) AS in_transit,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_today
    FROM orders
    WHERE DATE(created_at) = CURDATE()")->fetch(PDO::FETCH_ASSOC);

$stats = [
    'active'    => (int)($statsRow['active_count'] ?? 0),
    'transit'   => (int)($statsRow['in_transit']   ?? 0),
    'delivered' => (int)($statsRow['delivered_today'] ?? 0),
];

// Active orders (all non-delivered, non-cancelled)
$activeOrders = $pdo->query("
    SELECT o.*, 
           COALESCE(GROUP_CONCAT(oi.food_name SEPARATOR ', '), '') AS item_names,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status IN ('pending','confirmed','preparing','out_for_delivery')
    GROUP BY o.id
    ORDER BY FIELD(o.status,'out_for_delivery','preparing','confirmed','pending'), o.created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
    <!-- Apply saved theme before first paint -->
    <script>(function(){var t=localStorage.getItem('sb-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script src="../assets/js/theme.js"></script>
    <style>
        :root { --orange: #ff4f00; --dark: #1a0a00; --cream: #fff8f0; --cream2: #f0e6d9; --muted: #8b6a44; --shadow: 0 4px 24px rgba(26,10,0,0.08); }
        * { box-sizing: border-box; }

        /* ── Delivery dashboard base (dark by default, adapts to theme) ── */
        body {
          font-family: 'DM Sans', sans-serif;
          margin: 0;
          min-height: 100vh;
          transition: background 0.3s ease, color 0.3s ease;
        }

        /* Light theme */
        html:not([data-theme='dark']) body { background: #fff8f0; }
        html:not([data-theme='dark']) .sidebar { background: #1a0a00; }
        html:not([data-theme='dark']) .main { background: #fff8f0; }
        html:not([data-theme='dark']) .stat-box { background: #fff; border-color: rgba(255,79,0,0.12); }
        html:not([data-theme='dark']) .stat-num { color: #1a0a00; }
        html:not([data-theme='dark']) .stat-label { color: #8b6a44; }
        html:not([data-theme='dark']) .section-title { color: #1a0a00; }
        html:not([data-theme='dark']) .topbar-left h1 { color: #1a0a00; }
        html:not([data-theme='dark']) .topbar-left p { color: #8b6a44; }
        html:not([data-theme='dark']) .order-card { background: #fff; border-color: rgba(255,79,0,0.12); }
        html:not([data-theme='dark']) .order-id { color: #1a0a00; }
        html:not([data-theme='dark']) .order-time { color: #8b6a44; }
        html:not([data-theme='dark']) .card-header { border-bottom-color: rgba(0,0,0,0.06); }
        html:not([data-theme='dark']) .card-section + .card-section { border-left-color: rgba(0,0,0,0.06); }
        html:not([data-theme='dark']) .card-actions { border-top-color: rgba(0,0,0,0.06); }
        html:not([data-theme='dark']) .field-label { color: #8b6a44; }
        html:not([data-theme='dark']) .field-value { color: #3d2600; }
        html:not([data-theme='dark']) .field-value.highlight { color: var(--orange); }
        html:not([data-theme='dark']) .inp { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); color: #1a0a00; }
        html:not([data-theme='dark']) .sel { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); color: #1a0a00; }
        html:not([data-theme='dark']) .sel option { background: #fff; }
        html:not([data-theme='dark']) .empty-state { background: #fff; border-color: rgba(255,79,0,0.08); }
        html:not([data-theme='dark']) .empty-state h3 { color: #1a0a00; }
        html:not([data-theme='dark']) .empty-state p { color: #8b6a44; }
        html:not([data-theme='dark']) .btn-locate { background: rgba(0,0,0,0.05); border-color: rgba(0,0,0,0.1); color: #3d2600; }
        html:not([data-theme='dark']) .time-badge { background: rgba(255,79,0,0.08); border-color: rgba(255,79,0,0.2); }

        /* Dark theme */
        [data-theme='dark'] body { background: #0f0500; }
        [data-theme='dark'] .sidebar { background: #1a0a00; }
        [data-theme='dark'] .main { background: #0f0500; }
        [data-theme='dark'] .stat-box { background: #1a0a00; }
        [data-theme='dark'] .stat-num { color: #fff; }
        [data-theme='dark'] .stat-label { color: #c9a07d; }
        [data-theme='dark'] .section-title { color: #fff; }
        [data-theme='dark'] .topbar-left h1 { color: #fff; }
        [data-theme='dark'] .topbar-left p { color: #c9a07d; }
        [data-theme='dark'] .order-card { background: #1a0a00; border-color: rgba(255,255,255,0.08); }
        [data-theme='dark'] .order-id { color: #fff; }
        [data-theme='dark'] .order-time { color: #c9a07d; }
        [data-theme='dark'] .field-value { color: #e8d5c0; }
        [data-theme='dark'] .inp { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #fff; }
        [data-theme='dark'] .sel { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #fff; }
        [data-theme='dark'] .sel option { background: #1a0a00; }
        [data-theme='dark'] .empty-state { background: #1a0a00; border-color: rgba(255,255,255,0.06); }
        [data-theme='dark'] .empty-state h3 { color: #fff; }
        [data-theme='dark'] .empty-state p { color: #8b6a44; }

        /* ── Sidebar ── */
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #1a0a00; padding: 28px 20px; display: flex; flex-direction: column; gap: 8px; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; overflow-y: auto; }
        .sidebar-logo { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--orange); margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 10px; }
        .rider-card { background: rgba(255,79,0,0.1); border: 1px solid rgba(255,79,0,0.2); border-radius: 16px; padding: 16px; margin-bottom: 16px; }
        .rider-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--orange), #ff2400); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
        .rider-name { font-weight: 700; color: #fff; font-size: 0.95rem; }
        .rider-role { color: var(--orange); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 14px; text-decoration: none; color: #c9a07d; font-weight: 600; transition: all .2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,79,0,0.15); color: var(--orange); }
        .nav-item .nav-icon { font-size: 1.2rem; width: 24px; text-align: center; }
        .nav-badge { margin-left: auto; background: var(--orange); color: #fff; border-radius: 999px; font-size: 0.75rem; font-weight: 800; padding: 2px 8px; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.07); }

        /* ── Main ── */
        .main { margin-left: 260px; padding: 32px; flex: 1; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
        .topbar-left h1 { font-family: 'Syne', sans-serif; font-size: 1.9rem; color: #fff; margin: 0 0 4px; }
        .topbar-left p { color: #c9a07d; margin: 0; font-size: 0.9rem; }
        .time-badge { background: rgba(255,79,0,0.15); border: 1px solid rgba(255,79,0,0.3); color: var(--orange); padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 0.9rem; }

        /* ── Flash ── */
        .flash { padding: 14px 20px; border-radius: 14px; font-weight: 700; margin-bottom: 20px; }
        .flash-success { background: rgba(52,199,89,0.15); color: #2ecc71; border: 1px solid rgba(52,199,89,0.3); }
        .flash-error   { background: rgba(255,59,48,0.12); color: #ff6b6b; border: 1px solid rgba(255,59,48,0.3); }

        /* ── Stats ── */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-box { background: #1a0a00; border: 1px solid rgba(255,79,0,0.15); border-radius: 20px; padding: 22px 24px; position: relative; overflow: hidden; }
        .stat-box::before { content: ''; position: absolute; top: 0; right: 0; width: 80px; height: 80px; border-radius: 50%; background: rgba(255,79,0,0.07); transform: translate(20px,-20px); }
        .stat-emoji { font-size: 1.8rem; margin-bottom: 12px; }
        .stat-num { font-family: 'Syne', sans-serif; font-size: 2.4rem; font-weight: 800; color: #fff; line-height: 1; }
        .stat-label { color: #c9a07d; font-size: 0.88rem; margin-top: 6px; font-weight: 600; }

        /* ── Orders ── */
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .section-title { font-family: 'Syne', sans-serif; font-size: 1.2rem; color: #fff; margin: 0; }
        .refresh-btn { background: rgba(255,79,0,0.15); border: 1px solid rgba(255,79,0,0.3); color: var(--orange); padding: 8px 16px; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 0.85rem; text-decoration: none; }
        .refresh-btn:hover { background: rgba(255,79,0,0.25); }

        .orders-grid { display: grid; gap: 18px; }
        .order-card { background: #1a0a00; border: 1px solid rgba(255,255,255,0.08); border-radius: 22px; overflow: hidden; transition: border-color .2s; }
        .order-card:hover { border-color: rgba(255,79,0,0.4); }
        .order-card.priority { border-color: rgba(255,79,0,0.5); box-shadow: 0 0 0 2px rgba(255,79,0,0.15); }

        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.06); gap: 12px; flex-wrap: wrap; }
        .order-id { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 800; color: #fff; }
        .order-time { color: #c9a07d; font-size: 0.85rem; margin-top: 3px; }

        .status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .s-pending        { background: rgba(255,184,48,0.15); color: #f0b429; }
        .s-confirmed      { background: rgba(0,122,255,0.12); color: #5ac8fa; }
        .s-preparing      { background: rgba(175,82,222,0.15); color: #bf5af2; }
        .s-out_for_delivery { background: rgba(255,79,0,0.15); color: var(--orange); animation: pulse-border 2s infinite; }
        .s-delivered      { background: rgba(52,199,89,0.15); color: #30d158; }

        @keyframes pulse-border { 0%,100%{opacity:1} 50%{opacity:0.7} }

        .card-body { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .card-section { padding: 18px 22px; }
        .card-section + .card-section { border-left: 1px solid rgba(255,255,255,0.06); }
        .field-label { color: #8b6a44; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .field-value { color: #e8d5c0; font-size: 0.92rem; font-weight: 600; line-height: 1.5; }
        .field-value.highlight { color: var(--orange); font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800; }

        .card-actions { padding: 16px 22px; border-top: 1px solid rgba(255,255,255,0.06); display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .action-form { display: contents; }

        .form-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 0 22px 16px; }
        .inp { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 10px 14px; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; width: 100%; }
        .inp:focus { outline: none; border-color: var(--orange); background: rgba(255,79,0,0.05); }
        .inp::placeholder { color: #8b6a44; }
        .inp-label { color: #8b6a44; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block; }
        .sel { width: 100%; padding: 10px 14px; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; }
        .sel option { background: #1a0a00; }

        .btn-save { background: var(--orange); color: #fff; border: none; padding: 12px; border-radius: 14px; font-weight: 800; font-size: 0.9rem; cursor: pointer; width: 100%; font-family: 'DM Sans', sans-serif; transition: opacity .2s; }
        .btn-save:hover { opacity: 0.88; }
        .btn-locate { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); color: #e8d5c0; padding: 12px; border-radius: 14px; font-weight: 700; font-size: 0.85rem; cursor: pointer; width: 100%; font-family: 'DM Sans', sans-serif; transition: all .2s; }
        .btn-locate:hover { background: rgba(255,79,0,0.12); color: var(--orange); }

        .map-wrap { padding: 0 22px 18px; }
        .map-wrap iframe { width: 100%; height: 200px; border: 0; border-radius: 14px; }

        .empty-state { background: #1a0a00; border: 1px solid rgba(255,255,255,0.06); border-radius: 22px; padding: 60px 24px; text-align: center; }
        .empty-state .big-icon { font-size: 3rem; margin-bottom: 14px; }
        .empty-state h3 { font-family: 'Syne', sans-serif; color: #fff; font-size: 1.4rem; margin: 0 0 8px; }
        .empty-state p { color: #8b6a44; margin: 0; }

        @media (max-width: 900px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 20px; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .card-body { grid-template-columns: 1fr; }
            .card-section + .card-section { border-left: none; border-top: 1px solid rgba(255,255,255,0.06); }
            .form-inline { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
            .card-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            🛵 SwiftBite
            <!-- Theme Toggle Button -->
            <button id="theme-toggle" class="theme-toggle-btn" style="margin-left:auto;width:36px;height:36px;font-size:0.95rem;border:1.5px solid rgba(255,79,0,0.35);background:rgba(255,79,0,0.12);" title="Toggle theme" aria-label="Toggle dark/light mode">
              <span class="theme-icon theme-icon-sun">&#9728;</span>
              <span class="theme-icon theme-icon-moon">&#127769;</span>
            </button>
        </div>

        <div class="rider-card">
            <div class="rider-avatar"><?php echo strtoupper(substr($rider['name'], 0, 1)); ?></div>
            <div class="rider-name"><?php echo htmlspecialchars($rider['name']); ?></div>
            <div class="rider-role">🟢 Delivery Partner</div>
        </div>

        <a class="nav-item active" href="dashboard.php">
            <span class="nav-icon">📦</span> Active Orders
            <?php if ($stats['active'] > 0): ?>
                <span class="nav-badge"><?php echo $stats['active']; ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item" href="history.php">
            <span class="nav-icon">📋</span> Delivery History
        </a>

        <div class="sidebar-footer">
            <a class="nav-item" href="../auth/logout.php">
                <span class="nav-icon">🚪</span> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Active Deliveries</h1>
                <p>Manage orders, update status & share your live location</p>
            </div>
            <div class="time-badge" id="live-time">--:--</div>
        </div>

        <?php if ($flash_success): ?>
            <div class="flash flash-success">✅ <?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div class="flash flash-error">❌ <?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-emoji">🟡</div>
                <div class="stat-num"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active Orders Today</div>
            </div>
            <div class="stat-box">
                <div class="stat-emoji">🛵</div>
                <div class="stat-num"><?php echo $stats['transit']; ?></div>
                <div class="stat-label">Currently in Transit</div>
            </div>
            <div class="stat-box">
                <div class="stat-emoji">✅</div>
                <div class="stat-num"><?php echo $stats['delivered']; ?></div>
                <div class="stat-label">Delivered Today</div>
            </div>
        </div>

        <!-- Orders -->
        <div class="section-head">
            <h2 class="section-title">📦 Orders Queue</h2>
            <a class="refresh-btn" href="dashboard.php">🔄 Refresh</a>
        </div>

        <div class="orders-grid">
            <?php if (empty($activeOrders)): ?>
                <div class="empty-state">
                    <div class="big-icon">🎉</div>
                    <h3>All Clear!</h3>
                    <p>No active orders right now. Check back soon.</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeOrders as $order):
                    $isPriority = $order['status'] === 'out_for_delivery';
                    $statusClass = 's-' . $order['status'];
                    $statusLabels = [
                        'pending'          => ['⏳', 'Pending'],
                        'confirmed'        => ['👍', 'Confirmed'],
                        'preparing'        => ['🧑‍🍳', 'Preparing'],
                        'out_for_delivery' => ['🛵', 'Out for Delivery'],
                    ];
                    [$sIcon, $sLabel] = $statusLabels[$order['status']] ?? ['📦', ucfirst($order['status'])];
                ?>
                <div class="order-card <?php echo $isPriority ? 'priority' : ''; ?>">

                    <!-- Card Header -->
                    <div class="card-header">
                        <div>
                            <div class="order-id">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                            <div class="order-time"><?php echo date('M d • h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="status-pill <?php echo $statusClass; ?>"><?php echo $sIcon . ' ' . $sLabel; ?></div>
                    </div>

                    <!-- Rider + Status form inline fields -->
                    <form action="../actions/update_order_status.php" method="POST">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">

                        <div class="form-inline">
                            <div>
                                <label class="inp-label">Your Name</label>
                                <input class="inp" type="text" name="delivery_partner_name"
                                    value="<?php echo htmlspecialchars($order['delivery_partner_name'] ?? $rider['name']); ?>"
                                    placeholder="Rider name" required>
                            </div>
                            <div>
                                <label class="inp-label">Your Phone</label>
                                <input class="inp" type="text" name="delivery_partner_phone"
                                    value="<?php echo htmlspecialchars($order['delivery_partner_phone'] ?? $rider['phone'] ?? ''); ?>"
                                    placeholder="98XXXXXXXX" required>
                            </div>
                            <div>
                                <label class="inp-label">Update Status</label>
                                <select class="sel" name="status" required>
                                    <?php foreach (['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $s)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex;align-items:flex-end;">
                                <button type="submit" class="btn-save">💾 Save Status</button>
                            </div>
                        </div>
                    </form>

                    <!-- Customer & Order Info -->
                    <div class="card-body">
                        <div class="card-section">
                            <div class="field-label">Customer</div>
                            <div class="field-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>

                            <div class="field-label" style="margin-top:12px;">Phone</div>
                            <div class="field-value">
                                <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>"
                                   style="color:var(--orange); text-decoration:none;">
                                    📞 <?php echo htmlspecialchars($order['customer_phone']); ?>
                                </a>
                            </div>

                            <div class="field-label" style="margin-top:12px;">Items (<?php echo $order['item_count']; ?>)</div>
                            <div class="field-value"><?php echo htmlspecialchars(mb_strimwidth($order['item_names'], 0, 60, '...')); ?></div>
                        </div>

                        <div class="card-section">
                            <div class="field-label">Delivery Address</div>
                            <div class="field-value">
                                <?php echo htmlspecialchars($order['delivery_address']); ?>,
                                <?php echo htmlspecialchars($order['delivery_city']); ?>
                            </div>

                            <div class="field-label" style="margin-top:12px;">Payment</div>
                            <div class="field-value"><?php echo strtoupper(htmlspecialchars($order['payment_method'])); ?></div>

                            <div class="field-label" style="margin-top:12px;">Order Total</div>
                            <div class="field-value highlight">Rs. <?php echo number_format((float)$order['total'], 2); ?></div>
                        </div>
                    </div>

                    <!-- Live Location Update -->
                    <form action="../actions/update_delivery_location.php" method="POST" class="location-form">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                        <div class="form-inline">
                            <div>
                                <label class="inp-label">Latitude</label>
                                <input class="inp" type="text" name="delivery_lat"
                                    value="<?php echo htmlspecialchars((string)($order['delivery_lat'] ?? '')); ?>"
                                    placeholder="27.7172">
                            </div>
                            <div>
                                <label class="inp-label">Longitude</label>
                                <input class="inp" type="text" name="delivery_lng"
                                    value="<?php echo htmlspecialchars((string)($order['delivery_lng'] ?? '')); ?>"
                                    placeholder="85.3240">
                            </div>
                            <div>
                                <button type="button" class="btn-locate">📍 Use My GPS</button>
                            </div>
                            <div>
                                <button type="submit" class="btn-save" style="background:rgba(255,79,0,0.7);">📡 Update Location</button>
                            </div>
                        </div>
                    </form>

                    <!-- Map -->
                    <?php if (!empty($order['delivery_lat']) && !empty($order['delivery_lng'])): ?>
                    <div class="map-wrap">
                        <div style="color:#8b6a44;font-size:0.8rem;margin-bottom:8px;">
                            📍 Last updated: <?php echo !empty($order['location_updated_at']) ? date('h:i A', strtotime($order['location_updated_at'])) : 'N/A'; ?>
                        </div>
                        <iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.openstreetmap.org/export/embed.html?bbox=<?php
                                echo urlencode((string)((float)$order['delivery_lng'] - 0.01));
                            ?>%2C<?php echo urlencode((string)((float)$order['delivery_lat'] - 0.01));
                            ?>%2C<?php echo urlencode((string)((float)$order['delivery_lng'] + 0.01));
                            ?>%2C<?php echo urlencode((string)((float)$order['delivery_lat'] + 0.01));
                            ?>&layer=mapnik&marker=<?php
                                echo urlencode((string)$order['delivery_lat']);
                            ?>%2C<?php echo urlencode((string)$order['delivery_lng']); ?>">
                        </iframe>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Live clock
function updateClock() {
    const now = new Date();
    document.getElementById('live-time').textContent =
        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// GPS location
document.querySelectorAll('.location-form').forEach(function(form) {
    form.querySelector('.btn-locate').addEventListener('click', function() {
        if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
        const btn = this;
        btn.textContent = '📡 Getting GPS...';
        btn.disabled = true;
        navigator.geolocation.getCurrentPosition(function(pos) {
            form.querySelector('[name="delivery_lat"]').value = pos.coords.latitude.toFixed(7);
            form.querySelector('[name="delivery_lng"]').value = pos.coords.longitude.toFixed(7);
            btn.textContent = '✅ GPS Captured';
            btn.disabled = false;
        }, function() {
            alert('Unable to get location. Please allow GPS permission.');
            btn.textContent = '📍 Use My GPS';
            btn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    });
});

// Auto-refresh every 60 seconds
const refreshTimeout = setTimeout(() => location.reload(), 60000);

// ── Real-time Auto Tracking ──
(function() {
    const activeOrdersInTransit = <?php 
        $transitIds = array_map(fn($o) => $o['id'], array_filter($activeOrders, fn($o) => $o['status'] === 'out_for_delivery'));
        echo json_encode($transitIds); 
    ?>;

    if (activeOrdersInTransit.length === 0) return;

    console.log("🛵 Auto-tracking active for orders:", activeOrdersInTransit);
    
    // Stop auto-refresh if tracking is active to prevent jitter
    clearTimeout(refreshTimeout);

    let watchId = null;
    const CSRF_TOKEN = '<?php echo generateCsrfToken(); ?>';

    function sendLocation(lat, lng) {
        activeOrdersInTransit.forEach(orderId => {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('delivery_lat', lat);
            formData.append('delivery_lng', lng);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('../actions/update_delivery_location.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(response => {
                console.log(`📡 Location updated for #${orderId}`);
            }).catch(err => console.error("Tracking Error:", err));
        });
    }

    if ("geolocation" in navigator) {
        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;
                sendLocation(latitude.toFixed(7), longitude.toFixed(7));
                
                // Update UI fields if they exist
                activeOrdersInTransit.forEach(id => {
                    const form = document.querySelector(`.location-form input[name="order_id"][value="${id}"]`)?.closest('form');
                    if (form) {
                        form.querySelector('[name="delivery_lat"]').value = latitude.toFixed(7);
                        form.querySelector('[name="delivery_lng"]').value = longitude.toFixed(7);
                        const btn = form.querySelector('.btn-locate');
                        if (btn) btn.textContent = '🟢 Auto-tracking...';
                    }
                });
            },
            (err) => console.warn("WatchPosition Error:", err),
            { enableHighAccuracy: true, maximumAge: 10000 }
        );
    }
})();
</script>
</body>
</html>
