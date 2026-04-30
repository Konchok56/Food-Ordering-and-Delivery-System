<?php
session_start();
include('../core/db.php');
include('../core/cart_helper.php');
include('../core/notification_helper.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cartCount = getCartCount($pdo, $user_id);

// Fetch all notifications
$notifications = getNotifications($pdo, $user_id, 100);

// Mark all as read on page visit
markAllNotificationsRead($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notifications — SwiftBite</title>
    <meta name="description" content="View all your SwiftBite notifications — order updates, delivery status, account changes and more.">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
    <style>
        .notif-page {
            padding: 100px 24px 60px;
            min-height: 100vh;
            background: var(--cream);
        }
        .notif-inner {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Page Header */
        .notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .notif-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
        }
        .notif-header .notif-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            background: linear-gradient(135deg, #ff4f00, #ff2400);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 6px 20px rgba(255,79,0,0.25);
        }
        .notif-header .mark-read-btn {
            padding: 10px 20px;
            background: var(--cream2);
            color: var(--dark);
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .notif-header .mark-read-btn:hover {
            background: var(--orange);
            color: #fff;
        }

        /* Filter Tabs */
        .notif-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .notif-filter-btn {
            padding: 10px 20px;
            background: #fff;
            border: 2px solid var(--cream2);
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--muted);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.25s;
        }
        .notif-filter-btn:hover {
            border-color: var(--orange);
            color: var(--orange);
        }
        .notif-filter-btn.active {
            background: var(--orange);
            border-color: var(--orange);
            color: #fff;
            box-shadow: 0 4px 16px rgba(255,79,0,0.25);
        }

        /* Notification Card */
        .notif-card {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            background: #fff;
            border-radius: 24px;
            padding: 22px 24px;
            margin-bottom: 14px;
            box-shadow: 0 2px 12px rgba(26,16,4,0.04);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            cursor: pointer;
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .notif-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,79,0,0.03), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .notif-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(255, 79, 0, 0.1);
        }
        .notif-card:hover::before {
            opacity: 1;
        }
        .notif-card.unread {
            border-left-color: var(--orange);
            background: linear-gradient(135deg, #fff9f4, #fff);
        }
        .notif-card.unread::after {
            content: '';
            position: absolute;
            top: 22px;
            right: 20px;
            width: 10px;
            height: 10px;
            background: var(--orange);
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(255,79,0,0.4);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 8px rgba(255,79,0,0.4); }
            50% { box-shadow: 0 0 16px rgba(255,79,0,0.7); }
        }

        /* Notification Icon */
        .notif-icon-wrap {
            flex-shrink: 0;
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            position: relative;
            z-index: 1;
        }
        .notif-icon-wrap.type-order_placed { background: rgba(52, 199, 89, 0.12); }
        .notif-icon-wrap.type-order_cancelled { background: rgba(255, 59, 48, 0.12); }
        .notif-icon-wrap.type-order_delivered { background: rgba(52, 199, 89, 0.12); }
        .notif-icon-wrap.type-order_status { background: rgba(0, 122, 255, 0.12); }
        .notif-icon-wrap.type-password_changed { background: rgba(255, 184, 48, 0.12); }
        .notif-icon-wrap.type-profile_updated { background: rgba(88, 86, 214, 0.12); }
        .notif-icon-wrap.type-info { background: rgba(0, 122, 255, 0.12); }

        /* Notification Image */
        .notif-food-img {
            flex-shrink: 0;
            width: 64px;
            height: 64px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid var(--cream2);
            position: relative;
            z-index: 1;
        }

        /* Notification Content */
        .notif-content {
            flex: 1;
            min-width: 0;
            position: relative;
            z-index: 1;
        }
        .notif-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--dark);
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .notif-msg {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.55;
            margin-bottom: 8px;
        }
        .notif-time {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            opacity: 0.7;
        }

        /* Type badges */
        .notif-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 10px;
        }
        .badge-order_placed { background: rgba(52,199,89,0.12); color: #1a7a34; }
        .badge-order_cancelled { background: rgba(255,59,48,0.12); color: #d93025; }
        .badge-order_delivered { background: rgba(52,199,89,0.12); color: #1a7a34; }
        .badge-order_status { background: rgba(0,122,255,0.1); color: #007aff; }
        .badge-password_changed { background: rgba(255,184,48,0.12); color: #b87a00; }
        .badge-profile_updated { background: rgba(88,86,214,0.12); color: #5856d6; }
        .badge-info { background: rgba(0,122,255,0.1); color: #007aff; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 28px;
            box-shadow: var(--shadow);
        }
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 16px;
            filter: grayscale(0.3);
        }
        .empty-state h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .empty-state p {
            color: var(--muted);
            margin-bottom: 24px;
            max-width: 400px;
            margin-inline: auto;
            line-height: 1.6;
        }
        .empty-btn {
            display: inline-flex;
            padding: 16px 32px;
            background: var(--orange);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 30px rgba(255,79,0,0.3);
            transition: transform 0.2s;
        }
        .empty-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 40px rgba(255,79,0,0.4);
        }

        /* Animations */
        .notif-card {
            animation: slideUp 0.4s ease both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .notif-card:nth-child(2) { animation-delay: 0.04s; }
        .notif-card:nth-child(3) { animation-delay: 0.08s; }
        .notif-card:nth-child(4) { animation-delay: 0.12s; }
        .notif-card:nth-child(5) { animation-delay: 0.16s; }
        .notif-card:nth-child(6) { animation-delay: 0.20s; }
        .notif-card:nth-child(7) { animation-delay: 0.24s; }
        .notif-card:nth-child(8) { animation-delay: 0.28s; }

        /* Date Group Headers */
        .notif-date-group {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 0 8px;
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .notif-date-group::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--cream2);
        }

        @media (max-width: 600px) {
            .notif-card { padding: 16px; gap: 12px; }
            .notif-food-img { width: 48px; height: 48px; }
            .notif-header h1 { font-size: 1.7rem; }
            .notif-filters { gap: 6px; }
        }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="notif-page">
        <div class="notif-inner">

            <div class="notif-header">
                <h1>🔔 Notifications</h1>
                <?php
                    $unreadCount = 0;
                    foreach ($notifications as $n) {
                        if (!$n['is_read']) $unreadCount++;
                    }
                ?>
                <?php if (count($notifications) > 0): ?>
                    <span class="notif-count-pill"><?php echo count($notifications); ?> total</span>
                <?php endif; ?>
            </div>

            <!-- Filter Tabs -->
            <div class="notif-filters">
                <button class="notif-filter-btn active" data-filter="all">All</button>
                <button class="notif-filter-btn" data-filter="order_placed">🛒 Orders</button>
                <button class="notif-filter-btn" data-filter="order_delivered">🎉 Delivered</button>
                <button class="notif-filter-btn" data-filter="order_cancelled">❌ Cancelled</button>
                <button class="notif-filter-btn" data-filter="order_status">📦 Status</button>
                <button class="notif-filter-btn" data-filter="password_changed">🔒 Security</button>
                <button class="notif-filter-btn" data-filter="profile_updated">👤 Profile</button>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔔</div>
                    <h3>No notifications yet</h3>
                    <p>When you place orders, update your profile, or make account changes, you'll see notifications here!</p>
                    <a href="../menu.php" class="empty-btn">Explore Menu</a>
                </div>
            <?php else: ?>
                <div id="notifList">
                <?php
                    $lastDate = '';
                    foreach ($notifications as $idx => $notif):
                        $notifDate = date('Y-m-d', strtotime($notif['created_at']));
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));

                        if ($notifDate !== $lastDate) {
                            if ($notifDate === $today) {
                                $dateLabel = 'Today';
                            } elseif ($notifDate === $yesterday) {
                                $dateLabel = 'Yesterday';
                            } else {
                                $dateLabel = date('M d, Y', strtotime($notif['created_at']));
                            }
                            $lastDate = $notifDate;
                ?>
                    <div class="notif-date-group"><?php echo $dateLabel; ?></div>
                <?php } ?>

                    <div class="notif-card <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"
                         data-type="<?php echo htmlspecialchars($notif['type']); ?>"
                         <?php if (!empty($notif['link'])): ?>
                         onclick="window.location.href='<?php echo htmlspecialchars($notif['link']); ?>'"
                         <?php endif; ?>
                         style="animation-delay: <?php echo min($idx * 0.04, 0.4); ?>s;">

                        <div class="notif-icon-wrap type-<?php echo htmlspecialchars($notif['type']); ?>">
                            <?php echo $notif['icon']; ?>
                        </div>

                        <?php if (!empty($notif['image_path'])): ?>
                            <img class="notif-food-img"
                                 src="../<?php echo htmlspecialchars($notif['image_path']); ?>"
                                 alt="notification image"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>

                        <div class="notif-content">
                            <div class="notif-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <span class="notif-type-badge badge-<?php echo htmlspecialchars($notif['type']); ?>">
                                    <?php
                                        $typeLabels = [
                                            'order_placed' => 'Order',
                                            'order_cancelled' => 'Cancelled',
                                            'order_delivered' => 'Delivered',
                                            'order_status' => 'Update',
                                            'password_changed' => 'Security',
                                            'profile_updated' => 'Profile',
                                            'info' => 'Info',
                                        ];
                                        echo $typeLabels[$notif['type']] ?? 'Info';
                                    ?>
                                </span>
                            </div>
                            <div class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notif-time">
                                🕐 <?php echo timeAgo($notif['created_at']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include '../templates/floating_menu.php'; ?>
    <?php include '../templates/footer.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/cart.js"></script>
    <script>
    // Filter tabs
    document.querySelectorAll('.notif-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.notif-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.dataset.filter;
            document.querySelectorAll('.notif-card').forEach(card => {
                if (filter === 'all' || card.dataset.type === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide date groups based on visible cards
            document.querySelectorAll('.notif-date-group').forEach(group => {
                let next = group.nextElementSibling;
                let hasVisible = false;
                while (next && !next.classList.contains('notif-date-group')) {
                    if (next.classList.contains('notif-card') && next.style.display !== 'none') {
                        hasVisible = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }
                group.style.display = hasVisible ? '' : 'none';
            });
        });
    });
    </script>
</body>
</html>
<?php
/**
 * Helper: human-readable time ago string.
 */
function timeAgo(string $datetime): string {
    $now  = time();
    $diff = $now - strtotime($datetime);

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 172800) return 'Yesterday';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', strtotime($datetime));
}
?>
