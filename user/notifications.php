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
$notifications = getNotifications($pdo, $user_id, 100);

// Mark all as read
markAllNotificationsRead($pdo, $user_id);

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . 'm ago';
    if ($diff < 86400) return round($diff / 3600) . 'h ago';
    return date('M d', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notifications — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css?v=12" />
    <style>
        /* BASE PAGE STYLING - NO FLEX ON BODY/PAGE TO PREVENT FOOTER SIDEBAR BUG */
        .sb-notif-main {
            padding: 120px 20px 80px;
            background: var(--body-bg);
            min-height: 100vh;
            display: block; /* Standard block layout */
            overflow-x: hidden;
            position: relative;
        }

        /* CENTERED CONTAINER */
        .sb-notif-container {
            max-width: 800px;
            margin: 0 auto; /* Pure CSS centering */
            position: relative;
            z-index: 5;
        }

        /* HEADER SECTION */
        .sb-notif-header {
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .sb-notif-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-head);
            margin: 0;
        }
        .sb-notif-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 18px;
            background: var(--orange);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(255,79,0,0.2);
        }

        /* FILTER TABS */
        .sb-notif-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 35px;
            overflow-x: auto;
            padding-bottom: 15px;
            scrollbar-width: none;
        }
        .sb-notif-tabs::-webkit-scrollbar { display: none; }

        .sb-notif-tab {
            padding: 10px 22px;
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            border-radius: 999px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sb-notif-tab:hover {
            border-color: var(--orange);
            color: var(--orange);
        }
        .sb-notif-tab.active {
            background: var(--orange);
            color: #fff;
            border-color: var(--orange);
            box-shadow: 0 4px 15px rgba(255,79,0,0.25);
        }

        /* NOTIFICATION ITEM */
        .sb-notif-card {
            background: var(--surface);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 18px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            box-shadow: var(--shadow);
            border-left: 5px solid transparent;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }
        .sb-notif-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        .sb-notif-card.unread {
            border-left-color: var(--orange);
            background: linear-gradient(to right, var(--surface2), var(--surface));
        }
        .sb-notif-card.unread::after {
            content: '';
            position: absolute;
            top: 20px;
            right: 20px;
            width: 10px;
            height: 10px;
            background: var(--orange);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255,79,0,0.4);
        }

        /* ICON WRAPPER */
        .sb-notif-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            background: var(--border-subtle);
            color: var(--text-muted);
        }
        .unread .sb-notif-icon-wrap {
            background: rgba(255,79,0,0.1);
            color: var(--orange);
        }

        /* IMAGE WRAPPER */
        .sb-notif-img {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid var(--border-subtle);
        }

        /* CONTENT */
        .sb-notif-content {
            flex: 1;
            min-width: 0;
        }
        .sb-notif-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            gap: 12px;
        }
        .sb-notif-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-head);
            line-height: 1.3;
        }
        .sb-notif-type {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 10px;
            border-radius: 999px;
            background: var(--border-subtle);
            color: var(--text-muted);
            white-space: nowrap;
        }
        .sb-notif-msg {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        .sb-notif-time {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* DATE DIVIDER */
        .sb-date-divider {
            font-family: 'Syne', sans-serif;
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 40px 0 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .sb-date-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-subtle);
        }

        /* EMPTY STATE */
        .sb-notif-empty {
            text-align: center;
            padding: 80px 20px;
            background: var(--surface);
            border-radius: 28px;
            box-shadow: var(--shadow);
            border: 2px dashed var(--border-subtle);
        }
        .sb-notif-empty h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            color: var(--text-head);
            margin-bottom: 10px;
        }
        .sb-notif-empty p {
            color: var(--text-muted);
            max-width: 400px;
            margin: 0 auto 25px;
        }

        @media (max-width: 640px) {
            .sb-notif-main { padding-top: 100px; }
            .sb-notif-card { padding: 18px; gap: 15px; }
            .sb-notif-header h1 { font-size: 1.8rem; }
            .sb-notif-icon-wrap { width: 48px; height: 48px; font-size: 1.2rem; }
            .sb-notif-img { width: 56px; height: 56px; }
        }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <main class="sb-notif-main">
        <div class="sb-notif-container">
            
            <header class="sb-notif-header">
                <h1>Notifications</h1>
                <span class="sb-notif-badge"><?php echo count($notifications); ?> total</span>
            </header>

            <nav class="sb-notif-tabs">
                <button class="sb-notif-tab active" data-filter="all">All</button>
                <button class="sb-notif-tab" data-filter="order_placed">Orders</button>
                <button class="sb-notif-tab" data-filter="order_delivered">Delivered</button>
                <button class="sb-notif-tab" data-filter="order_cancelled">Cancelled</button>
                <button class="sb-notif-tab" data-filter="order_status">Updates</button>
            </nav>

            <?php if (empty($notifications)): ?>
                <div class="sb-notif-empty">
                    <h3>No notifications yet</h3>
                    <p>When you place orders or account updates occur, they'll appear here.</p>
                </div>
            <?php else: ?>
                <div id="sb-notif-list">
                    <?php
                    $lastDate = '';
                    foreach ($notifications as $idx => $n):
                        $nDate = date('Y-m-d', strtotime($n['created_at']));
                        if ($nDate !== $lastDate):
                            $label = ($nDate === date('Y-m-d')) ? 'Today' : date('F j, Y', strtotime($nDate));
                    ?>
                        <div class="sb-date-divider"><?php echo $label; ?></div>
                    <?php 
                        $lastDate = $nDate;
                        endif; 
                    ?>

                    <div class="sb-notif-card <?php echo !$n['is_read'] ? 'unread' : ''; ?>" 
                         data-type="<?php echo htmlspecialchars($n['type']); ?>"
                         <?php if ($n['link']): ?> onclick="window.location.href='<?php echo htmlspecialchars($n['link']); ?>'" <?php endif; ?>>
                        
                        <?php if ($n['image_path']): ?>
                            <img src="../<?php echo htmlspecialchars($n['image_path']); ?>" class="sb-notif-img" alt="Order image">
                        <?php else: ?>
                            <div class="sb-notif-icon-wrap">
                                <?php echo $n['icon']; ?>
                            </div>
                        <?php endif; ?>

                        <div class="sb-notif-content">
                            <div class="sb-notif-top">
                                <span class="sb-notif-title"><?php echo $n['title']; ?></span>
                                <span class="sb-notif-type"><?php echo str_replace('_', ' ', $n['type']); ?></span>
                            </div>
                            <p class="sb-notif-msg"><?php echo $n['message']; ?></p>
                            <span class="sb-notif-time">
                                <i class="fa-regular fa-clock"></i> <?php echo timeAgo($n['created_at']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include '../templates/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.sb-notif-tab');
            const cards = document.querySelectorAll('.sb-notif-card');
            const dividers = document.querySelectorAll('.sb-date-divider');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const filter = tab.getAttribute('data-filter');
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    cards.forEach(card => {
                        const type = card.getAttribute('data-type');
                        card.style.display = (filter === 'all' || type === filter) ? 'flex' : 'none';
                    });

                    dividers.forEach(div => {
                        let hasVisible = false;
                        let next = div.nextElementSibling;
                        while(next && next.classList.contains('sb-notif-card')) {
                            if(next.style.display !== 'none') hasVisible = true;
                            next = next.nextElementSibling;
                        }
                        div.style.display = hasVisible ? 'flex' : 'none';
                    });
                });
            });
        });
    </script>
</body>
</html>
