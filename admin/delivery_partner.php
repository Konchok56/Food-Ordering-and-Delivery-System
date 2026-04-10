<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$currentRole = (string) $roleStmt->fetchColumn();

if (!in_array($currentRole, ['admin', 'delivery_partner'], true)) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied! Only admin can access this page.</h2>";
    exit;
}

$flashSuccess = $_SESSION['delivery_success'] ?? '';
$flashError = $_SESSION['delivery_error'] ?? '';
unset($_SESSION['delivery_success'], $_SESSION['delivery_error']);

$statusFilter = $_GET['status'] ?? 'active';
$allowedFilters = ['active', 'all', 'pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = 'active';
}

if ($statusFilter === 'active') {
    $ordersStmt = $pdo->query("SELECT * FROM orders WHERE status IN ('pending','confirmed','preparing','out_for_delivery') ORDER BY created_at DESC");
} elseif ($statusFilter === 'all') {
    $ordersStmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 50");
} else {
    $ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC LIMIT 50");
    $ordersStmt->execute([$statusFilter]);
}
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$countsStmt = $pdo->query("SELECT 
    COUNT(*) AS total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
    SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) AS delivery_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders
    FROM orders");
$counts = $countsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_orders' => 0,
    'pending_orders' => 0,
    'delivery_orders' => 0,
    'delivered_orders' => 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner Panel - SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css?v=6" />
    <style>
        .page { padding: 100px 24px 60px; min-height: 100vh; background: var(--cream); }
        .inner { max-width: 1200px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 18px; margin-bottom: 24px; }
        .topbar h1 { margin: 0 0 8px; font-family: 'Syne', sans-serif; font-size: 2.2rem; color: var(--dark); }
        .topbar p { margin: 0; color: var(--muted); }
        .back-links { display: flex; gap: 12px; flex-wrap: wrap; }
        .back-link { text-decoration: none; padding: 12px 18px; border-radius: 14px; background: #fff; color: var(--dark); font-weight: 700; box-shadow: var(--shadow); }
        .back-link:hover { background: var(--orange); color: #fff; }

        .flash { padding: 14px 18px; border-radius: 14px; font-weight: 700; margin-bottom: 18px; }
        .flash-success { background: rgba(52,199,89,0.14); color: #1a7a34; }
        .flash-error { background: rgba(255,59,48,0.12); color: #cc2d25; }

        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
        .stat { background: #fff; padding: 22px; border-radius: 24px; box-shadow: var(--shadow); }
        .stat-label { color: var(--muted); font-weight: 700; margin-bottom: 10px; }
        .stat-value { color: var(--dark); font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; }

        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .chip { text-decoration: none; padding: 10px 14px; border-radius: 999px; background: #fff; color: var(--dark); font-weight: 700; box-shadow: var(--shadow); }
        .chip.active { background: var(--orange); color: #fff; }

        .orders { display: grid; gap: 22px; }
        .order-card { background: #fff; border-radius: 28px; padding: 26px; box-shadow: var(--shadow); }
        .order-head { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; padding-bottom: 18px; border-bottom: 2px dashed var(--cream2); margin-bottom: 18px; }
        .order-id { color: var(--dark); font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; }
        .order-date { color: var(--muted); margin-top: 6px; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; font-size: 0.84rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: rgba(255,184,48,0.15); color: #d68c00; }
        .status-confirmed, .status-preparing { background: rgba(0,122,255,0.1); color: #007aff; }
        .status-out_for_delivery { background: rgba(255,79,0,0.12); color: var(--orange); }
        .status-delivered { background: rgba(52,199,89,0.14); color: #1a7a34; }
        .status-cancelled { background: rgba(255,59,48,0.1); color: var(--red); }

        .order-grid { display: grid; grid-template-columns: 1.1fr 1fr; gap: 18px; }
        .panel { background: var(--cream); border-radius: 22px; padding: 18px; }
        .panel h3 { margin: 0 0 14px; color: var(--dark); font-family: 'Syne', sans-serif; }
        .detail { margin-bottom: 10px; color: var(--text); line-height: 1.5; }
        .detail strong { color: var(--dark); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 700; color: var(--dark); }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; border-radius: 14px; border: 2px solid #eadcc9; background: #fff; font-family: 'DM Sans', sans-serif; }
        .btn-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
        .btn-main, .btn-ghost { border: none; cursor: pointer; padding: 12px 16px; border-radius: 14px; font-weight: 800; font-family: 'DM Sans', sans-serif; }
        .btn-main { background: var(--orange); color: #fff; }
        .btn-ghost { background: #fff; color: var(--dark); border: 2px solid #eadcc9; }
        .btn-main:hover, .btn-ghost:hover { transform: translateY(-1px); }
        .map-box iframe { width: 100%; height: 220px; border: 0; border-radius: 16px; }
        .small-note { color: var(--muted); font-size: 0.9rem; margin-top: 8px; }
        .empty { background: #fff; padding: 46px 24px; border-radius: 24px; text-align: center; box-shadow: var(--shadow); }

        @media (max-width: 900px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .order-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .stats, .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../sections/navbar.php'; ?>

    <div class="page">
        <div class="inner">
            <div class="topbar">
                <div>
                    <h1>Delivery Partner Panel</h1>
                    <p>Update order status, assign rider details, and push live location.</p>
                </div>
                <div class="back-links">
                    <a class="back-link" href="dashboard.php">Admin Dashboard</a>
                    <a class="back-link" href="../index.php">View Site</a>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="flash flash-success">✅ <?php echo htmlspecialchars($flashSuccess); ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="flash flash-error">❌ <?php echo htmlspecialchars($flashError); ?></div>
            <?php endif; ?>

            <div class="stats">
                <div class="stat"><div class="stat-label">Total Orders</div><div class="stat-value"><?php echo (int) $counts['total_orders']; ?></div></div>
                <div class="stat"><div class="stat-label">Pending</div><div class="stat-value"><?php echo (int) $counts['pending_orders']; ?></div></div>
                <div class="stat"><div class="stat-label">Out for Delivery</div><div class="stat-value"><?php echo (int) $counts['delivery_orders']; ?></div></div>
                <div class="stat"><div class="stat-label">Delivered</div><div class="stat-value"><?php echo (int) $counts['delivered_orders']; ?></div></div>
            </div>

            <div class="filters">
                <?php foreach ($allowedFilters as $filter): ?>
                    <a class="chip <?php echo $statusFilter === $filter ? 'active' : ''; ?>" href="?status=<?php echo urlencode($filter); ?>"><?php echo ucwords(str_replace('_', ' ', $filter)); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty">
                    <h3 style="font-family:'Syne',sans-serif; color:var(--dark); margin-bottom:8px;">No orders found</h3>
                    <p style="color:var(--muted);">Try a different filter or wait for a new order.</p>
                </div>
            <?php else: ?>
                <div class="orders">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-head">
                                <div>
                                    <div class="order-id">Order #<?php echo str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                    <div class="order-date"><?php echo date('M d, Y • h:i A', strtotime($order['created_at'])); ?></div>
                                </div>
                                <div class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $order['status'])); ?>
                                </div>
                            </div>

                            <div class="order-grid">
                                <div class="panel">
                                    <h3>Order & Customer Details</h3>
                                    <div class="detail"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="detail"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></div>
                                    <div class="detail"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                    <div class="detail"><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?>, <?php echo htmlspecialchars($order['delivery_city']); ?></div>
                                    <div class="detail"><strong>Payment:</strong> <?php echo strtoupper(htmlspecialchars($order['payment_method'])); ?></div>
                                    <div class="detail"><strong>Total:</strong> Rs. <?php echo number_format((float) $order['total'], 2); ?></div>
                                    <?php if (!empty($order['notes'])): ?>
                                        <div class="detail"><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div style="display:grid; gap:18px;">
                                    <div class="panel">
                                        <h3>Status Update</h3>
                                        <form action="../actions/update_order_status.php" method="POST">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">

                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Delivery Partner Name</label>
                                                    <input type="text" name="delivery_partner_name" value="<?php echo htmlspecialchars($order['delivery_partner_name'] ?? ''); ?>" placeholder="Rider name">
                                                </div>
                                                <div class="form-group">
                                                    <label>Delivery Partner Phone</label>
                                                    <input type="text" name="delivery_partner_phone" value="<?php echo htmlspecialchars($order['delivery_partner_phone'] ?? ''); ?>" placeholder="98XXXXXXXX">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Order Status</label>
                                                <select name="status" required>
                                                    <?php $statuses = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled']; ?>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                                            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="btn-row">
                                                <button type="submit" class="btn-main">Save Status</button>
                                                <a class="btn-ghost" href="../user/order_details.php?id=<?php echo (int) $order['id']; ?>" style="text-decoration:none; display:inline-flex; align-items:center;">View User Order Page</a>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="panel">
                                        <h3>Live Location Update</h3>
                                        <form action="../actions/update_delivery_location.php" method="POST" class="location-form">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">

                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Latitude</label>
                                                    <input type="text" name="delivery_lat" value="<?php echo htmlspecialchars((string) ($order['delivery_lat'] ?? '')); ?>" placeholder="27.7172">
                                                </div>
                                                <div class="form-group">
                                                    <label>Longitude</label>
                                                    <input type="text" name="delivery_lng" value="<?php echo htmlspecialchars((string) ($order['delivery_lng'] ?? '')); ?>" placeholder="85.3240">
                                                </div>
                                            </div>

                                            <div class="btn-row">
                                                <button type="button" class="btn-ghost btn-locate">Use My Current Location</button>
                                                <button type="submit" class="btn-main">Update Location</button>
                                            </div>
                                            <div class="small-note">Tip: set status to <strong>Out For Delivery</strong> when rider starts moving.</div>
                                        </form>

                                        <?php if (!empty($order['delivery_lat']) && !empty($order['delivery_lng'])): ?>
                                            <div class="map-box" style="margin-top:16px;">
                                                <iframe
                                                    loading="lazy"
                                                    referrerpolicy="no-referrer-when-downgrade"
                                                    src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo urlencode((string) ((float) $order['delivery_lng'] - 0.01)); ?>%2C<?php echo urlencode((string) ((float) $order['delivery_lat'] - 0.01)); ?>%2C<?php echo urlencode((string) ((float) $order['delivery_lng'] + 0.01)); ?>%2C<?php echo urlencode((string) ((float) $order['delivery_lat'] + 0.01)); ?>&layer=mapnik&marker=<?php echo urlencode((string) $order['delivery_lat']); ?>%2C<?php echo urlencode((string) $order['delivery_lng']); ?>"></iframe>
                                            </div>
                                            <div class="small-note">
                                                Last updated:
                                                <?php echo !empty($order['location_updated_at']) ? date('M d, Y h:i A', strtotime($order['location_updated_at'])) : 'N/A'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../sections/footer.php'; ?>
    <script>
        document.querySelectorAll('.location-form').forEach(function(form) {
            var button = form.querySelector('.btn-locate');
            if (!button) return;

            button.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported in this browser.');
                    return;
                }

                button.textContent = 'Getting location...';
                button.disabled = true;

                navigator.geolocation.getCurrentPosition(function(position) {
                    form.querySelector('input[name="delivery_lat"]').value = position.coords.latitude.toFixed(7);
                    form.querySelector('input[name="delivery_lng"]').value = position.coords.longitude.toFixed(7);
                    button.textContent = 'Location Captured';
                    button.disabled = false;
                }, function() {
                    alert('Unable to get current location. Please allow location permission or enter it manually.');
                    button.textContent = 'Use My Current Location';
                    button.disabled = false;
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });
        });
    </script>
</body>
</html>

