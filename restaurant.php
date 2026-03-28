<?php
session_start();

// Remember me auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    include('includes/db.php');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token=?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    }
}

// Cart count
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) $item['quantity'];
    }
}

include('includes/db.php');

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

// Get food category filter
$menuCat = isset($_GET['cat']) ? trim($_GET['cat']) : 'all';

// Fetch restaurant's menu categories
$catStmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM foods WHERE restaurant_id = ? GROUP BY category ORDER BY category");
$catStmt->execute([$restId]);
$menuCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch restaurant's foods
if ($menuCat !== 'all') {
    $foodStmt = $pdo->prepare("SELECT * FROM foods WHERE restaurant_id = ? AND category = ? ORDER BY is_featured DESC, id DESC");
    $foodStmt->execute([$restId, $menuCat]);
} else {
    $foodStmt = $pdo->prepare("SELECT * FROM foods WHERE restaurant_id = ? ORDER BY is_featured DESC, id DESC");
    $foodStmt->execute([$restId]);
}
$foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="assets/css/style.css" />
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

        /* Menu section */
        .restd-menu { padding: 0 60px 80px; }
        .restd-menu-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 20px; margin-bottom: 24px;
        }
        .restd-menu-filters {
            display: flex; gap: 10px; overflow-x: auto;
            margin-bottom: 24px; padding-bottom: 4px;
        }
        .restd-menu-filters::-webkit-scrollbar { display: none; }

        .restd-menu-empty {
            text-align: center; padding: 60px 20px;
            background: #fff; border-radius: 24px; box-shadow: var(--shadow);
        }
        .restd-menu-empty .empty-icon { font-size: 3.5rem; margin-bottom: 16px; }
        .restd-menu-empty h3 { font-family: 'Syne', sans-serif; color: var(--dark); margin-bottom: 8px; }
        .restd-menu-empty p { color: var(--muted); }

        @media (max-width: 768px) {
            .breadcrumb, .restd-info, .restd-menu { padding-left: 20px; padding-right: 20px; }
            .restd-hero { margin: 0 20px 24px; border-radius: 24px; min-height: 220px; }
            .restd-hero-content { padding: 28px 24px; }
            .restd-info-card { min-width: 120px; padding: 14px; }
        }
    </style>
</head>
<body>
    <?php include 'sections/navbar.php'; ?>

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
        <div style="padding: 0 60px 32px;">
            <div style="background:#fff; border-radius:24px; padding:28px; box-shadow:var(--shadow);">
                <div style="font-weight:800; font-size:0.78rem; letter-spacing:2px; text-transform:uppercase; color:var(--orange); margin-bottom:10px;">About</div>
                <p style="color:var(--muted); line-height:1.8; font-size:1.02rem;"><?php echo nl2br(htmlspecialchars($rest['description'])); ?></p>
            </div>
        </div>

        <!-- Menu Section -->
        <div class="restd-menu">
            <div class="restd-menu-header">
                <div>
                    <div class="section-tag">🍽️ Menu</div>
                    <div class="section-title"><?php echo htmlspecialchars($rest['name']); ?>'s Menu</div>
                </div>
                <div class="results-count" style="font-weight:700; color:var(--dark);">
                    <span style="color:var(--orange);"><?php echo count($foods); ?></span> items
                </div>
            </div>

            <?php if (!empty($menuCategories)): ?>
                <div class="restd-menu-filters">
                    <a href="restaurant.php?id=<?php echo $restId; ?>"
                       class="filter-tab <?php echo $menuCat === 'all' ? 'active' : ''; ?>">
                        🍽️ All
                        <span class="tab-count"><?php echo array_sum(array_column($menuCategories, 'count')); ?></span>
                    </a>
                    <?php foreach ($menuCategories as $mc): ?>
                        <a href="restaurant.php?id=<?php echo $restId; ?>&cat=<?php echo urlencode($mc['category']); ?>"
                           class="filter-tab <?php echo $menuCat === $mc['category'] ? 'active' : ''; ?>">
                            <?php echo $catEmojis[$mc['category']] ?? '🍴'; ?>
                            <?php echo htmlspecialchars($mc['category']); ?>
                            <span class="tab-count"><?php echo $mc['count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($foods)): ?>
                <div class="restd-menu-empty">
                    <div class="empty-icon">🍽️</div>
                    <h3>No menu items yet</h3>
                    <p>This restaurant hasn't added any food items to their menu yet.</p>
                </div>
            <?php else: ?>
                <div class="foods-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                    <?php foreach ($foods as $food): ?>
                        <a href="food_detail.php?id=<?php echo (int) $food['id']; ?>" class="food-card-link">
                        <article class="food-card">
                            <div class="food-img">
                                <?php if (!empty($food['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($food['image_path']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="food-photo">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($food['emoji']); ?>
                                <?php endif; ?>
                                <?php if (!empty($food['badge'])): ?>
                                    <span class="food-badge<?php echo $food['badge'] === 'New' ? ' new' : ''; ?>">
                                        <?php echo htmlspecialchars($food['badge']); ?>
                                    </span>
                                <?php endif; ?>
                                <div class="food-fav"><?php echo $food['is_favorite'] ? '❤️' : '🤍'; ?></div>
                            </div>
                            <div class="food-info">
                                <div class="food-meta">
                                    <span class="food-category"><?php echo htmlspecialchars($food['category']); ?></span>
                                    <span class="food-rating">⭐ <?php echo htmlspecialchars($food['rating']); ?></span>
                                </div>
                                <div class="food-name"><?php echo htmlspecialchars($food['name']); ?></div>
                                <div class="food-desc"><?php echo htmlspecialchars($food['description']); ?></div>
                                <div class="food-footer">
                                    <div>
                                        <div class="food-time">🕐 <?php echo htmlspecialchars($food['delivery_time']); ?></div>
                                        <div class="food-price">Rs. <?php echo number_format((float) $food['price'], 2); ?></div>
                                    </div>
                                    <form action="actions/add_to_cart.php" method="post" onclick="event.stopPropagation();">
                                        <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>" />
                                        <input type="hidden" name="price" value="<?php echo (float) $food['price']; ?>" />
                                        <button class="add-btn" type="submit" onclick="event.preventDefault(); event.stopPropagation(); this.closest('form').submit();">+</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'sections/footer.php'; ?>

    <button class="cart-fab" type="button" aria-label="Open cart" onclick="window.location.href='cart.php'">
        🛒
        <span class="cart-count" id="cartCount"><?php echo $cartCount; ?></span>
    </button>

    <script src="assets/js/script.js"></script>
</body>
</html>
