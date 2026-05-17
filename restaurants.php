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

// Cart count from DB
include('core/db.php');
include('core/cart_helper.php');
$cartCount = isset($_SESSION['user_id']) ? getCartCount($pdo, $_SESSION['user_id']) : 0;

// Filters
$activeCuisine = isset($_GET['cuisine']) ? trim($_GET['cuisine']) : 'all';
$activeCity = isset($_GET['city']) ? trim($_GET['city']) : 'all';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Get distinct cuisine types
$cuisineStmt = $pdo->query("SELECT cuisine_type, COUNT(*) as count FROM restaurants GROUP BY cuisine_type ORDER BY cuisine_type");
$cuisines = $cuisineStmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct cities
$cityStmt = $pdo->query("SELECT DISTINCT city FROM restaurants ORDER BY city");
$cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

// Build query
$where = [];
$params = [];

if ($activeCuisine !== 'all') {
    $where[] = "cuisine_type = ?";
    $params[] = $activeCuisine;
}
if ($activeCity !== 'all') {
    $where[] = "city = ?";
    $params[] = $activeCity;
}
if ($searchQuery !== '') {
    $where[] = "(name LIKE ? OR description LIKE ? OR cuisine_type LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql = "SELECT * FROM restaurants";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY is_featured DESC, rating DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Food count per restaurant
$foodCountStmt = $pdo->query("SELECT restaurant_id, COUNT(*) as cnt FROM foods WHERE restaurant_id IS NOT NULL GROUP BY restaurant_id");
$foodCounts = [];
while ($row = $foodCountStmt->fetch(PDO::FETCH_ASSOC)) {
    $foodCounts[$row['restaurant_id']] = $row['cnt'];
}

// Cuisine emoji map
$cuisineEmojis = [
    'Fast Food' => '<i class="fa-solid fa-burger"></i>', 'Nepali' => '<i class="fa-solid fa-bowl-food"></i>', 'Italian' => '<i class="fa-solid fa-pizza-slice"></i>', 'Chinese' => '<i class="fa-solid fa-bowl-rice"></i>',
    'Japanese' => '<i class="fa-solid fa-fish"></i>', 'Healthy' => '<i class="fa-solid fa-leaf"></i>', 'Indian' => '<i class="fa-solid fa-bowl-food"></i>', 'Thai' => '<i class="fa-solid fa-bowl-food"></i>',
    'Mexican' => '<i class="fa-solid fa-bowl-food"></i>', 'Korean' => '<i class="fa-solid fa-bowl-food"></i>', 'BBQ' => '<i class="fa-solid fa-drumstick-bite"></i>', 'Cafe' => '<i class="fa-solid fa-mug-hot"></i>',
    'Bakery' => '<i class="fa-solid fa-cake-candles"></i>', 'Seafood' => '<i class="fa-solid fa-fish"></i>', 'Mixed' => '<i class="fa-solid fa-utensils"></i>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $activeCuisine !== 'all' ? htmlspecialchars($activeCuisine) . ' Restaurants — ' : ''; ?>Restaurants — SwiftBite</title>
    <meta name="description" content="Browse top-rated restaurants near you. Order from your favorite local spots on SwiftBite." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=8" />
    <style>
        .rest-page { padding-top: 100px; min-height: 100vh; }

        /* Hero */
        .rest-hero {
            text-align: center;
            padding: 40px 60px 20px;
        }
        .rest-hero .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            margin-bottom: 12px;
        }
        .rest-hero p {
            color: var(--muted);
            font-size: 1.05rem;
            max-width: 520px;
            margin: 0 auto 28px;
            line-height: 1.7;
        }

        /* Search */
        .rest-search {
            max-width: 700px;
            margin: 0 auto 28px;
            display: flex;
            gap: 10px;
            padding: 0 60px;
        }
        .rest-search input {
            flex: 1;
            padding: 14px 22px;
            border: 2px solid var(--cream2);
            border-radius: 16px;
            font-size: 1rem;
            background: #fff;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s;
        }
        .rest-search input:focus { border-color: var(--orange); }
        .rest-search select {
            padding: 14px 18px;
            border: 2px solid var(--cream2);
            border-radius: 16px;
            font-size: 0.92rem;
            background: #fff;
            color: var(--text);
            cursor: pointer;
            font-weight: 600;
            outline: none;
        }
        .rest-search button {
            padding: 14px 28px;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .rest-search button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255,79,0,0.35);
        }

        /* Cuisine Tabs */
        .rest-filters {
            display: flex;
            gap: 10px;
            padding: 0 60px 16px;
            overflow-x: auto;
            justify-content: center;
            flex-wrap: wrap;
        }
        .rest-filters::-webkit-scrollbar { display: none; }

        /* Grid */
        .rest-content { padding: 24px 60px 80px; }
        .rest-results {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .rest-results .results-count span { color: var(--orange); }

        .rest-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
        }

        /* Restaurant Card */
        .rest-card-link {
            text-decoration: none !important;
            color: inherit;
            display: block;
            border-radius: 28px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .rest-card-link:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 48px rgba(255, 79, 0, 0.18);
        }
        .rest-card-link *,
        .rest-card-link:visited,
        .rest-card-link:visited * {
            text-decoration: none !important;
        }

        .rest-card {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .rest-card-cover {
            height: 180px;
            background: linear-gradient(135deg, #fff6ea, rgba(255, 79, 0, 0.12));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4.5rem;
            position: relative;
            overflow: hidden;
        }
        .rest-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .rest-card-link:hover .rest-card-cover img {
            transform: scale(1.05);
        }
        .rest-status {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .rest-status.open {
            background: rgba(52, 199, 89, 0.9);
            color: #fff;
        }
        .rest-status.closed {
            background: rgba(255, 59, 48, 0.9);
            color: #fff;
        }
        .rest-cuisine-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--orange);
        }

        .rest-card-body { padding: 22px; }
        .rest-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }
        .rest-card-name {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--dark);
            line-height: 1.3;
        }
        .rest-card-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            color: var(--dark);
            font-size: 0.92rem;
            white-space: nowrap;
        }
        .rest-card-desc {
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .rest-card-meta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .rest-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: var(--cream);
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text);
        }

        /* Empty state */
        .rest-empty {
            text-align: center;
            padding: 80px 20px;
            grid-column: 1 / -1;
        }
        .rest-empty .empty-icon { font-size: 4rem; margin-bottom: 16px; }
        .rest-empty h3 { font-family: 'Syne', sans-serif; color: var(--dark); font-size: 1.4rem; margin-bottom: 8px; }
        .rest-empty p { color: var(--muted); margin-bottom: 24px; }
        .rest-empty a {
            display: inline-flex; padding: 12px 28px; background: var(--orange);
            color: #fff; border-radius: 999px; font-weight: 700; text-decoration: none;
        }

        @media (max-width: 768px) {
            .rest-hero { padding: 24px 20px 12px; }
            .rest-search { padding: 0 20px; flex-direction: column; }
            .rest-filters { padding: 0 20px 12px; justify-content: flex-start; flex-wrap: nowrap; }
            .rest-content { padding: 16px 20px 60px; }
            .rest-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'templates/navbar.php'; ?>

    <div class="rest-page">
        <div class="rest-hero">
            <div class="section-tag">🏪 Restaurants</div>
            <div class="section-title">Discover Restaurants<br />Near You</div>
            <p>Browse top-rated restaurants and order your favorite meals. Fresh food from the best local spots.</p>
        </div>

        <!-- Search -->
        <form class="rest-search" method="GET" action="restaurants.php">
            <div class="ac-wrap" style="flex: 1; display: flex;">
                <input style="flex: 1;" type="text" name="q" placeholder="Search restaurants..." value="<?php echo htmlspecialchars($searchQuery); ?>" autocomplete="off" data-autocomplete aria-label="Search restaurants" aria-autocomplete="list" aria-haspopup="listbox">
            </div>
            <select name="city">
                <option value="all"><i class="fa-solid fa-location-dot"></i> All Cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $activeCity === $c ? 'selected' : ''; ?>>
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($activeCuisine !== 'all'): ?>
                <input type="hidden" name="cuisine" value="<?php echo htmlspecialchars($activeCuisine); ?>">
            <?php endif; ?>
            <button type="submit">Search</button>
        </form>

        <!-- Cuisine Tabs -->
        <div class="rest-filters">
            <a href="restaurants.php<?php echo $activeCity !== 'all' ? '?city=' . urlencode($activeCity) : ''; ?>"
               class="filter-tab <?php echo $activeCuisine === 'all' ? 'active' : ''; ?>">
                <i class="fa-solid fa-utensils"></i> All
                <span class="tab-count"><?php echo array_sum(array_column($cuisines, 'count')); ?></span>
            </a>
            <?php foreach ($cuisines as $cuisine): ?>
                <a href="restaurants.php?cuisine=<?php echo urlencode($cuisine['cuisine_type']); ?><?php echo $activeCity !== 'all' ? '&city=' . urlencode($activeCity) : ''; ?>"
                   class="filter-tab <?php echo $activeCuisine === $cuisine['cuisine_type'] ? 'active' : ''; ?>">
                    <?php echo $cuisineEmojis[$cuisine['cuisine_type']] ?? '<i class="fa-solid fa-utensils"></i>'; ?>
                    <?php echo htmlspecialchars($cuisine['cuisine_type']); ?>
                    <span class="tab-count"><?php echo $cuisine['count']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Results -->
        <div class="rest-content">
            <div class="rest-results">
                <div class="results-count" style="font-weight:700; color:var(--dark);">
                    Showing <span><?php echo count($restaurants); ?></span> restaurants
                    <?php if ($activeCuisine !== 'all'): ?> in <span><?php echo htmlspecialchars($activeCuisine); ?></span><?php endif; ?>
                    <?php if ($activeCity !== 'all'): ?> — <span><?php echo htmlspecialchars($activeCity); ?></span><?php endif; ?>
                </div>
                <?php if ($activeCuisine !== 'all' || $activeCity !== 'all' || $searchQuery !== ''): ?>
                    <a href="restaurants.php" class="view-all">Clear Filters <i class="fa-solid fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>

            <div class="rest-grid">
                <?php if (empty($restaurants)): ?>
                    <div class="rest-empty">
                        <div class="empty-icon">🔍</div>
                        <h3>No restaurants found</h3>
                        <p>Try adjusting your search or filters.</p>
                        <a href="restaurants.php">View All Restaurants</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($restaurants as $rest): ?>
                        <a href="restaurant.php?id=<?php echo (int) $rest['id']; ?>" class="rest-card-link">
                            <div class="rest-card">
                                <div class="rest-card-cover">
                                    <?php if (!empty($rest['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($rest['image_path']); ?>" alt="<?php echo htmlspecialchars($rest['name']); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($rest['logo_emoji']); ?>
                                    <?php endif; ?>
                                    <span class="rest-status <?php echo $rest['is_open'] ? 'open' : 'closed'; ?>">
                                        <?php echo $rest['is_open'] ? '● Open' : '● Closed'; ?>
                                    </span>
                                    <span class="rest-cuisine-badge">
                                        <?php echo $cuisineEmojis[$rest['cuisine_type']] ?? '<i class="fa-solid fa-utensils"></i>'; ?>
                                        <?php echo htmlspecialchars($rest['cuisine_type']); ?>
                                    </span>
                                </div>
                                <div class="rest-card-body">
                                    <div class="rest-card-top">
                                        <div class="rest-card-name"><?php echo htmlspecialchars($rest['name']); ?></div>
                                        <div class="rest-card-rating"><i class="fa-solid fa-star" style="color:#f59e0b"></i> <?php echo htmlspecialchars($rest['rating']); ?></div>
                                    </div>
                                    <div class="rest-card-desc"><?php echo htmlspecialchars($rest['description']); ?></div>
                                    <div class="rest-card-meta">
                                        <span class="rest-meta-pill">🕐 <?php echo htmlspecialchars($rest['delivery_time']); ?></span>
                                        <span class="rest-meta-pill"><i class="fa-solid fa-truck"></i> Rs. <?php echo number_format((float) $rest['delivery_fee'], 0); ?></span>
                                        <span class="rest-meta-pill"><i class="fa-solid fa-box"></i> Min Rs. <?php echo number_format((float) $rest['min_order'], 0); ?></span>
                                        <?php if (isset($foodCounts[$rest['id']])): ?>
                                            <span class="rest-meta-pill"><i class="fa-solid fa-utensils"></i> <?php echo $foodCounts[$rest['id']]; ?> items</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
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
