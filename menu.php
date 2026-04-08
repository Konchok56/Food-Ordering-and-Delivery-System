<?php
require_once 'core/bootstrap.php';

// ── Search / Filter params ──
$keyword       = trim($_GET['keyword'] ?? '');
$activeCategory = trim($_GET['category'] ?? '');
$activeCity    = trim($_GET['city'] ?? '');

// Get all distinct categories
$catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM foods GROUP BY category ORDER BY category");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Build search query ──
$where  = [];
$params = [];

if ($keyword !== '') {
    $where[]  = "(f.name LIKE ? OR f.description LIKE ? OR f.category LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}
if ($activeCategory !== '') {
    $where[]  = "f.category = ?";
    $params[] = $activeCategory;
}

// City filter needs a restaurant join
$join = '';
if ($activeCity !== '') {
    $join     = "LEFT JOIN restaurants r ON f.restaurant_id = r.id";
    $where[]  = "(r.city = ? OR f.restaurant_id IS NULL)";
    $params[] = $activeCity;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT f.* FROM foods f $join $whereSQL ORDER BY f.is_featured DESC, f.id DESC";

if ($params) {
    $foodStmt = $pdo->prepare($sql);
    $foodStmt->execute($params);
} else {
    $foodStmt = $pdo->query($sql);
}
$foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

// Build page title
$pageTitle = 'Full Menu — SwiftBite';
if ($keyword) $pageTitle = "Search: $keyword — SwiftBite";
elseif ($activeCategory) $pageTitle = "$activeCategory — SwiftBite";

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
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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

        /* ── Search Bar on menu page ── */
        .menu-search-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            background: #fff;
            border-radius: 20px;
            padding: 16px 22px;
            box-shadow: var(--shadow);
            max-width: 780px;
            margin: 0 auto 32px;
            border: 2px solid var(--cream2);
            transition: border-color 0.2s;
        }
        .menu-search-bar:focus-within { border-color: var(--orange); }
        .menu-search-bar svg { color: var(--muted); flex-shrink: 0; }
        .menu-search-bar input {
            flex: 1; border: none; outline: none;
            font-size: 1rem; color: var(--text);
            background: none; font-family: 'DM Sans', sans-serif;
        }
        .menu-search-bar input::placeholder { color: var(--muted); }
        .menu-search-clear {
            background: var(--cream2); border: none;
            width: 28px; height: 28px; border-radius: 50%;
            cursor: pointer; font-size: 0.85rem; color: var(--muted);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; flex-shrink: 0;
        }
        .menu-search-clear:hover { background: var(--orange); color: #fff; }
        .search-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,79,0,0.1); color: var(--orange);
            padding: 5px 14px; border-radius: 999px;
            font-size: 0.82rem; font-weight: 700;
            margin-left: 8px;
        }
        .city-tag {
            background: rgba(52,199,89,0.1); color: #1a7a34;
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
    <?php include 'templates/navbar.php'; ?>

    <div class="menu-page">
        <!-- Hero Header -->
        <div class="menu-hero">
            <div class="section-tag">🍽️ Our Menu</div>
            <div class="section-title">Explore Our<br />Full Menu</div>
            <p>Discover all the delicious dishes we have to offer. Filter by category to find exactly what you're craving.</p>
        </div>

        <!-- Search bar (pre-filled if coming from search) -->
        <form class="menu-search-bar ac-wrap" action="menu.php" method="GET" id="menuSearchForm">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" name="keyword" id="menuSearchInput"
                   value="<?php echo htmlspecialchars($keyword); ?>"
                   placeholder="Search food, category, description…"
                   autocomplete="off"
                   data-autocomplete
                   aria-label="Search menu"
                   aria-autocomplete="list"
                   aria-haspopup="listbox">
            <?php if ($activeCategory): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($activeCategory); ?>">
            <?php endif; ?>
            <?php if ($activeCity): ?>
                <input type="hidden" name="city" value="<?php echo htmlspecialchars($activeCity); ?>">
            <?php endif; ?>
            <?php if ($keyword): ?>
                <button type="button" class="menu-search-clear" onclick="window.location='menu.php'" title="Clear search">✕</button>
            <?php endif; ?>
        </form>

        <!-- Category Filter Tabs -->
        <div class="menu-filters">
            <a href="menu.php<?php echo $keyword ? '?keyword='.urlencode($keyword) : ''; ?>" class="filter-tab <?php echo $activeCategory === '' ? 'active' : ''; ?>">
                🍽️ All
                <span class="tab-count"><?php echo array_sum(array_column($categories, 'count')); ?></span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="menu.php?category=<?php echo urlencode($cat['category']); ?><?php echo $keyword ? '&keyword='.urlencode($keyword) : ''; ?>"
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
                    <?php if ($keyword || $activeCategory || $activeCity): ?>
                        Found <span><?php echo count($foods); ?></span> result<?php echo count($foods) !== 1 ? 's' : ''; ?>
                        <?php if ($keyword): ?>
                            for <span>"<?php echo htmlspecialchars($keyword); ?>"</span>
                        <?php endif; ?>
                        <?php if ($activeCategory): ?>
                            <span class="search-tag"><?php echo $catEmojis[$activeCategory] ?? '🍴'; ?> <?php echo htmlspecialchars($activeCategory); ?></span>
                        <?php endif; ?>
                        <?php if ($activeCity): ?>
                            <span class="search-tag city-tag">📍 <?php echo htmlspecialchars($activeCity); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        Showing <span><?php echo count($foods); ?></span> items
                    <?php endif; ?>
                </div>
                <?php if ($keyword || $activeCategory || $activeCity): ?>
                    <a href="menu.php" class="view-all">Clear filters ✕</a>
                <?php endif; ?>
            </div>

            <div class="menu-grid">
                <?php if (empty($foods)): ?>
                    <div class="menu-empty">
                        <div class="empty-icon">🔍</div>
                        <h3>No results found</h3>
                        <p>
                            <?php if ($keyword): ?>
                                Nothing matched "<strong><?php echo htmlspecialchars($keyword); ?></strong>".
                                Try a different keyword or browse all items.
                            <?php else: ?>
                                There are no food items in this category yet.
                            <?php endif; ?>
                        </p>
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

                                    <form action="actions/add_to_orders/cart.php" method="post" onclick="event.stopPropagation();">
                                        <input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>" />
                                        <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>" />
                                        <input type="hidden" name="price" value="<?php echo (float) $food['price']; ?>" />
                                        <button class="add-btn" type="submit" aria-label="Add <?php echo htmlspecialchars($food['name']); ?> to cart" data-name="<?php echo htmlspecialchars($food['name']); ?>">+</button>
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

    <?php include 'templates/footer.php'; ?>

    <?php include 'templates/floating_menu.php'; ?>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/search_autocomplete.js"></script>
</body>
</html>
