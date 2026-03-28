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

// Get active category filter
$activeCategory = isset($_GET['category']) ? trim($_GET['category']) : 'all';

// Get all distinct categories from database
$catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM foods GROUP BY category ORDER BY category");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch foods (filtered or all)
if ($activeCategory !== 'all') {
    $foodStmt = $pdo->prepare("SELECT * FROM foods WHERE category = ? ORDER BY is_featured DESC, id DESC");
    $foodStmt->execute([$activeCategory]);
} else {
    $foodStmt = $pdo->query("SELECT * FROM foods ORDER BY is_featured DESC, id DESC");
}
$foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

// Category emoji map
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
    <title><?php echo $activeCategory !== 'all' ? htmlspecialchars($activeCategory) . ' — ' : ''; ?>Full Menu — SwiftBite</title>
    <meta name="description" content="Browse the full SwiftBite menu. Order delicious food from our wide range of categories." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        /* ── Menu Page Specific Styles ── */
        .menu-page {
            padding-top: 100px;
            min-height: 100vh;
        }
        .menu-hero {
            text-align: center;
            padding: 40px 60px 20px;
        }
        .menu-hero .section-tag {
            display: inline-block;
            margin-bottom: 10px;
        }
        .menu-hero .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            margin-bottom: 12px;
        }
        .menu-hero p {
            color: var(--muted);
            font-size: 1.05rem;
            max-width: 500px;
            margin: 0 auto 32px;
            line-height: 1.7;
        }

        /* ── Category Filter Tabs ── */
        .menu-filters {
            display: flex;
            gap: 10px;
            padding: 0 60px 16px;
            overflow-x: auto;
            justify-content: center;
            flex-wrap: wrap;
        }
        .menu-filters::-webkit-scrollbar { display: none; }
        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 999px;
            border: 2px solid var(--cream2);
            background: #fff;
            color: var(--text);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
            text-decoration: none;
        }
        .filter-tab:hover {
            border-color: var(--orange);
            color: var(--orange);
            transform: translateY(-2px);
        }
        .filter-tab.active {
            background: var(--orange);
            color: #fff;
            border-color: var(--orange);
            box-shadow: 0 6px 20px rgba(255, 79, 0, 0.3);
        }
        .filter-tab .tab-count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
        }
        .filter-tab.active .tab-count {
            background: rgba(255,255,255,0.25);
        }

        /* ── Menu Grid ── */
        .menu-content {
            padding: 24px 60px 80px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        .menu-results-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 16px;
        }
        .results-count {
            font-weight: 700;
            color: var(--dark);
        }
        .results-count span {
            color: var(--orange);
        }

        /* ── Empty state ── */
        .menu-empty {
            text-align: center;
            padding: 80px 20px;
            grid-column: 1 / -1;
        }
        .menu-empty .empty-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }
        .menu-empty h3 {
            font-family: 'Syne', sans-serif;
            color: var(--dark);
            font-size: 1.4rem;
            margin-bottom: 8px;
        }
        .menu-empty p {
            color: var(--muted);
            margin-bottom: 24px;
        }
        .menu-empty a {
            display: inline-flex;
            padding: 12px 28px;
            background: var(--orange);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }
        .menu-empty a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255,79,0,0.35);
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .menu-hero { padding: 24px 20px 12px; }
            .menu-filters { padding: 0 20px 12px; justify-content: flex-start; flex-wrap: nowrap; }
            .menu-content { padding: 16px 20px 60px; }
            .menu-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'sections/navbar.php'; ?>

    <div class="menu-page">
        <!-- Hero Header -->
        <div class="menu-hero">
            <div class="section-tag">🍽️ Our Menu</div>
            <div class="section-title">Explore Our<br />Full Menu</div>
            <p>Discover all the delicious dishes we have to offer. Filter by category to find exactly what you're craving.</p>
        </div>

        <!-- Category Filter Tabs -->
        <div class="menu-filters">
            <a href="menu.php" class="filter-tab <?php echo $activeCategory === 'all' ? 'active' : ''; ?>">
                🍽️ All
                <span class="tab-count"><?php echo array_sum(array_column($categories, 'count')); ?></span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="menu.php?category=<?php echo urlencode($cat['category']); ?>"
                   class="filter-tab <?php echo $activeCategory === $cat['category'] ? 'active' : ''; ?>">
                    <?php echo $catEmojis[$cat['category']] ?? '🍴'; ?>
                    <?php echo htmlspecialchars($cat['category']); ?>
                    <span class="tab-count"><?php echo $cat['count']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Menu Grid -->
        <div class="menu-content">
            <div class="menu-results-info">
                <div class="results-count">
                    Showing <span><?php echo count($foods); ?></span> items
                    <?php if ($activeCategory !== 'all'): ?>
                        in <span><?php echo htmlspecialchars($activeCategory); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($activeCategory !== 'all'): ?>
                    <a href="menu.php" class="view-all">View All →</a>
                <?php endif; ?>
            </div>

            <div class="menu-grid">
                <?php if (empty($foods)): ?>
                    <div class="menu-empty">
                        <div class="empty-icon">🔍</div>
                        <h3>No items found</h3>
                        <p>There are no food items in this category yet.</p>
                        <a href="menu.php">View All Items</a>
                    </div>
                <?php else: ?>
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
                                        <button class="add-btn" type="submit" aria-label="Add <?php echo htmlspecialchars($food['name']); ?> to cart" onclick="event.preventDefault(); event.stopPropagation(); this.closest('form').submit();">+</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
