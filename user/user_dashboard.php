<?php
require_once '../core/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$cartCount = getCartCount($pdo, $user_id);

$userStmt = $pdo->prepare("SELECT id, name, email, phone, address, city, created_at FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$statsStmt = $pdo->prepare("SELECT 
    COUNT(*) AS total_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
    SUM(CASE WHEN status IN ('pending','confirmed','preparing','out_for_delivery') THEN 1 ELSE 0 END) AS active_orders,
    COALESCE(SUM(total), 0) AS total_spent
    FROM orders
    WHERE user_id = ?");
$statsStmt->execute([$user_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_orders' => 0,
    'delivered_orders' => 0,
    'active_orders' => 0,
    'total_spent' => 0,
];

$recentStmt = $pdo->prepare("SELECT 
    o.*, 
    COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 6");
$recentStmt->execute([$user_id]);
$recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

function orderStatusMeta(string $status): array
{
    $map = [
        'pending' => ['icon' => '<i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i>', 'label' => __('status_pending', 'Pending')],
        'confirmed' => ['icon' => '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i>', 'label' => __('status_confirmed', 'Confirmed')],
        'preparing' => ['icon' => '<i class="fa-solid fa-kitchen-set"></i>', 'label' => __('status_preparing', 'Preparing')],
        'out_for_delivery' => ['icon' => '<i class="fa-solid fa-motorcycle"></i>', 'label' => __('status_out_for_delivery', 'Out for delivery')],
        'delivered' => ['icon' => '<i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i>', 'label' => __('status_delivered', 'Delivered')],
        'cancelled' => ['icon' => '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>', 'label' => __('status_cancelled', 'Cancelled')],
    ];
    return $map[$status] ?? ['icon' => '<i class="fa-solid fa-box"></i>', 'label' => __($status, ucfirst(str_replace('_', ' ', $status)))];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo __('user_dashboard', 'User Dashboard'); ?> — SwiftBite</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
    <style>
        .dash-page {
            padding: 100px 24px 60px;
            min-height: 100vh;
            background: var(--cream);
        }

        .dash-inner {
            max-width: 1180px;
            margin: 0 auto;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .dash-header h1 {
            margin: 0 0 8px;
            font-family: 'Syne', sans-serif;
            font-size: 2.3rem;
            color: var(--dark);
        }

        .dash-header p {
            margin: 0;
            color: var(--muted);
        }

        .quick-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quick-link {
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 14px;
            background: #fff;
            color: var(--dark);
            font-weight: 700;
            box-shadow: var(--shadow);
        }

        .quick-link:hover {
            color: #fff;
            background: var(--orange);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: #fff;
            border-radius: 28px;
            padding: 28px;
            box-shadow: var(--shadow);
        }

        .profile-card {
            position: sticky;
            top: 100px;
        }

        .avatar {
            width: 96px;
            height: 96px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--orange), #ff2400);
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 2.3rem;
            font-weight: 800;
        }

        .profile-name {
            text-align: center;
            margin: 0;
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            color: var(--dark);
        }

        .profile-email {
            text-align: center;
            color: var(--muted);
            margin: 8px 0 24px;
            word-break: break-word;
        }

        .profile-info {
            display: grid;
            gap: 14px;
        }

        .info-box {
            background: var(--cream);
            border-radius: 18px;
            padding: 14px 16px;
        }

        .info-label {
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--dark);
            font-weight: 700;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border-radius: 24px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .stat-label {
            color: var(--muted);
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .stat-value {
            color: var(--dark);
            font-size: 2rem;
            line-height: 1;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
        }

        .stat-hint {
            margin-top: 10px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .section-title {
            margin: 0 0 18px;
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            color: var(--dark);
        }

        .orders-list {
            display: grid;
            gap: 18px;
        }

        .order-card {
            border: 1px solid var(--cream2);
            border-radius: 22px;
            padding: 20px;
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .order-id {
            font-weight: 800;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .order-date {
            color: var(--muted);
            font-size: 0.92rem;
            margin-top: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .status-pending {
            background: rgba(255, 184, 48, 0.15);
            color: #d68c00;
        }

        .status-confirmed,
        .status-preparing {
            background: rgba(0, 122, 255, 0.1);
            color: #007aff;
        }

        .status-out_for_delivery {
            background: rgba(255, 79, 0, 0.12);
            color: var(--orange);
        }

        .status-delivered {
            background: rgba(52, 199, 89, 0.14);
            color: #1a7a34;
        }

        .status-cancelled {
            background: rgba(255, 59, 48, 0.1);
            color: var(--red);
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin: 16px 0;
        }

        .mini {
            background: var(--cream);
            border-radius: 16px;
            padding: 12px 14px;
        }

        .mini-label {
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .mini-value {
            color: var(--dark);
            font-weight: 700;
        }

        .map-wrap {
            margin-top: 14px;
        }

        .map-wrap iframe {
            width: 100%;
            height: 240px;
            border: 0;
            border-radius: 18px;
        }

        .order-actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-soft {
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 14px;
            font-weight: 700;
            background: var(--cream2);
            color: var(--dark);
        }

        .btn-soft:hover {
            background: var(--orange);
            color: #fff;
        }

        .empty-box {
            padding: 48px 20px;
            text-align: center;
            border-radius: 24px;
            background: #fff;
            box-shadow: var(--shadow);
        }

        .empty-box h3 {
            font-family: 'Syne', sans-serif;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .empty-box p {
            color: var(--muted);
            margin-bottom: 22px;
        }

        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }

            .profile-card {
                position: static;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {

            .stats-grid,
            .order-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="dash-page">
        <div class="dash-inner">
            <div class="dash-header">
                <div>
                    <h1><?php echo __('user_dashboard', 'User Dashboard'); ?></h1>
                    <p><?php echo __('dashboard_sub', 'Profile summary, order history, and delivery tracking in one place.'); ?></p>
                </div>
                <div class="quick-links">
                    <a class="quick-link" href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('edit_profile', 'Edit Profile'); ?></a>
                    <a class="quick-link" href="order_history.php"><i class="fa-solid fa-box"></i> <?php echo __('full_order_history', 'Full Order History'); ?></a>
                    <a class="quick-link" href="../menu.php"><i class="fa-solid fa-burger"></i> <?php echo __('browse_menu', 'Browse Menu'); ?></a>
                </div>
            </div>

            <div class="hero-grid">
                <div class="card profile-card">
                    <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>

                    <div class="profile-info">
                        <div class="info-box">
                            <div class="info-label"><?php echo __('phone', 'Phone'); ?></div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: __('not_added_yet', 'Not added yet')); ?>
                            </div>
                        </div>
                        <div class="info-box">
                            <div class="info-label"><?php echo __('address', 'Address'); ?></div>
                            <div class="info-value"><?php echo htmlspecialchars($user['address'] ?: __('not_added_yet', 'Not added yet')); ?>
                            </div>
                        </div>
                        <div class="info-box">
                            <div class="info-label"><?php echo __('city', 'City'); ?></div>
                            <div class="info-value"><?php echo htmlspecialchars(__($user['city'] ?: 'Kathmandu', $user['city'] ?: 'Kathmandu')); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="info-label"><?php echo __('member_since', 'Member Since'); ?></div>
                            <div class="info-value">
                                <?php 
                                if (!empty($user['created_at'])) {
                                    $m = date('M', strtotime($user['created_at']));
                                    $dy = date('d, Y', strtotime($user['created_at']));
                                    echo __($m, $m) . ' ' . t_num($dy);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label"><?php echo __('total_orders_label', 'Total Orders'); ?></div>
                            <div class="stat-value"><?php echo t_num((int) $stats['total_orders']); ?></div>
                            <div class="stat-hint"><?php echo __('all_orders_placed', 'All orders placed'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label"><?php echo __('active_orders_label', 'Active Orders'); ?></div>
                            <div class="stat-value"><?php echo t_num((int) $stats['active_orders']); ?></div>
                            <div class="stat-hint"><?php echo __('pending_to_delivery', 'Pending to delivery'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label"><?php echo __('delivered_label', 'Delivered'); ?></div>
                            <div class="stat-value"><?php echo t_num((int) $stats['delivered_orders']); ?></div>
                            <div class="stat-hint"><?php echo __('successfully_delivered', 'Successfully delivered'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label"><?php echo __('total_spent_label', 'Total Spent'); ?></div>
                            <div class="stat-value"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format((float) $stats['total_spent'], 2)); ?>
                            </div>
                            <div class="stat-hint"><?php echo __('across_all_orders', 'Across all orders'); ?></div>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="section-title"><?php echo __('recent_orders_title', 'Recent Orders'); ?></h2>

                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-box">
                                <h3><?php echo __('no_orders_yet', 'No orders yet'); ?></h3>
                                <p><?php echo __('dashboard_empty_msg', 'Your dashboard will show your recent orders and delivery tracking here.'); ?></p>
                                <a class="btn-soft" href="../menu.php"><?php echo __('start_ordering', 'Start Ordering'); ?></a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <?php $meta = orderStatusMeta($order['status']); ?>
                                    <div class="order-card">
                                        <div class="order-top">
                                            <div>
                                                <div class="order-id"><?php echo __('order_title_hash', 'Order'); ?>
                                                    #<?php echo t_num(str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT)); ?></div>
                                                <div class="order-date">
                                                    <?php 
                                                    $o_month = date('M', strtotime($order['created_at']));
                                                    $o_day = date('d, Y', strtotime($order['created_at']));
                                                    $o_time = date('h:i A', strtotime($order['created_at']));
                                                    echo __($o_month, $o_month) . ' ' . t_num($o_day) . ' • ' . t_num($o_time);
                                                    ?></div>
                                            </div>
                                            <div class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                                <?php echo $meta['icon'] . ' ' . htmlspecialchars($meta['label']); ?>
                                            </div>
                                        </div>

                                        <div class="order-meta">
                                            <div class="mini">
                                                <div class="mini-label"><?php echo __('items', 'Items'); ?></div>
                                                <div class="mini-value"><?php echo t_num((int) $order['total_items']); ?></div>
                                            </div>
                                            <div class="mini">
                                                <div class="mini-label"><?php echo __('payment', 'Payment'); ?></div>
                                                <div class="mini-value">
                                                    <?php echo $order['payment_method'] === 'cod' ? __('cash_on_delivery_cod', 'Cash on Delivery (COD)') : htmlspecialchars(strtoupper($order['payment_method'])); ?></div>
                                            </div>
                                            <div class="mini">
                                                <div class="mini-label"><?php echo __('total', 'Total'); ?></div>
                                                <div class="mini-value"><?php echo __('currency_rs', 'Rs.'); ?>
                                                    <?php echo t_num(number_format((float) $order['total'], 2)); ?></div>
                                            </div>
                                            <div class="mini">
                                                <div class="mini-label"><?php echo __('partner', 'Partner'); ?></div>
                                                <div class="mini-value">
                                                    <?php echo htmlspecialchars($order['delivery_partner_name'] ?: __('not_assigned', 'Not assigned')); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($order['delivery_lat']) && !empty($order['delivery_lng'])): ?>
                                            <div class="map-wrap">
                                                <div class="mini" style="margin-bottom:10px;">
                                                    <div class="mini-label"><?php echo __('live_location_label', 'Live Location'); ?></div>
                                                    <div class="mini-value">
                                                        <?php echo t_num($order['delivery_lat']); ?>,
                                                        <?php echo t_num($order['delivery_lng']); ?>
                                                        <?php if (!empty($order['location_updated_at'])): ?>
                                                            · <?php echo __('updated_at_label', 'Updated'); ?>
                                                            <?php 
                                                            $up_month = date('M', strtotime($order['location_updated_at']));
                                                            $up_day = date('d, Y h:i A', strtotime($order['location_updated_at']));
                                                            echo __($up_month, $up_month) . ' ' . t_num($up_day);
                                                            ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                                                    src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo urlencode((string) ((float) $order['delivery_lng'] - 0.01)); ?>%2C<?php echo urlencode((string) ((float) $order['delivery_lat'] - 0.01)); ?>%2C<?php echo urlencode((string) ((float) $order['delivery_lng'] + 0.01)); ?>%2C<?php echo urlencode((string) ((float) $order['delivery_lat'] + 0.01)); ?>&layer=mapnik&marker=<?php echo urlencode((string) $order['delivery_lat']); ?>%2C<?php echo urlencode((string) $order['delivery_lng']); ?>"></iframe>
                                            </div>
                                        <?php endif; ?>

                                        <div class="order-actions">
                                            <a class="btn-soft"
                                                href="order_details.php?id=<?php echo (int) $order['id']; ?>"><?php echo __('view_details', 'View Details'); ?></a>
                                            <a class="btn-soft" href="order_history.php"><?php echo __('see_all_orders', 'See All Orders'); ?></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../templates/floating_menu.php'; ?>
    <?php include '../templates/chatbot.php'; ?>
    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/cart.js"></script>
</body>

</html>