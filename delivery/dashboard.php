<?php
require_once '../core/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Only delivery_partner role allowed
$roleStmt = $pdo->prepare("SELECT role, name, email, phone, availability_status FROM users WHERE id = ? LIMIT 1");
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
    COUNT(CASE WHEN status IN ('pending','confirmed','preparing','out_for_delivery') THEN 1 END) AS active_count,
    COUNT(CASE WHEN status = 'out_for_delivery' THEN 1 END) AS in_transit,
    COUNT(CASE WHEN status = 'delivered' AND DATE(updated_at) = CURDATE() THEN 1 END) AS delivered_today
    FROM orders")->fetch(PDO::FETCH_ASSOC);

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
        .btn-simulate { background: linear-gradient(135deg, #6c47ff, #a78bfa); border: none; color: #fff; padding: 12px; border-radius: 14px; font-weight: 800; font-size: 0.85rem; cursor: pointer; width: 100%; font-family: 'DM Sans', sans-serif; transition: all .2s; }
        .btn-simulate:hover { opacity: 0.88; }
        .btn-simulate:disabled { opacity: 0.5; cursor: not-allowed; }
        .sim-progress-wrap { padding: 0 22px 14px; display: none; }
        .sim-progress-bar-bg { height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden; margin-bottom: 6px; }
        .sim-progress-bar-fill { height: 100%; background: linear-gradient(90deg, #6c47ff, #a78bfa); width: 0%; transition: width 0.4s ease; border-radius: 3px; }
        .sim-status-text { font-size: 0.78rem; color: #a78bfa; font-weight: 600; }

        /* Live Tracking Banner */
        .live-tracking-banner {
            margin: 0 22px 14px;
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid;
            transition: all 0.3s;
        }
        .live-tracking-banner.idle    { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
        .live-tracking-banner.active  { background: rgba(52,199,89,0.1);  border-color: rgba(52,199,89,0.35); }
        .live-tracking-banner.error   { background: rgba(255,59,48,0.1);  border-color: rgba(255,59,48,0.35); }
        .tracking-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; background: #8b6a44; }
        .tracking-dot.green { background: #34c759; animation: blink 1.2s infinite; }
        .tracking-dot.red   { background: #ff3b30; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
        .tracking-info { flex: 1; }
        .tracking-title { font-weight: 800; font-size: 0.88rem; color: #fff; }
        .tracking-sub   { font-size: 0.75rem; color: #8b6a44; margin-top: 2px; }
        .btn-toggle-tracking {
            padding: 8px 16px; border-radius: 12px; border: none;
            font-weight: 800; font-size: 0.8rem; cursor: pointer;
            font-family: 'DM Sans', sans-serif; transition: all .2s; white-space: nowrap;
        }
        .btn-toggle-tracking.start { background: #34c759; color: #fff; }
        .btn-toggle-tracking.stop  { background: rgba(255,59,48,0.2); color: #ff3b30; border: 1px solid rgba(255,59,48,0.4); }

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
            <div class="rider-status-wrap" style="margin-top: 8px;">
                <?php $isOnline = ($rider['availability_status'] === 'online'); ?>
                <div id="status-indicator" class="rider-role" style="color: <?php echo $isOnline ? '#2ecc71' : '#8b6a44'; ?>;">
                    <?php echo $isOnline ? '🟢 Online' : '⚪ Offline'; ?>
                </div>
                
                <!-- Status Toggle Switch -->
                <div style="margin-top: 12px; display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 12px;">
                    <span style="font-size: 0.75rem; font-weight: 800; color: #fff; text-transform: uppercase;">Work Mode</span>
                    <label class="sb-toggle" style="position: relative; display: inline-block; width: 44px; height: 24px; cursor: pointer;">
                        <input type="checkbox" id="availability-toggle" <?php echo $isOnline ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; inset: 0; background: #3d2600; transition: .3s; border-radius: 24px;"></span>
                        <span style="position: absolute; left: 4px; bottom: 4px; background: #fff; width: 16px; height: 16px; transition: .3s; border-radius: 50%;" id="toggle-circle"></span>
                    </label>
                </div>
            </div>
        </div>

        <style>
            #availability-toggle:checked + span { background: var(--orange); }
            #availability-toggle:checked + span + #toggle-circle { transform: translateX(20px); }
            /* Direct sibling selector fix for the circle */
            #availability-toggle:checked ~ #toggle-circle { transform: translateX(20px); }
        </style>

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

                    <!-- Live GPS Tracking Banner -->
                    <?php if ($order['status'] === 'out_for_delivery'): ?>
                    <div class="live-tracking-banner idle" id="track-banner-<?php echo (int)$order['id']; ?>">
                        <div class="tracking-dot" id="track-dot-<?php echo (int)$order['id']; ?>"></div>
                        <div class="tracking-info">
                            <div class="tracking-title" id="track-title-<?php echo (int)$order['id']; ?>">📡 Live GPS Tracking</div>
                            <div class="tracking-sub"  id="track-sub-<?php echo (int)$order['id']; ?>">Tap Start to begin sending your location automatically</div>
                        </div>
                        <button type="button" class="btn-toggle-tracking start"
                            id="track-btn-<?php echo (int)$order['id']; ?>"
                            data-order-id="<?php echo (int)$order['id']; ?>">
                            ▶ Start
                        </button>
                    </div>
                    <?php endif; ?>

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
                            <?php if ($order['status'] === 'out_for_delivery'): ?>
                            <div style="grid-column:1/-1;">
                                <button type="button" class="btn-simulate"
                                    data-order-id="<?php echo (int)$order['id']; ?>"
                                    data-start-lat="<?php echo (float)($order['delivery_lat'] ?: 27.7172); ?>"
                                    data-start-lng="<?php echo (float)($order['delivery_lng'] ?: 85.3240); ?>">
                                    🎬 Simulate Delivery
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Simulation Progress Bar -->
                    <div class="sim-progress-wrap" id="sim-wrap-<?php echo (int)$order['id']; ?>">
                        <div class="sim-progress-bar-bg"><div class="sim-progress-bar-fill" id="sim-bar-<?php echo (int)$order['id']; ?>"></div></div>
                        <div class="sim-status-text" id="sim-text-<?php echo (int)$order['id']; ?>">🎬 Starting simulation...</div>
                    </div>

                    <!-- Mini Map (Leaflet) -->
                    <div class="map-wrap" id="minimap-wrap-<?php echo (int)$order['id']; ?>">
                        <div style="color:#8b6a44;font-size:0.8rem;margin-bottom:8px;">
                            📍 Last updated: <?php echo !empty($order['location_updated_at']) ? date('h:i A', strtotime($order['location_updated_at'])) : 'N/A'; ?>
                        </div>
                        <div id="minimap-<?php echo (int)$order['id']; ?>" style="width:100%;height:200px;border-radius:14px;overflow:hidden;"></div>
                    </div>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Leaflet for mini-maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ── Live Clock ──
function updateClock() {
    const now = new Date();
    document.getElementById('live-time').textContent =
        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000); updateClock();

// ── GPS Button ──
document.querySelectorAll('.location-form').forEach(function(form) {
    form.querySelector('.btn-locate').addEventListener('click', function() {
        if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
        const btn = this;
        btn.textContent = '📡 Getting GPS...'; btn.disabled = true;
        navigator.geolocation.getCurrentPosition(function(pos) {
            form.querySelector('[name="delivery_lat"]').value = pos.coords.latitude.toFixed(7);
            form.querySelector('[name="delivery_lng"]').value = pos.coords.longitude.toFixed(7);
            btn.textContent = '✅ GPS Captured'; btn.disabled = false;
        }, function() {
            alert('Unable to get location.'); btn.textContent = '📍 Use My GPS'; btn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    });
});

// ── Auto-refresh (stops if simulation is running) ──
let refreshTimeout = setTimeout(() => location.reload(), 60000);

// ═══════════════════════════════════════════════════
// 🎬 SIMULATION ENGINE
// ═══════════════════════════════════════════════════
const activeSimulations = {}; // orderId → interval handle

// Initialize Leaflet mini-maps for each order card
const miniMaps = {};

<?php foreach ($activeOrders as $order):
    $startLat = (float)($order['delivery_lat'] ?: 27.7172);
    $startLng = (float)($order['delivery_lng'] ?: 85.3240);
?>
(function() {
    const orderId  = <?php echo (int)$order['id']; ?>;
    const startLat = <?php echo $startLat; ?>;
    const startLng = <?php echo $startLng; ?>;
    const destLat  = startLat + 0.012;
    const destLng  = startLng - 0.008;

    // ── Init mini Leaflet map ──
    const mapEl = document.getElementById('minimap-' + orderId);
    if (!mapEl) return;

    const miniMap = L.map('minimap-' + orderId, { zoomControl: false, attributionControl: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);

    const riderIcon = L.divIcon({
        className: '',
        html: '<div style="width:34px;height:34px;background:#ff4f00;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;box-shadow:0 2px 8px rgba(255,79,0,0.5);">🛵</div>',
        iconSize: [34, 34], iconAnchor: [17, 17]
    });
    const destIcon = L.divIcon({
        className: '',
        html: '<div style="width:30px;height:30px;background:#007aff;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.9rem;box-shadow:0 2px 8px rgba(0,122,255,0.5);">📍</div>',
        iconSize: [30, 30], iconAnchor: [15, 30]
    });

    const riderMarker = L.marker([startLat, startLng], { icon: riderIcon }).addTo(miniMap);
    L.marker([destLat, destLng], { icon: destIcon }).addTo(miniMap);
    miniMap.setView([startLat, startLng], 14);
    miniMaps[orderId] = { map: miniMap, marker: riderMarker };

    // ── Simulate button click ──
    const simBtn = document.querySelector(`.btn-simulate[data-order-id="${orderId}"]`);
    if (!simBtn) return;

    simBtn.addEventListener('click', async function() {
        if (activeSimulations[orderId]) {
            // Stop
            clearInterval(activeSimulations[orderId]);
            delete activeSimulations[orderId];
            simBtn.textContent = '🎬 Simulate Delivery';
            simBtn.style.background = 'linear-gradient(135deg,#6c47ff,#a78bfa)';
            document.getElementById('sim-wrap-' + orderId).style.display = 'none';
            return;
        }

        simBtn.disabled = true;
        simBtn.textContent = '⏳ Loading route...';

        // Stop page auto-refresh during simulation
        clearTimeout(refreshTimeout);

        // 1. Fetch road route from OSRM
        let routePoints = [];
        try {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${destLng},${destLat}?overview=full&geometries=geojson`;
            const res  = await fetch(osrmUrl);
            const data = await res.json();
            if (data.routes && data.routes[0]) {
                routePoints = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
            }
        } catch(e) { console.warn('OSRM failed, using straight line'); }

        // Fallback: generate interpolated points between start and dest
        if (routePoints.length < 2) {
            const steps = 30;
            for (let i = 0; i <= steps; i++) {
                routePoints.push([
                    startLat + (destLat - startLat) * (i / steps),
                    startLng + (destLng - startLng) * (i / steps)
                ]);
            }
        }

        // 2. Draw full route on mini map
        L.polyline(routePoints, { color: '#ff4f00', weight: 3, opacity: 0.7 }).addTo(miniMaps[orderId].map);
        miniMaps[orderId].map.fitBounds(L.latLngBounds(routePoints), { padding: [20, 20] });

        // 3. Show progress UI
        const simWrap = document.getElementById('sim-wrap-' + orderId);
        const simBar  = document.getElementById('sim-bar-' + orderId);
        const simText = document.getElementById('sim-text-' + orderId);
        simWrap.style.display = 'block';
        simBtn.disabled = false;
        simBtn.textContent = '⏹ Stop Simulation';
        simBtn.style.background = 'linear-gradient(135deg,#ff4f00,#ff7340)';

        // 4. Walk along route points
        const total = routePoints.length;
        let   step  = 0;

        async function sendStep() {
            if (step >= total) {
                clearInterval(activeSimulations[orderId]);
                delete activeSimulations[orderId];
                simBtn.textContent  = '✅ Delivered!';
                simBtn.disabled     = true;
                simBar.style.width  = '100%';
                simText.textContent = '✅ Simulation complete — rider arrived!';
                return;
            }

            const [lat, lng] = routePoints[step];

            // Update marker on mini map
            miniMaps[orderId].marker.setLatLng([lat, lng]);
            miniMaps[orderId].map.panTo([lat, lng]);

            // Update lat/lng input fields
            const latInput = document.querySelector(`.location-form input[name="order_id"][value="${orderId}"]`)
                             ?.closest('form')?.querySelector('[name="delivery_lat"]');
            const lngInput = document.querySelector(`.location-form input[name="order_id"][value="${orderId}"]`)
                             ?.closest('form')?.querySelector('[name="delivery_lng"]');
            if (latInput) latInput.value = lat.toFixed(7);
            if (lngInput) lngInput.value = lng.toFixed(7);

            // POST to DB (AJAX, no reload)
            try {
                const fd = new FormData();
                fd.append('order_id', orderId);
                fd.append('lat', lat.toFixed(7));
                fd.append('lng', lng.toFixed(7));
                await fetch('../actions/update_location_ajax.php', { method: 'POST', body: fd });
            } catch(e) {}

            // Update progress bar
            const pct = Math.round((step / (total - 1)) * 100);
            simBar.style.width  = pct + '%';
            simText.textContent = `🛵 Simulating... ${pct}% (${step + 1}/${total} points)`;
            step++;
        }

        activeSimulations[orderId] = setInterval(sendStep, 2000); // move every 2 seconds
        sendStep(); // fire immediately for first point
    });
})();
<?php endforeach; ?>

// ══════════════════════════════════════════════
// 📡 REAL GPS LIVE TRACKING ENGINE
// ══════════════════════════════════════════════
const gpsWatchIds = {}; // orderId → watchPosition ID

document.querySelectorAll('.btn-toggle-tracking').forEach(btn => {
    btn.addEventListener('click', function() {
        const orderId = this.dataset.orderId;

        // ── STOP tracking ──
        if (gpsWatchIds[orderId]) {
            navigator.geolocation.clearWatch(gpsWatchIds[orderId]);
            delete gpsWatchIds[orderId];
            setTrackingState(orderId, 'idle', '📡 Live GPS Tracking', 'Tracking stopped. Tap Start to resume.');
            btn.textContent = '▶ Start';
            btn.className = 'btn-toggle-tracking start';
            return;
        }

        // ── Check GPS support ──
        if (!navigator.geolocation) {
            setTrackingState(orderId, 'error', '❌ GPS Not Supported',
                'Your browser does not support geolocation. Use Chrome or Firefox on a real device.');
            return;
        }

        // ── START tracking ──
        setTrackingState(orderId, 'idle', '⏳ Requesting GPS permission...', 'Please allow location access in your browser.');
        btn.textContent = '...';
        btn.disabled = true;

        const watchId = navigator.geolocation.watchPosition(
            // ✅ SUCCESS — position received
            async function(pos) {
                const lat = pos.coords.latitude.toFixed(7);
                const lng = pos.coords.longitude.toFixed(7);
                const acc = Math.round(pos.coords.accuracy);

                // Update input fields
                const form = document.querySelector(`.location-form input[name="order_id"][value="${orderId}"]`)?.closest('form');
                if (form) {
                    form.querySelector('[name="delivery_lat"]').value = lat;
                    form.querySelector('[name="delivery_lng"]').value = lng;
                }

                // Update banner to active
                setTrackingState(orderId, 'active',
                    '🟢 Live Tracking Active',
                    `📍 ${lat}, ${lng} · Accuracy: ±${acc}m · Sending to server...`
                );

                // Enable stop button
                btn.textContent = '⏹ Stop';
                btn.className = 'btn-toggle-tracking stop';
                btn.disabled = false;

                // Send to server via AJAX
                try {
                    const fd = new FormData();
                    fd.append('order_id', orderId);
                    fd.append('lat', lat);
                    fd.append('lng', lng);
                    const res  = await fetch('../actions/update_location_ajax.php', { method: 'POST', body: fd });
                    const data = await res.json();

                    const now = new Date().toLocaleTimeString();
                    if (data.success) {
                        document.getElementById('track-sub-' + orderId).textContent =
                            `✅ Sent at ${now} · ${lat}, ${lng} · ±${acc}m`;
                    } else {
                        document.getElementById('track-sub-' + orderId).textContent =
                            `⚠️ Server error at ${now}: ${data.message}`;
                    }
                } catch(e) {
                    document.getElementById('track-sub-' + orderId).textContent =
                        `⚠️ Network error — will retry on next movement`;
                }
            },

            // ❌ ERROR — GPS failed
            function(err) {
                btn.textContent = '▶ Start';
                btn.className = 'btn-toggle-tracking start';
                btn.disabled = false;
                delete gpsWatchIds[orderId];

                const msgs = {
                    1: '❌ Permission Denied — Please allow location access in your browser settings.',
                    2: '❌ GPS Unavailable — Make sure you are outdoors or GPS is enabled on your device.',
                    3: '❌ GPS Timeout — Your device took too long to get a location. Try again.',
                };
                setTrackingState(orderId, 'error', '❌ GPS Error', msgs[err.code] || '❌ Unknown GPS error.');
            },

            // Options
            {
                enableHighAccuracy: true,
                maximumAge: 5000,     // accept cached position up to 5s old
                timeout: 15000        // wait up to 15s for a fix
            }
        );

        gpsWatchIds[orderId] = watchId;
    });
});

function setTrackingState(orderId, state, title, sub) {
    const banner = document.getElementById('track-banner-' + orderId);
    if (!banner) return;
    banner.className = 'live-tracking-banner ' + state;
    document.getElementById('track-dot-' + orderId).className = 'tracking-dot ' + (state === 'active' ? 'green' : (state === 'error' ? 'red' : ''));
    document.getElementById('track-title-' + orderId).textContent = title;
    document.getElementById('track-sub-' + orderId).textContent = sub;
}

// ── Availability Toggle Logic ──
const availToggle = document.getElementById('availability-toggle');
const statusInd = document.getElementById('status-indicator');
const toggleCircle = document.getElementById('toggle-circle');

if (availToggle) {
    availToggle.addEventListener('change', async function() {
        const isOnline = this.checked;
        const newStatus = isOnline ? 'online' : 'offline';
        
        console.log('Switching to:', newStatus);

        // Optimistic UI update
        statusInd.textContent = isOnline ? '🟢 Online' : '⚪ Offline';
        statusInd.style.color = isOnline ? '#2ecc71' : '#8b6a44';
        if (toggleCircle) toggleCircle.style.transform = isOnline ? 'translateX(20px)' : 'translateX(0)';

        try {
            const fd = new FormData();
            fd.append('status', newStatus);
            fd.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
            
            const res = await fetch('<?php echo SITE_BASE_URL; ?>/actions/toggle_rider_status.php', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            console.log('Update result:', data);
            
            if (!data.success) {
                console.error('Update failed:', data.message);
                alert('Failed to update status: ' + data.message);
                // Revert UI
                this.checked = !isOnline;
                statusInd.textContent = !isOnline ? '🟢 Online' : '⚪ Offline';
                statusInd.style.color = !isOnline ? '#2ecc71' : '#8b6a44';
                if (toggleCircle) toggleCircle.style.transform = !isOnline ? 'translateX(20px)' : 'translateX(0)';
            }
        } catch (e) {
            console.error('Network Error:', e);
            alert('Network error. Status not saved.');
        }
    });
}
</script>
</body>
</html>
