<?php
session_start();

// Remember me auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    include('core/db.php');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token=?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    }
}

include('core/db.php');
include('core/cart_helper.php');

// Cart count from DB
$cartCount = isset($_SESSION['user_id']) ? getCartCount($pdo, $_SESSION['user_id']) : 0;

// Get restaurant ID
$restId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($restId <= 0) {
    header('Location: restaurants.php');
    exit;
}

// Fetch restaurant
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$restId]);
$rest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rest) {
    header('Location: restaurants.php');
    exit;
}

// Fetch menu categories
$catStmt = $pdo->prepare("SELECT DISTINCT category FROM foods WHERE restaurant_id = ? ORDER BY category");
$catStmt->execute([$restId]);
$menuCategories = $catStmt->fetchColumn() !== false ? $catStmt->fetchAll(PDO::FETCH_COLUMN) : [];
// Re-fetch properly
$catStmt2 = $pdo->prepare("SELECT category, COUNT(*) as cnt FROM foods WHERE restaurant_id = ? GROUP BY category ORDER BY category");
$catStmt2->execute([$restId]);
$menuCatData = $catStmt2->fetchAll(PDO::FETCH_ASSOC); // [{category, cnt}]

// Fetch ALL foods grouped (we display all, grouped by category on the page)
$foodStmt = $pdo->prepare("SELECT * FROM foods WHERE restaurant_id = ? ORDER BY category ASC, is_featured DESC, id DESC");
$foodStmt->execute([$restId]);
$allFoods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

// Group foods by category
$foodsByCategory = [];
foreach ($allFoods as $f) {
    $foodsByCategory[$f['category']][] = $f;
}
$totalFoods = count($allFoods);

// Cuisine emoji
$cuisineEmojis = [
    'Fast Food' => '🍔', 'Nepali' => '🥘', 'Italian' => '🍕', 'Chinese' => '🥡',
    'Japanese' => '🍣', 'Healthy' => '🥗', 'Indian' => '🍛', 'Thai' => '🍜',
    'Mexican' => '🌮', 'Korean' => '🥟', 'BBQ' => '🥩', 'Cafe' => '☕',
    'Bakery' => '🧁', 'Seafood' => '🦐', 'Mixed' => '🍴',
];

$catEmojis = [
    'Burgers' => '🍔', 'Pizza' => '🍕', 'Sushi' => '🍣', 'Noodles' => '🍜',
    'Salads' => '🥗', 'Desserts' => '🍰', 'Chicken' => '🍗', 'Drinks' => '🥤',
    'Seafood' => '🦐', 'Pasta' => '🍝', 'BBQ' => '🥩', 'Breakfast' => '🥞',
    'Sandwich' => '🥪', 'Soup' => '🍲', 'Rice' => '🍚',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($rest['name']); ?> — SwiftBite</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($rest['description'], 0, 155)); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=6" />
    <style>
        .restd-page { padding-top: 90px; min-height: 100vh; }

        /* Breadcrumb */
        .breadcrumb {
            display: flex; align-items: center; gap: 8px;
            padding: 20px 60px; font-size: 0.88rem; flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--muted); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .breadcrumb a:hover { color: var(--orange); }
        .breadcrumb .sep { color: var(--muted); opacity: 0.5; }
        .breadcrumb .current { color: var(--dark); font-weight: 700; }

        /* Hero banner */
        .restd-hero {
            margin: 0 60px 32px;
            border-radius: 32px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark2) 100%);
            min-height: 280px;
            display: flex;
            align-items: flex-end;
        }
        .restd-hero-bg {
            position: absolute; inset: 0;
            background-size: cover; background-position: center;
            opacity: 0.3;
        }
        .restd-hero-content {
            position: relative; z-index: 2;
            padding: 40px 48px;
            color: #fff;
            width: 100%;
        }
        .restd-cuisine-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);
            padding: 8px 18px; border-radius: 999px;
            font-weight: 700; font-size: 0.82rem; margin-bottom: 14px;
        }
        .restd-name {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            font-weight: 800; line-height: 1.15; margin-bottom: 12px;
        }
        .restd-hero-meta {
            display: flex; gap: 20px; flex-wrap: wrap; align-items: center;
        }
        .restd-hero-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.12); backdrop-filter: blur(4px);
            padding: 8px 16px; border-radius: 999px;
            font-size: 0.85rem; font-weight: 600;
        }
        .restd-status {
            padding: 8px 18px; border-radius: 999px;
            font-size: 0.82rem; font-weight: 800;
        }
        .restd-status.open { background: rgba(52,199,89,0.9); }
        .restd-status.closed { background: rgba(255,59,48,0.9); }

        /* Info cards */
        .restd-info {
            display: flex; gap: 16px; padding: 0 60px 32px; flex-wrap: wrap;
        }
        .restd-info-card {
            flex: 1; min-width: 160px;
            background: #fff; border-radius: 20px; padding: 20px;
            box-shadow: var(--shadow); text-align: center;
            border: 2px solid var(--cream2); transition: all 0.2s;
        }
        .restd-info-card:hover { border-color: var(--orange); transform: translateY(-2px); }
        .restd-info-icon { font-size: 1.6rem; margin-bottom: 8px; }
        .restd-info-label { font-size: 0.72rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .restd-info-value { font-weight: 800; color: var(--dark); font-size: 0.92rem; }

        /* ── Menu Layout ── */
        .restd-menu-wrap {
            padding: 0 60px 80px;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 32px;
            align-items: start;
        }

        /* Sticky sidebar */
        .menu-sidebar {
            position: sticky;
            top: 110px;
            background: #fff;
            border-radius: 24px;
            padding: 20px 16px;
            box-shadow: var(--shadow);
        }
        .menu-sidebar-title {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--orange);
            padding: 0 8px 12px;
            border-bottom: 2px solid var(--cream2);
            margin-bottom: 12px;
        }
        .menu-sidebar-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            gap: 8px;
        }
        .menu-sidebar-item:hover,
        .menu-sidebar-item.active {
            background: rgba(255,79,0,0.08);
            color: var(--orange);
        }
        .menu-sidebar-item .item-emoji { font-size: 1.1rem; flex-shrink: 0; }
        .menu-sidebar-item .item-label { flex: 1; }
        .menu-sidebar-item .item-count {
            background: var(--cream2);
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            min-width: 24px;
            text-align: center;
        }
        .menu-sidebar-item.active .item-count {
            background: var(--orange);
            color: #fff;
        }

        /* Mobile category pills (hidden on desktop) */
        .menu-pills-mobile {
            display: none;
            gap: 10px;
            overflow-x: auto;
            padding: 0 20px 16px;
            scrollbar-width: none;
        }
        .menu-pills-mobile::-webkit-scrollbar { display: none; }
        .menu-pill {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            background: #fff;
            color: var(--text);
            border: 2px solid var(--cream2);
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .menu-pill:hover, .menu-pill.active {
            background: var(--orange);
            color: #fff;
            border-color: var(--orange);
        }

        /* Main menu content */
        .menu-content { min-width: 0; }
        .menu-section { margin-bottom: 44px; }
        .menu-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--cream2);
        }
        .menu-section-title .title-emoji {
            width: 38px; height: 38px;
            border-radius: 12px;
            background: var(--cream2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .menu-section-count {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--muted);
            background: var(--cream2);
            padding: 3px 10px;
            border-radius: 999px;
            margin-left: auto;
        }

        /* Horizontal menu item row */
        .menu-item {
            display: flex;
            gap: 16px;
            align-items: center;
            background: #fff;
            border-radius: 20px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 2px 16px rgba(255,79,0,0.06);
            border: 2px solid transparent;
            transition: all 0.22s;
            position: relative;
            overflow: hidden;
        }
        .menu-item:hover {
            border-color: rgba(255,79,0,0.15);
            box-shadow: 0 8px 32px rgba(255,79,0,0.12);
            transform: translateY(-2px);
        }
        .menu-item-img {
            width: 90px;
            height: 90px;
            border-radius: 16px;
            flex-shrink: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #fff6ea, rgba(255,79,0,0.12));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.6rem;
        }
        .menu-item-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.35s;
        }
        .menu-item:hover .menu-item-img img { transform: scale(1.08); }
        .menu-item-body {
            flex: 1;
            min-width: 0;
        }
        .menu-item-top {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 4px;
        }
        .menu-item-name {
            font-weight: 800;
            font-size: 1rem;
            color: var(--dark);
            line-height: 1.3;
            flex: 1;
        }
        .menu-item-badge {
            flex-shrink: 0;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--orange);
            color: #fff;
        }
        .menu-item-badge.new { background: #34c759; }
        .menu-item-badge.popular { background: #ffb830; color: var(--dark); }
        .menu-item-badge.sale { background: #5856d6; }
        .menu-item-desc {
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .menu-item-footer {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .menu-item-rating {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--dark);
            display: flex; align-items: center; gap: 3px;
        }
        .menu-item-time {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            display: flex; align-items: center; gap: 3px;
        }
        .menu-fav-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #ff3b30;
            margin-left: 4px;
        }
        /* Right side: price + add btn */
        .menu-item-right {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        .menu-item-price {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--dark);
            white-space: nowrap;
        }
        .menu-item-price small {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--muted);
        }
        .menu-add-btn {
            width: 42px; height: 42px;
            border-radius: 14px;
            background: var(--orange);
            color: #fff;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
            line-height: 1;
            box-shadow: 0 4px 14px rgba(255,79,0,0.3);
        }
        .menu-add-btn:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(255,79,0,0.45); }
        .menu-add-btn:active { transform: scale(0.95); }
        .menu-add-btn.added {
            background: #34c759;
            animation: popIn 0.3s ease;
        }
        @keyframes popIn {
            0% { transform: scale(1); }
            50% { transform: scale(1.25); }
            100% { transform: scale(1); }
        }

        /* Empty state */
        .restd-menu-empty {
            background: #fff; border-radius: 28px; box-shadow: var(--shadow);
            text-align: center; padding: 80px 40px;
            grid-column: 1 / -1;
        }
        .restd-menu-empty .empty-icon { font-size: 4rem; margin-bottom: 16px; }
        .restd-menu-empty h3 { font-family: 'Syne', sans-serif; color: var(--dark); margin-bottom: 8px; font-size: 1.4rem; }
        .restd-menu-empty p { color: var(--muted); margin-bottom: 24px; }
        .restd-menu-empty a {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; background: var(--orange); color: #fff;
            border-radius: 14px; font-weight: 700; text-decoration: none;
            transition: all 0.2s;
        }
        .restd-menu-empty a:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,79,0,0.3); }

        /* Toast notification */
        .cart-toast {
            position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: var(--dark); color: #fff;
            padding: 14px 24px; border-radius: 16px;
            font-weight: 600; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s;
            z-index: 500;
            opacity: 0; pointer-events: none;
            white-space: nowrap;
        }
        .cart-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .cart-toast-icon { font-size: 1.3rem; }

        @media (max-width: 900px) {
            .restd-menu-wrap { grid-template-columns: 1fr; padding: 0 20px 60px; gap: 16px; }
            .menu-sidebar { display: none; }
            .menu-pills-mobile { display: flex; }
            .breadcrumb, .restd-info { padding-left: 20px; padding-right: 20px; }
            .restd-hero { margin: 0 20px 24px; border-radius: 24px; min-height: 220px; }
            .restd-hero-content { padding: 28px 24px; }
            .restd-info-card { min-width: 120px; padding: 14px; }
        }
        @media (max-width: 520px) {
            .menu-item-img { width: 72px; height: 72px; font-size: 2rem; }
            .menu-item { padding: 14px; }
            .menu-item-name { font-size: 0.92rem; }
        }
    </style>
</head>
<body>
    <?php include 'templates/navbar.php'; ?>

    <div class="restd-page">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="sep">›</span>
            <a href="restaurants.php">Restaurants</a>
            <span class="sep">›</span>
            <span class="current"><?php echo htmlspecialchars($rest['name']); ?></span>
        </div>

        <!-- Hero Banner -->
        <div class="restd-hero">
            <?php if (!empty($rest['image_path'])): ?>
                <div class="restd-hero-bg" style="background-image: url('<?php echo htmlspecialchars($rest['image_path']); ?>'); opacity: 0.35;"></div>
            <?php endif; ?>
            <div class="restd-hero-content">
                <div class="restd-cuisine-pill">
                    <?php echo $cuisineEmojis[$rest['cuisine_type']] ?? '🍴'; ?>
                    <?php echo htmlspecialchars($rest['cuisine_type']); ?>
                </div>
                <div class="restd-name"><?php echo htmlspecialchars($rest['name']); ?></div>
                <div class="restd-hero-meta">
                    <span class="restd-hero-pill">⭐ <?php echo htmlspecialchars($rest['rating']); ?></span>
                    <span class="restd-hero-pill">🕐 <?php echo htmlspecialchars($rest['delivery_time']); ?></span>
                    <span class="restd-hero-pill">📍 <?php echo htmlspecialchars($rest['address']); ?></span>
                    <span class="restd-status <?php echo $rest['is_open'] ? 'open' : 'closed'; ?>">
                        <?php echo $rest['is_open'] ? '● Open Now' : '● Closed'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="restd-info">
            <div class="restd-info-card">
                <div class="restd-info-icon">🕐</div>
                <div class="restd-info-label">Delivery</div>
                <div class="restd-info-value"><?php echo htmlspecialchars($rest['delivery_time']); ?></div>
            </div>
            <div class="restd-info-card">
                <div class="restd-info-icon">🚚</div>
                <div class="restd-info-label">Delivery Fee</div>
                <div class="restd-info-value">Rs. <?php echo number_format((float)$rest['delivery_fee'], 0); ?></div>
            </div>
            <div class="restd-info-card">
                <div class="restd-info-icon">📦</div>
                <div class="restd-info-label">Min Order</div>
                <div class="restd-info-value">Rs. <?php echo number_format((float)$rest['min_order'], 0); ?></div>
            </div>
            <div class="restd-info-card">
                <div class="restd-info-icon">📞</div>
                <div class="restd-info-label">Phone</div>
                <div class="restd-info-value"><?php echo htmlspecialchars($rest['phone']); ?></div>
            </div>
            <div class="restd-info-card">
                <div class="restd-info-icon">📍</div>
                <div class="restd-info-label">City</div>
                <div class="restd-info-value"><?php echo htmlspecialchars($rest['city']); ?></div>
            </div>
        </div>

        <!-- About -->
        <div style="padding: 0 60px 24px;">
            <div style="background:#fff; border-radius:24px; padding:28px; box-shadow:var(--shadow);">
                <div style="font-weight:800; font-size:0.78rem; letter-spacing:2px; text-transform:uppercase; color:var(--orange); margin-bottom:10px;">About</div>
                <p style="color:var(--muted); line-height:1.8; font-size:1.02rem;"><?php echo nl2br(htmlspecialchars($rest['description'])); ?></p>
            </div>
        </div>

        <!-- Menu heading -->
        <div style="padding: 0 60px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
                <div class="section-tag">🍽️ Full Menu</div>
                <div class="section-title"><?php echo htmlspecialchars($rest['name']); ?>'s Menu</div>
            </div>
            <span style="background:var(--cream2); padding:6px 18px; border-radius:999px; font-size:0.85rem; font-weight:700; color:var(--orange);">
                <?php echo $totalFoods; ?> items
            </span>
        </div>

        <!-- Mobile category pills -->
        <?php if (!empty($foodsByCategory)): ?>
        <div class="menu-pills-mobile">
            <?php foreach ($foodsByCategory as $cat => $items): ?>
                <a href="#cat-<?php echo urlencode($cat); ?>" class="menu-pill">
                    <?php echo $catEmojis[$cat] ?? '🍴'; ?> <?php echo htmlspecialchars($cat); ?>
                    <span style="font-size:0.68rem;opacity:0.7;">(<?php echo count($items); ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Menu: sidebar + content -->
        <div class="restd-menu-wrap">

            <?php if (empty($foodsByCategory)): ?>
                <!-- Empty state -->
                <div class="restd-menu-empty">
                    <div class="empty-icon">🍽️</div>
                    <h3>No menu items yet</h3>
                    <p>This restaurant hasn't added any food items yet. Check back soon!</p>
                    <a href="menu.php">Browse other foods →</a>
                </div>
            <?php else: ?>

                <!-- Sticky sidebar -->
                <aside class="menu-sidebar">
                    <div class="menu-sidebar-title">Categories</div>
                    <?php foreach ($foodsByCategory as $cat => $items): ?>
                        <a href="#cat-<?php echo urlencode($cat); ?>" class="menu-sidebar-item">
                            <span class="item-emoji"><?php echo $catEmojis[$cat] ?? '🍴'; ?></span>
                            <span class="item-label"><?php echo htmlspecialchars($cat); ?></span>
                            <span class="item-count"><?php echo count($items); ?></span>
                        </a>
                    <?php endforeach; ?>
                </aside>

                <!-- Menu sections -->
                <div class="menu-content">
                    <?php foreach ($foodsByCategory as $cat => $items): ?>
                        <section class="menu-section" id="cat-<?php echo urlencode($cat); ?>">
                            <div class="menu-section-title">
                                <div class="title-emoji"><?php echo $catEmojis[$cat] ?? '🍴'; ?></div>
                                <?php echo htmlspecialchars($cat); ?>
                                <span class="menu-section-count"><?php echo count($items); ?> items</span>
                            </div>

                            <?php foreach ($items as $food): ?>
                                <div class="menu-item">
                                    <!-- Image -->
                                    <a href="food_detail.php?id=<?php echo (int)$food['id']; ?>" class="menu-item-img" style="text-decoration:none;">
                                        <?php if (!empty($food['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($food['image_path']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($food['emoji']); ?>
                                        <?php endif; ?>
                                    </a>

                                    <!-- Info -->
                                    <div class="menu-item-body">
                                        <div class="menu-item-top">
                                            <div class="menu-item-name">
                                                <?php if ($food['is_favorite']): ?>❤️ <?php endif; ?>
                                                <?php echo htmlspecialchars($food['name']); ?>
                                            </div>
                                            <?php if (!empty($food['badge'])): ?>
                                                <?php
                                                    $badgeClass = match(strtolower($food['badge'])) {
                                                        'new' => 'new',
                                                        'popular' => 'popular',
                                                        'sale' => 'sale',
                                                        default => ''
                                                    };
                                                ?>
                                                <span class="menu-item-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($food['badge']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="menu-item-desc"><?php echo htmlspecialchars($food['description']); ?></div>
                                        <div class="menu-item-footer">
                                            <span class="menu-item-rating">⭐ <?php echo htmlspecialchars($food['rating']); ?></span>
                                            <span class="menu-item-time">🕐 <?php echo htmlspecialchars($food['delivery_time']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Price + Add -->
                                    <div class="menu-item-right">
                                        <div class="menu-item-price">
                                            <small>Rs.</small> <?php echo number_format((float)$food['price'], 0); ?>
                                        </div>
                                    <form action="actions/add_to_cart.php" method="post" class="menu-cart-form">
                                            <input type="hidden" name="food_id" value="<?php echo (int)$food['id']; ?>">
                                            <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>">
                                            <input type="hidden" name="price" value="<?php echo (float)$food['price']; ?>">
                                            <button class="menu-add-btn" type="submit" title="Add to cart"
                                                    data-name="<?php echo htmlspecialchars($food['name']); ?>">+</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>


    <?php include 'templates/footer.php'; ?>

    <?php include 'templates/floating_menu.php'; ?>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/cart.js"></script>
    <script>
        // ── Sidebar active state on scroll ──
        const sections = document.querySelectorAll('.menu-section');
        const sidebarLinks = document.querySelectorAll('.menu-sidebar-item');

        function updateActiveSidebar() {
            let current = '';
            sections.forEach(sec => {
                const top = sec.getBoundingClientRect().top;
                if (top <= 140) current = sec.id;
            });
            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateActiveSidebar, { passive: true });
        updateActiveSidebar();

        // ── Smooth scroll with offset for sticky nav ──
        document.querySelectorAll('a[href^="#cat-"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 120;
                    const top = target.getBoundingClientRect().top + window.scrollY - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>

