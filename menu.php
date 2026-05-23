<?php
require_once 'core/bootstrap.php';

$keyword        = trim($_GET['keyword']    ?? '');
$activeCategory = trim($_GET['category']   ?? '');
$activeCity     = trim($_GET['city']       ?? '');
$sortBy         = trim($_GET['sort']       ?? 'featured');
$minPrice       = trim($_GET['min_price']  ?? '');
$maxPrice       = trim($_GET['max_price']  ?? '');
$minRating      = trim($_GET['min_rating'] ?? '');

$catStmt    = $pdo->query("SELECT category, emoji, COUNT(*) as count FROM foods GROUP BY category ORDER BY category");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$where  = ["1=1"];
$params = [];

if ($keyword !== '') {
    $where[]  = "(f.name LIKE ? OR f.description LIKE ? OR f.category LIKE ?)";
    $params[] = "%$keyword%"; $params[] = "%$keyword%"; $params[] = "%$keyword%";
}
if ($activeCategory !== '') { $where[] = "f.category = ?"; $params[] = $activeCategory; }
if ($minPrice !== '')        { $where[] = "f.price >= ?";   $params[] = (float)$minPrice; }
if ($maxPrice !== '')        { $where[] = "f.price <= ?";   $params[] = (float)$maxPrice; }
if ($minRating !== '')       { $where[] = "f.rating >= ?";  $params[] = (float)$minRating; }

$join = '';
if ($activeCity !== '') {
    $join     = "LEFT JOIN restaurants r ON f.restaurant_id = r.id";
    $where[]  = "(r.city = ? OR f.restaurant_id IS NULL)";
    $params[] = $activeCity;
}

$orderMap = ['price_asc'=>'f.price ASC','price_desc'=>'f.price DESC','rating'=>'f.rating DESC','newest'=>'f.id DESC'];
$orderSQL = $orderMap[$sortBy] ?? 'f.is_featured DESC, f.id DESC';
$whereSQL = 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT f.* FROM foods f $join $whereSQL ORDER BY $orderSQL";

$foodStmt = $pdo->prepare($sql);
$foodStmt->execute($params);
$foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = __('full_menu', 'Full Menu') . ' — SwiftBite';
if ($keyword) $pageTitle = __('search', 'Search') . ": " . htmlspecialchars($keyword) . ' — SwiftBite';
elseif ($activeCategory) $pageTitle = htmlspecialchars(__($activeCategory, $activeCategory)) . ' — SwiftBite';

$getCatEmoji = function($n) use ($categories) {
    foreach ($categories as $c) { if ($c['category']===$n) return $c['emoji'] ?: ''; }
    return '';
};

function menuUrl($extra=[]) {
    global $keyword,$activeCategory,$activeCity,$sortBy,$minPrice,$maxPrice,$minRating;
    $p = [];
    if ($keyword)        $p['keyword']    = $keyword;
    if ($activeCategory) $p['category']   = $activeCategory;
    if ($activeCity)     $p['city']       = $activeCity;
    if ($sortBy !== 'featured') $p['sort']= $sortBy;
    if ($minPrice !== '') $p['min_price'] = $minPrice;
    if ($maxPrice !== '') $p['max_price'] = $maxPrice;
    if ($minRating !== '') $p['min_rating']= $minRating;
    foreach ($extra as $k=>$v) { if ($v===null) unset($p[$k]); else $p[$k]=$v; }
    return 'menu.php'.($p ? '?'.http_build_query($p) : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo __('menu_meta_desc', 'Browse the full SwiftBite menu.'); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=8" />
    <style>
        .menu-page { padding-top: 100px; min-height: 100vh; background: var(--cream, #fff9f5); }

        /* Hero */
        .menu-hero { text-align:center; padding: 36px 60px 20px; }
        .menu-hero .section-tag { display:inline-block; margin-bottom:10px; }
        .menu-hero .section-title { font-size:clamp(2rem,4vw,3rem); margin-bottom:10px; }
        .menu-hero p { color:var(--muted); font-size:1rem; max-width:480px; margin:0 auto 28px; line-height:1.7; }

        /* Search */
        .menu-search-wrap { max-width:700px; margin:0 auto 24px; padding:0 24px; }
        .menu-search-bar {
            display:flex; gap:10px; align-items:center;
            background:#fff; border-radius:16px; padding:14px 20px;
            box-shadow:0 2px 16px rgba(0,0,0,0.07); border:2px solid var(--cream2,#f0e8e0);
            transition:border-color 0.2s;
        }
        .menu-search-bar:focus-within { border-color:var(--orange,#ff4f00); }
        .menu-search-bar svg { color:var(--muted); flex-shrink:0; }
        .menu-search-bar input { flex:1; border:none; outline:none; font-size:1rem; color:var(--text); background:none; font-family:'DM Sans',sans-serif; }
        .menu-search-bar input::placeholder { color:var(--muted); }
        .menu-search-clear { background:var(--cream2,#f0e8e0); border:none; width:28px; height:28px; border-radius:50%; cursor:pointer; font-size:0.85rem; color:var(--muted); display:flex; align-items:center; justify-content:center; transition:all 0.2s; flex-shrink:0; }
        .menu-search-clear:hover { background:var(--orange,#ff4f00); color:#fff; }

        /* Category tabs */
        .menu-filters { display:flex; gap:10px; padding:0 24px 20px; overflow-x:auto; flex-wrap:wrap; justify-content:center; }
        .menu-filters::-webkit-scrollbar { display:none; }
        .filter-tab { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border-radius:999px; border:2px solid var(--cream2,#f0e8e0); background:#fff; color:var(--text); font-weight:600; font-size:0.88rem; cursor:pointer; transition:all 0.22s; white-space:nowrap; text-decoration:none; }
        .filter-tab:hover { border-color:var(--orange,#ff4f00); color:var(--orange,#ff4f00); transform:translateY(-1px); }
        .filter-tab.active { background:var(--orange,#ff4f00); color:#fff; border-color:var(--orange,#ff4f00); box-shadow:0 4px 16px rgba(255,79,0,0.28); }
        .tab-count { background:rgba(0,0,0,0.1); padding:1px 7px; border-radius:999px; font-size:0.74rem; }
        .filter-tab.active .tab-count { background:rgba(255,255,255,0.25); }

        /* Main layout */
        .menu-body { display:grid; grid-template-columns:260px 1fr; gap:24px; padding:0 24px 80px; max-width:1400px; margin:0 auto; align-items:start; }

        /* Sidebar */
        .filter-sidebar { background:#fff; border-radius:20px; box-shadow:0 2px 20px rgba(0,0,0,0.07); padding:0; position:sticky; top:100px; overflow:hidden; box-sizing:border-box; width:100%; }
        .fs-section { padding:20px 22px 0; box-sizing:border-box; width:100%; overflow:hidden; }
        .fs-section:last-of-type { padding-bottom:22px; }
        .fs-title { font-size:0.72rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#aaa; margin-bottom:12px; display:flex; align-items:center; gap:7px; }
        .fs-title svg { color:var(--orange,#ff4f00); }
        .fs-divider { height:1px; background:var(--cream2,#f0e8e0); margin:16px 0 0; }

        /* Sort radio list */
        .sort-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px; }
        .sort-list li label {
            display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px;
            cursor:pointer; font-size:0.9rem; font-weight:500; color:var(--text); transition:background 0.15s;
        }
        .sort-list li label:hover { background:var(--cream,#fff9f5); }
        .sort-list li input[type=radio] { display:none; }
        .sort-radio-dot {
            width:16px; height:16px; border-radius:50%; border:2px solid #ccc;
            flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all 0.2s;
        }
        .sort-list li input[type=radio]:checked + label .sort-radio-dot { border-color:var(--orange,#ff4f00); background:var(--orange,#ff4f00); box-shadow:0 0 0 3px rgba(255,79,0,0.15); }
        .sort-list li input[type=radio]:checked + label .sort-radio-dot::after { content:''; width:6px; height:6px; border-radius:50%; background:#fff; display:block; }
        .sort-list li input[type=radio]:checked + label { color:var(--orange,#ff4f00); font-weight:700; }

        /* Price range */
        .price-row { display:flex; align-items:center; gap:6px; width:100%; box-sizing:border-box; }
        .price-row input[type=number] {
            width:0; flex:1; min-width:0;
            padding:9px 8px; border:2px solid var(--cream2,#f0e8e0);
            border-radius:10px; font-size:0.88rem; font-family:'DM Sans',sans-serif;
            color:var(--text); outline:none; transition:border-color 0.2s;
            -moz-appearance:textfield; background:var(--cream,#fff9f5);
            box-sizing:border-box;
        }
        .price-row input[type=number]::-webkit-inner-spin-button,
        .price-row input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
        .price-row input[type=number]:focus { border-color:var(--orange,#ff4f00); background:#fff; }
        .price-sep { color:#bbb; font-weight:700; flex-shrink:0; font-size:1rem; }

        /* Rating checkboxes */
        .rating-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px; }
        .rating-list li label {
            display:flex; align-items:center; justify-content:space-between; padding:8px 10px;
            border-radius:10px; cursor:pointer; font-size:0.88rem; font-weight:600; color:var(--text);
            transition:all 0.15s; border:2px solid transparent;
        }
        .rating-list li label:hover { background:var(--cream,#fff9f5); }
        .rating-list li input[type=radio] { display:none; }
        .rating-list li input[type=radio]:checked + label { color:var(--orange,#ff4f00); background:rgba(255,79,0,0.07); border-color:rgba(255,79,0,0.2); }
        .rating-check { width:18px; height:18px; border-radius:50%; border:2px solid #ddd; display:flex; align-items:center; justify-content:center; font-size:0.7rem; transition:all 0.2s; flex-shrink:0; }
        .rating-list li input[type=radio]:checked + label .rating-check { background:var(--orange,#ff4f00); border-color:var(--orange,#ff4f00); color:#fff; }

        /* Any Rating btn */
        .any-rating-btn { display:block; width:100%; padding:9px; border:2px solid var(--orange,#ff4f00); border-radius:10px; background:transparent; color:var(--orange,#ff4f00); font-family:'DM Sans',sans-serif; font-weight:700; font-size:0.88rem; cursor:pointer; text-align:center; margin-top:10px; transition:all 0.2s; text-decoration:none; }
        .any-rating-btn:hover { background:var(--orange,#ff4f00); color:#fff; }
        .any-rating-btn.active { background:var(--orange,#ff4f00); color:#fff; }

        /* Sidebar action buttons */
        .sb-btn-row { display:flex; gap:0; }
        .apply-btn { flex:1; padding:13px 8px; border:none; border-radius:0 0 0 20px; background:linear-gradient(135deg,var(--orange,#ff4f00),#e63600); color:#fff; font-family:'DM Sans',sans-serif; font-weight:800; font-size:0.9rem; cursor:pointer; text-align:center; margin-top:20px; transition:all 0.2s; letter-spacing:0.02em; box-shadow:0 4px 16px rgba(255,79,0,0.3); }
        .apply-btn:hover { box-shadow:0 8px 24px rgba(255,79,0,0.45); filter:brightness(1.05); }
        .reset-btn { flex:0 0 auto; padding:13px 16px; border:none; border-radius:0 0 20px 0; background:#f5f5f5; color:#888; font-family:'DM Sans',sans-serif; font-weight:700; font-size:0.88rem; cursor:pointer; text-align:center; margin-top:20px; transition:all 0.2s; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; border-left:1px solid #e8e8e8; }
        .reset-btn:hover { background:#ffe8e0; color:var(--orange,#ff4f00); }

        /* Main content */
        .menu-main {}
        .menu-results-info { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:12px; }
        .results-count { font-weight:700; color:var(--dark); font-size:0.95rem; }
        .results-count span { color:var(--orange,#ff4f00); }
        .view-all { color:var(--orange,#ff4f00); font-weight:700; font-size:0.88rem; text-decoration:none; }
        .view-all:hover { text-decoration:underline; }
        .search-tag { display:inline-flex; align-items:center; gap:4px; background:rgba(255,79,0,0.1); color:var(--orange,#ff4f00); padding:3px 12px; border-radius:999px; font-size:0.8rem; font-weight:700; margin-left:6px; }

        /* Grid */
        .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px; }

        /* Empty */
        .menu-empty { text-align:center; padding:80px 20px; grid-column:1/-1; }
        .menu-empty .empty-icon { font-size:3.5rem; margin-bottom:14px; }
        .menu-empty h3 { font-family:'Syne',sans-serif; color:var(--dark); font-size:1.3rem; margin-bottom:8px; }
        .menu-empty p { color:var(--muted); margin-bottom:20px; }
        .menu-empty a { display:inline-flex; padding:11px 26px; background:var(--orange,#ff4f00); color:#fff; border-radius:999px; font-weight:700; text-decoration:none; }

        /* Responsive */
        @media (max-width: 960px) {
            .menu-body { grid-template-columns:1fr; }
            .filter-sidebar { position:static; }
            .menu-filters { justify-content:flex-start; flex-wrap:nowrap; overflow-x:auto; }
            .menu-hero { padding:24px 16px 12px; }
        }
        @media (max-width: 640px) {
            .menu-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php include 'templates/navbar.php'; ?>

<div class="menu-page">
    <!-- Hero -->
    <div class="menu-hero">
        <div class="section-tag"><i class="fa-solid fa-utensils"></i> <?php echo __('our_menu', 'Our Menu'); ?></div>
        <div class="section-title"><?php echo __('explore_our_menu', 'Explore Our<br />Full Menu'); ?></div>
        <p><?php echo __('discover_delicious_dishes', 'Discover all the delicious dishes we have to offer.'); ?></p>
    </div>

    <!-- Search -->
    <div class="menu-search-wrap">
        <form class="menu-search-bar ac-wrap" action="menu.php" method="GET" id="menuSearchForm">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
            <input type="text" name="keyword" id="menuSearchInput"
                   value="<?php echo htmlspecialchars($keyword); ?>"
                   placeholder="<?php echo __('search_food_placeholder', 'Search food, category...'); ?>"
                   autocomplete="off" data-autocomplete aria-label="Search menu">
            <?php if ($activeCategory): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($activeCategory); ?>"><?php endif; ?>
            <?php if ($sortBy !== 'featured'): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>"><?php endif; ?>
            <?php if ($minPrice !== ''): ?><input type="hidden" name="min_price" value="<?php echo (float)$minPrice; ?>"><?php endif; ?>
            <?php if ($maxPrice !== ''): ?><input type="hidden" name="max_price" value="<?php echo (float)$maxPrice; ?>"><?php endif; ?>
            <?php if ($minRating !== ''): ?><input type="hidden" name="min_rating" value="<?php echo (float)$minRating; ?>"><?php endif; ?>
            <?php if ($keyword): ?>
                <button type="button" class="menu-search-clear" onclick="window.location='menu.php'" title="Clear">&times;</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Tabs -->
    <div class="menu-filters">
        <a href="<?php echo menuUrl(['category'=>null]); ?>" class="filter-tab <?php echo $activeCategory===''?'active':''; ?>">
            <?php echo __('all', 'All'); ?> <span class="tab-count"><?php echo t_num(array_sum(array_column($categories,'count'))); ?></span>
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?php echo menuUrl(['category'=>$cat['category']]); ?>"
               class="filter-tab <?php echo $activeCategory===$cat['category']?'active':''; ?>">
                <?php echo htmlspecialchars($cat['emoji'] ?: ''); ?>
                <?php echo htmlspecialchars(__($cat['category'], $cat['category'])); ?>
                <span class="tab-count"><?php echo t_num($cat['count']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Body: sidebar + grid -->
    <div class="menu-body">

        <!-- ── SIDEBAR ── -->
        <aside class="filter-sidebar">
            <form method="GET" action="menu.php" id="smartFilterForm">
                <?php if ($keyword): ?><input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>"><?php endif; ?>
                <?php if ($activeCategory): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($activeCategory); ?>"><?php endif; ?>

                <!-- Sort By -->
                <div class="fs-section">
                    <div class="fs-title">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 6h18M6 12h12M9 18h6"/></svg>
                        <?php echo __('sort_by', 'Sort By'); ?>
                    </div>
                    <ul class="sort-list">
                        <?php
                        $sorts = [
                            'featured' => __('sort_featured', 'Featured (Default)'),
                            'rating' => __('sort_rating', 'Top Rated'),
                            'newest' => __('sort_newest', 'Newest Arrivals'),
                            'price_asc' => __('sort_price_asc', 'Price: Low to High'),
                            'price_desc' => __('sort_price_desc', 'Price: High to Low')
                        ];
                        foreach ($sorts as $val=>$label): ?>
                            <li>
                                <input type="radio" name="sort" id="sort_<?php echo $val; ?>" value="<?php echo $val; ?>" <?php echo $sortBy===$val?'checked':''; ?>>
                                <label for="sort_<?php echo $val; ?>">
                                    <span class="sort-radio-dot"></span>
                                    <?php echo $label; ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="fs-divider"></div>

                <!-- Price Range -->
                <div class="fs-section" style="padding-top:16px;">
                    <div class="fs-title">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6"/></svg>
                        <?php echo __('price_range', 'Price Range'); ?>
                    </div>
                    <div class="price-row">
                        <input type="number" name="min_price" placeholder="<?php echo __('min', 'Min'); ?>" min="0" step="1"
                               value="<?php echo $minPrice!==''?(int)$minPrice:''; ?>">
                        <span class="price-sep">&mdash;</span>
                        <input type="number" name="max_price" placeholder="<?php echo __('max', 'Max'); ?>" min="0" step="1"
                               value="<?php echo $maxPrice!==''?(int)$maxPrice:''; ?>">
                    </div>
                </div>

                <div class="fs-divider"></div>

                <!-- Min Rating -->
                <div class="fs-section" style="padding-top:16px;">
                    <div class="fs-title">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" style="color:var(--orange,#ff4f00)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php echo __('min_rating_title', 'Min Rating'); ?>
                    </div>
                    <ul class="rating-list">
                        <?php
                        $ratings = ['4.5'=>sprintf(__('%s_stars', '%s+ Stars'), t_num('4.5')),'4'=>sprintf(__('%s_stars', '%s+ Stars'), t_num('4')),'3.5'=>sprintf(__('%s_stars', '%s+ Stars'), t_num('3.5'))];
                        foreach ($ratings as $val=>$label): ?>
                            <li>
                                <input type="radio" name="min_rating" id="r<?php echo str_replace('.','_',$val); ?>" value="<?php echo $val; ?>" <?php echo $minRating==$val?'checked':''; ?>>
                                <label for="r<?php echo str_replace('.','_',$val); ?>">
                                    <span><?php echo $label; ?></span>
                                    <span class="rating-check"><i class="fa-solid fa-check"></i></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo menuUrl(['min_rating'=>null]); ?>"
                       class="any-rating-btn <?php echo $minRating===''?'active':''; ?>"><?php echo __('any_rating', 'Any Rating'); ?></a>
                </div>

                <div class="sb-btn-row">
                    <button type="submit" class="apply-btn"><?php echo __('apply_filters', 'Apply Filters'); ?></button>
                    <a href="menu.php<?php echo $keyword ? '?keyword='.urlencode($keyword) : ''; ?>" class="reset-btn" title="Reset all filters">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        <?php echo __('btn_reset', 'Reset'); ?>
                    </a>
                </div>
            </form>
        </aside>

        <!-- ── MAIN CONTENT ── -->
        <div class="menu-main">
            <div class="menu-results-info">
                <div class="results-count">
                    <?php echo __('showing', 'Showing'); ?> <span><?php echo t_num(count($foods)); ?></span> <?php echo count($foods)!==1?__('items_lower', 'items'):__('item_lower', 'item'); ?>
                    <?php if ($keyword): ?> <?php echo __('for', 'for'); ?> <span>"<?php echo htmlspecialchars($keyword); ?>"</span><?php endif; ?>
                    <?php if ($activeCategory): ?>
                        <span class="search-tag"><?php echo htmlspecialchars($getCatEmoji($activeCategory).' '.__($activeCategory, $activeCategory)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($keyword||$activeCategory||$activeCity||$minPrice!==''||$maxPrice!==''||$minRating!==''||$sortBy!=='featured'): ?>
                    <a href="menu.php" class="view-all"><?php echo __('clear_filters_times', 'Clear filters &times;'); ?></a>
                <?php endif; ?>
            </div>

            <div class="menu-grid">
                <?php if (empty($foods)): ?>
                    <div class="menu-empty">
                        <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <h3><?php echo __('no_results_found', 'No results found'); ?></h3>
                        <p><?php echo $keyword ? sprintf(__('nothing_matched_for', 'Nothing matched "%s". Try a different keyword.'), htmlspecialchars($keyword)) : __('no_items_match_filters', 'No items match your filters.'); ?></p>
                        <a href="menu.php"><?php echo __('view_all_items', 'View All Items'); ?></a>
                    </div>
                <?php else: ?>
                    <?php foreach ($foods as $food): ?>
                        <a href="food_detail.php?id=<?php echo (int)$food['id']; ?>" class="food-card-link">
                        <article class="food-card">
                            <div class="food-img">
                                <?php if (!empty($food['image_path']) && file_exists($food['image_path'])): ?>
                                    <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($food['image_path']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="food-photo">
                                <?php elseif (!empty($food['image_path'])): ?>
                                    <img src="https://placehold.co/400x300/1a0a00/ff4f00?text=<?php echo urlencode($food['name']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="food-photo">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($food['emoji']); ?>
                                <?php endif; ?>
                                <?php if (!empty($food['badge'])): ?>
                                    <span class="food-badge<?php echo strtolower($food['badge'])==='new'?' new':''; ?>"><?php echo htmlspecialchars(__($food['badge'], $food['badge'])); ?></span>
                                <?php endif; ?>
                                <div class="food-fav"><?php echo $food['is_favorite'] ? '<i class="fa-solid fa-heart" style="color:#ef4444"></i>' : '<i class="fa-regular fa-heart"></i>'; ?></div>
                            </div>
                            <div class="food-info">
                                <div class="food-meta">
                                    <span class="food-category"><?php echo htmlspecialchars(__($food['category'], $food['category'])); ?></span>
                                    <span class="food-rating"><i class="fa-solid fa-star" style="color:#f59e0b"></i> <?php echo t_num($food['rating']); ?></span>
                                </div>
                                <div class="food-name"><?php echo htmlspecialchars(__($food['name'], $food['name'])); ?></div>
                                <div class="food-desc"><?php echo htmlspecialchars(__($food['description'], $food['description'])); ?></div>
                                <div class="food-footer">
                                    <div>
                                        <div class="food-time"><i class="fa-regular fa-clock"></i> <?php echo t_delivery_time($food['delivery_time']); ?></div>
                                        <div class="food-price"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format((float)$food['price'],2)); ?></div>
                                    </div>
                                    <form action="actions/add_to_cart.php" method="post" onclick="event.stopPropagation();">
                                        <input type="hidden" name="food_id"   value="<?php echo (int)$food['id']; ?>" />
                                        <input type="hidden" name="food_name" value="<?php echo htmlspecialchars(__($food['name'], $food['name'])); ?>" />
                                        <input type="hidden" name="price"     value="<?php echo (float)$food['price']; ?>" />
                                        <button class="add-btn" type="submit" aria-label="<?php echo sprintf(__('add_to_cart_aria', 'Add %s to cart'), htmlspecialchars(__($food['name'], $food['name']))); ?>" data-name="<?php echo htmlspecialchars(__($food['name'], $food['name'])); ?>">+</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div><!-- /menu-main -->

    </div><!-- /menu-body -->
</div><!-- /menu-page -->

<?php include 'templates/footer.php'; ?>
<?php include 'templates/floating_menu.php'; ?>
<script src="assets/js/script.js"></script>
<script src="assets/js/cart.js"></script>
<script src="assets/js/search_autocomplete.js"></script>
<script>
// Keep radio dots in sync visually
document.querySelectorAll('.sort-list input[type=radio]').forEach(function(r){
    r.addEventListener('change', function(){
        document.querySelectorAll('.sort-radio-dot').forEach(function(d){ d.innerHTML=''; });
        if(this.checked){ this.nextElementSibling.querySelector('.sort-radio-dot').innerHTML=''; }
    });
});
</script>
</body>
</html>
