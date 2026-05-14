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

// Get food ID
$foodId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($foodId <= 0) {
    header('Location: menu.php');
    exit;
}

// Fetch the food item
$stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
$stmt->execute([$foodId]);
$food = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$food) {
    header('Location: menu.php');
    exit;
}

// Fetch related foods (same category, exclude current)
$relStmt = $pdo->prepare("SELECT * FROM foods WHERE category = ? AND id != ? ORDER BY is_featured DESC, rating DESC LIMIT 4");
$relStmt->execute([$food['category'], $foodId]);
$relatedFoods = $relStmt->fetchAll(PDO::FETCH_ASSOC);

// If not enough related, fill from other categories
if (count($relatedFoods) < 4) {
    $need = 4 - count($relatedFoods);
    $existingIds = array_column($relatedFoods, 'id');
    $existingIds[] = $foodId;
    $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
    $fillStmt = $pdo->prepare("SELECT * FROM foods WHERE id NOT IN ($placeholders) ORDER BY is_featured DESC, rating DESC LIMIT $need");
    $fillStmt->execute($existingIds);
    $relatedFoods = array_merge($relatedFoods, $fillStmt->fetchAll(PDO::FETCH_ASSOC));
}

// Success message after add-to-cart
$cartSuccess = isset($_GET['added']) ? true : false;
$reviewSuccess = isset($_GET['review_added']) ? true : false;

// Fetch Reviews
$revStmt = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.food_id = ? 
    ORDER BY r.created_at DESC
");
$revStmt->execute([$foodId]);
$reviews = $revStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if current user has already reviewed
$hasReviewed = false;
if (isset($_SESSION['user_id'])) {
    foreach ($reviews as $r) {
        if ($r['user_id'] == $_SESSION['user_id']) {
            $hasReviewed = true;
            break;
        }
    }
}

$alreadyReviewed = isset($_GET['already_reviewed']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($food['name']); ?> — SwiftBite</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($food['description'], 0, 155)); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=8" />
    <style>
        /* ── Food Detail Page ── */
        .detail-page {
            padding-top: 90px;
            min-height: 100vh;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 20px 60px;
            font-size: 0.88rem;
            flex-wrap: wrap;
        }
        .breadcrumb a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .breadcrumb a:hover { color: var(--orange); }
        .breadcrumb .sep { color: var(--muted); opacity: 0.5; }
        .breadcrumb .current {
            color: var(--dark);
            font-weight: 700;
        }

        /* Cart success toast */
        .cart-toast {
            position: fixed;
            top: 90px;
            right: 24px;
            background: linear-gradient(135deg, #34c759 0%, #28a745 100%);
            color: #fff;
            padding: 16px 28px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 0.92rem;
            box-shadow: 0 10px 40px rgba(52, 199, 89, 0.35);
            z-index: 150;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: toastIn 0.4s ease, toastOut 0.4s ease 2.6s forwards;
        }
        .review-toast {
            top: 160px; /* lower than cart toast */
            background: linear-gradient(135deg, var(--orange) 0%, #ff2400 100%);
            box-shadow: 0 10px 40px rgba(255, 79, 0, 0.35);
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(40px); }
        }

        /* Main layout */
        .detail-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            padding: 0 60px 60px;
            align-items: start;
        }

        /* ── Image Hero ── */
        .detail-image-wrap {
            position: sticky;
            top: 110px;
            border-radius: 32px;
            overflow: hidden;
            background: linear-gradient(135deg, #fff6ea 0%, rgba(255, 79, 0, 0.10) 100%);
            aspect-ratio: 1 / 0.85;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        .detail-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .detail-image-wrap:hover img {
            transform: scale(1.04);
        }
        .detail-emoji {
            font-size: 10rem;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.08));
            animation: floatEmoji 4s ease-in-out infinite;
        }
        @keyframes floatEmoji {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(3deg); }
        }
        .detail-badge {
            position: absolute;
            top: 24px;
            left: 24px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.82rem;
            letter-spacing: 0.5px;
            z-index: 2;
        }
        .detail-badge.hot {
            background: linear-gradient(135deg, var(--orange), #ff2400);
            color: #fff;
            box-shadow: 0 6px 20px rgba(255, 79, 0, 0.4);
        }
        .detail-badge.new {
            background: linear-gradient(135deg, #34c759, #28a745);
            color: #fff;
            box-shadow: 0 6px 20px rgba(52, 199, 89, 0.4);
        }
        .detail-badge.popular {
            background: linear-gradient(135deg, var(--yellow), #e6a200);
            color: #fff;
            box-shadow: 0 6px 20px rgba(255, 184, 48, 0.4);
        }
        .detail-badge.sale {
            background: linear-gradient(135deg, #5856d6, #7c3aed);
            color: #fff;
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }
        .detail-fav {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            z-index: 2;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .detail-fav:hover { transform: scale(1.1); }

        /* ── Info Panel ── */
        .detail-info {
            padding: 8px 0;
        }
        .detail-category-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--cream2);
            padding: 8px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--orange);
            letter-spacing: 0.5px;
        }
        .detail-rating {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: 0.92rem;
            color: var(--dark);
        }
        .detail-rating .stars {
            color: var(--yellow);
            letter-spacing: 1px;
        }

        .detail-name {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 3.5vw, 2.6rem);
            font-weight: 800;
            color: var(--dark);
            line-height: 1.15;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .detail-delivery-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .detail-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #fff;
            border: 2px solid var(--cream2);
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        .detail-divider {
            height: 1px;
            background: var(--cream2);
            margin: 24px 0;
        }

        .detail-desc-label {
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--orange);
            margin-bottom: 10px;
        }
        .detail-desc {
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--muted);
            margin-bottom: 8px;
        }

        /* ── Price & Cart Section ── */
        .detail-purchase {
            background: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--shadow);
            margin-top: 32px;
        }
        .detail-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }
        .detail-price {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }
        .detail-price span {
            font-size: 1rem;
            color: var(--muted);
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
        }

        /* Quantity controls */
        .qty-control {
            display: flex;
            align-items: center;
            gap: 0;
            background: var(--cream);
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid var(--cream2);
        }
        .qty-btn {
            width: 48px;
            height: 48px;
            border: none;
            background: none;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--orange);
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn:hover {
            background: var(--cream2);
        }
        .qty-btn:active {
            transform: scale(0.92);
        }
        .qty-value {
            width: 52px;
            text-align: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--dark);
            border-left: 2px solid var(--cream2);
            border-right: 2px solid var(--cream2);
            padding: 0 4px;
            line-height: 48px;
        }

        .detail-add-btn {
            width: 100%;
            padding: 18px 32px;
            background: linear-gradient(135deg, var(--orange), #ff2400);
            color: #fff;
            border: none;
            border-radius: 18px;
            font-weight: 800;
            font-size: 1.05rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 8px 30px rgba(255, 79, 0, 0.35);
            letter-spacing: 0.3px;
        }
        .detail-add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 40px rgba(255, 79, 0, 0.45);
        }
        .detail-add-btn:active {
            transform: translateY(0);
        }
        .detail-total {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
        }

        /* ── Highlights Section ── */
        .detail-highlights {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        .highlight-card {
            flex: 1;
            min-width: 120px;
            background: var(--cream);
            border-radius: 20px;
            padding: 20px 16px;
            text-align: center;
            border: 2px solid var(--cream2);
            transition: all 0.2s;
        }
        .highlight-card:hover {
            border-color: var(--orange);
            transform: translateY(-2px);
        }
        .highlight-icon {
            font-size: 1.6rem;
            margin-bottom: 8px;
        }
        .highlight-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .highlight-value {
            font-weight: 800;
            color: var(--dark);
            font-size: 0.88rem;
        }

        /* ── Related & Reviews Sections ── */
        .related-section, .reviews-section {
            padding: 40px 60px 80px;
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .detail-main {
                grid-template-columns: 1fr;
                gap: 28px;
                padding: 0 30px 40px;
            }
            .detail-image-wrap {
                position: relative;
                top: 0;
                aspect-ratio: 16 / 10;
            }
            .breadcrumb, .related-section {
                padding-left: 30px;
                padding-right: 30px;
            }
        }
        @media (max-width: 768px) {
            .detail-main {
                padding: 0 20px 30px;
            }
            .breadcrumb, .related-section {
                padding-left: 20px;
                padding-right: 20px;
            }
            .detail-image-wrap {
                border-radius: 24px;
                aspect-ratio: 1 / 0.7;
            }
            .detail-purchase {
                padding: 20px;
            }
            .detail-name {
                font-size: 1.6rem;
            }
            .detail-highlights {
                gap: 10px;
            }
            .highlight-card {
                min-width: 90px;
                padding: 14px 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'templates/navbar.php'; ?>

    <div class="detail-page">
        <!-- Cart & Review success toasts -->
        <?php if ($cartSuccess): ?>
            <div class="cart-toast" id="cartToast">
                ✅ Added to cart successfully!
            </div>
        <?php endif; ?>
        <?php if ($reviewSuccess): ?>
            <div class="cart-toast review-toast" id="reviewToast">
                ⭐ Review submitted successfully!
            </div>
        <?php endif; ?>
        <?php if ($alreadyReviewed): ?>
            <div class="cart-toast review-toast" id="alreadyReviewedToast" style="background: linear-gradient(135deg, #ff3b30 0%, #d93025 100%); box-shadow: 0 10px 40px rgba(255, 59, 48, 0.35);">
                🚫 You have already reviewed this item!
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="sep">›</span>
            <a href="menu.php">Menu</a>
            <span class="sep">›</span>
            <a href="menu.php?category=<?php echo urlencode($food['category']); ?>"><?php echo htmlspecialchars($food['category']); ?></a>
            <span class="sep">›</span>
            <span class="current"><?php echo htmlspecialchars($food['name']); ?></span>
        </div>

        <!-- Detail Content -->
        <div class="detail-main">
            <!-- Left: Image -->
            <div class="detail-image-wrap">
                <?php if (!empty($food['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($food['image_path']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>">
                <?php else: ?>
                    <div class="detail-emoji"><?php echo htmlspecialchars($food['emoji']); ?></div>
                <?php endif; ?>

                <?php if (!empty($food['badge'])): ?>
                    <div class="detail-badge <?php echo strtolower($food['badge']); ?>">
                        <?php
                        $badgeIcons = ['Hot' => '🔥', 'New' => '🆕', 'Popular' => '⭐', 'Sale' => '💰'];
                        echo ($badgeIcons[$food['badge']] ?? '') . ' ' . htmlspecialchars($food['badge']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="detail-fav"><?php echo $food['is_favorite'] ? '❤️' : '🤍'; ?></div>
            </div>

            <!-- Right: Info -->
            <div class="detail-info">
                <!-- Category & Rating -->
                <div class="detail-category-row">
                    <div class="detail-cat-pill">
                        <?php echo htmlspecialchars($food['emoji'] ?: '🍴'); ?>
                        <?php echo htmlspecialchars($food['category']); ?>
                    </div>
                    <div class="detail-rating">
                        <span class="stars"><?php
                            $r = (float) $food['rating'];
                            $full = floor($r);
                            $half = ($r - $full) >= 0.5 ? 1 : 0;
                            echo str_repeat('★', $full);
                            if ($half) echo '★';
                            echo str_repeat('☆', 5 - $full - $half);
                        ?></span>
                        <?php echo htmlspecialchars($food['rating']); ?>
                    </div>
                </div>

                <!-- Name -->
                <h1 class="detail-name"><?php echo htmlspecialchars($food['name']); ?></h1>

                <!-- Delivery / Info pills -->
                <div class="detail-delivery-row">
                    <div class="detail-pill">🕐 <?php echo htmlspecialchars($food['delivery_time']); ?></div>
                    <?php if ($food['is_featured']): ?>
                        <div class="detail-pill">⚡ Featured</div>
                    <?php endif; ?>
                    <?php if ($food['is_favorite']): ?>
                        <div class="detail-pill">❤️ Favorite</div>
                    <?php endif; ?>
                </div>

                <div class="detail-divider"></div>

                <!-- Description -->
                <div class="detail-desc-label">Description</div>
                <p class="detail-desc"><?php echo nl2br(htmlspecialchars($food['description'])); ?></p>

                <!-- Purchase Card -->
                <div class="detail-purchase">
                    <div class="detail-price-row">
                        <div class="detail-price">
                            Rs. <?php echo number_format((float) $food['price'], 2); ?>
                            <span>per item</span>
                        </div>

                        <div class="qty-control">
                            <button type="button" class="qty-btn" id="qtyMinus" aria-label="Decrease quantity">−</button>
                            <div class="qty-value" id="qtyValue">1</div>
                            <button type="button" class="qty-btn" id="qtyPlus" aria-label="Increase quantity">+</button>
                        </div>
                    </div>

                    <form action="actions/add_to_cart.php" method="POST" id="addToCartForm">
                        <input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>">
                        <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>">
                        <input type="hidden" name="price" value="<?php echo (float) $food['price']; ?>">
                        <input type="hidden" name="quantity" id="qtyInput" value="1">
                        <button type="submit" class="detail-add-btn" id="addToCartBtn" data-name="<?php echo htmlspecialchars($food['name']); ?>">
                            🛒 Add to Cart — <span class="detail-total" id="totalPrice">Rs. <?php echo number_format((float) $food['price'], 2); ?></span>
                        </button>
                    </form>
                </div>

                <!-- Highlights -->
                <div class="detail-highlights">
                    <div class="highlight-card">
                        <div class="highlight-icon">🕐</div>
                        <div class="highlight-label">Delivery</div>
                        <div class="highlight-value"><?php echo htmlspecialchars($food['delivery_time']); ?></div>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon">⭐</div>
                        <div class="highlight-label">Rating</div>
                        <div class="highlight-value"><?php echo htmlspecialchars($food['rating']); ?> / 5.0</div>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon">🏷️</div>
                        <div class="highlight-label">Category</div>
                        <div class="highlight-value"><?php echo htmlspecialchars($food['category']); ?></div>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon"><?php echo $food['is_featured'] ? '⚡' : '📋'; ?></div>
                        <div class="highlight-label">Status</div>
                        <div class="highlight-value"><?php echo $food['is_featured'] ? 'Featured' : 'Regular'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <section class="reviews-section">
            <div class="section-header" style="margin-bottom: 32px;">
                <div>
                    <div class="section-tag">Customer Feedback</div>
                    <div class="section-title">Reviews & Ratings</div>
                </div>
            </div>

            <div style="display: grid; gap: 32px; grid-template-columns: 1fr; max-width: 800px; margin: 0 auto 40px;">
                <!-- Review Form -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($hasReviewed): ?>
                        <div class="review-form-card" style="background: var(--cream); border-radius: 24px; padding: 32px; border: 2px solid var(--cream2); text-align: center;">
                            <div style="font-size: 2.5rem; margin-bottom: 12px;">✅</div>
                            <h3 style="margin-bottom: 10px; font-family: 'Syne', sans-serif; font-size: 1.3rem;">You've reviewed this!</h3>
                            <p style="color: var(--muted); font-weight: 500;">Thank you for your feedback. You can only review each item once.</p>
                        </div>
                    <?php else: ?>
                        <div class="review-form-card" style="background: var(--cream); border-radius: 24px; padding: 32px; border: 2px solid var(--cream2);">
                            <h3 style="margin-bottom: 20px; font-family: 'Syne', sans-serif; font-size: 1.3rem;">Write a Review</h3>
                            <form action="actions/add_review.php" method="POST">
                                <input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>">
                                <div style="margin-bottom: 20px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Rating</label>
                                    <select name="rating" required style="width: 100%; padding: 14px; border-radius: 12px; border: 2px solid var(--cream2); outline: none; background: #fff; font-family: 'DM Sans', sans-serif;">
                                        <option value="5">5 - Excellent! ⭐⭐⭐⭐⭐</option>
                                        <option value="4">4 - Very Good ⭐⭐⭐⭐</option>
                                        <option value="3">3 - Average ⭐⭐⭐</option>
                                        <option value="2">2 - Poor ⭐⭐</option>
                                        <option value="1">1 - Terrible ⭐</option>
                                    </select>
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Comment</label>
                                    <textarea name="comment" rows="3" placeholder="Share your experience format..." style="width: 100%; padding: 14px; border-radius: 12px; border: 2px solid var(--cream2); outline: none; resize: vertical; font-family: 'DM Sans', sans-serif; background: #fff;"></textarea>
                                </div>
                                <button type="submit" style="padding: 14px 28px; background: var(--dark); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;">Submit Review</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="padding: 24px; background: var(--cream); border-radius: 20px; text-align: center; color: var(--muted); font-weight: 600; border: 2px solid var(--cream2);">Please <a href="auth/login.php" style="color: var(--orange); text-decoration: underline;">log in</a> to drop a review.</p>
                <?php endif; ?>

                <!-- Reviews List -->
                <div class="reviews-list" style="display: flex; flex-direction: column; gap: 20px;">
                    <?php if (empty($reviews)): ?>
                        <p style="color: var(--muted); text-align: center; padding: 20px;">No reviews yet. Be the first to review!</p>
                    <?php else: ?>
                        <?php foreach($reviews as $rev): ?>
                            <div style="background: #fff; padding: 24px; border-radius: 20px; box-shadow: var(--shadow); border: 2px solid var(--cream2);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                    <div style="font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($rev['user_name']); ?></div>
                                    <div style="color: var(--yellow); letter-spacing: 1px;">
                                        <?php echo str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']); ?>
                                    </div>
                                </div>
                                <div style="color: var(--muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 12px;">
                                    <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #a1a1aa; font-weight: 500;">
                                    <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Related Items -->
        <?php if (!empty($relatedFoods)): ?>
        <section class="related-section" style="padding-top: 0;">
            <div class="section-header">
                <div>
                    <div class="section-tag">You May Also Like</div>
                    <div class="section-title">Similar Dishes</div>
                </div>
                <a href="menu.php?category=<?php echo urlencode($food['category']); ?>" class="view-all">View All →</a>
            </div>

            <div class="foods-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                <?php foreach ($relatedFoods as $rel): ?>
                    <a href="food_detail.php?id=<?php echo (int) $rel['id']; ?>" class="food-card-link">
                        <article class="food-card">
                            <div class="food-img">
                                <?php if (!empty($rel['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($rel['image_path']); ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>" class="food-photo">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($rel['emoji']); ?>
                                <?php endif; ?>
                                <?php if (!empty($rel['badge'])): ?>
                                    <span class="food-badge<?php echo $rel['badge'] === 'New' ? ' new' : ''; ?>">
                                        <?php echo htmlspecialchars($rel['badge']); ?>
                                    </span>
                                <?php endif; ?>
                                <div class="food-fav"><?php echo $rel['is_favorite'] ? '❤️' : '🤍'; ?></div>
                            </div>
                            <div class="food-info">
                                <div class="food-meta">
                                    <span class="food-category"><?php echo htmlspecialchars($rel['category']); ?></span>
                                    <span class="food-rating">⭐ <?php echo htmlspecialchars($rel['rating']); ?></span>
                                </div>
                                <div class="food-name"><?php echo htmlspecialchars($rel['name']); ?></div>
                                <div class="food-desc"><?php echo htmlspecialchars($rel['description']); ?></div>
                                <div class="food-footer">
                                    <div>
                                        <div class="food-time">🕐 <?php echo htmlspecialchars($rel['delivery_time']); ?></div>
                                        <div class="food-price">Rs. <?php echo number_format((float) $rel['price'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <?php include 'templates/footer.php'; ?>

    <?php include 'templates/floating_menu.php'; ?>

    <script>
        // Quantity control
        const price = <?php echo (float) $food['price']; ?>;
        const qtyValue = document.getElementById('qtyValue');
        const qtyInput = document.getElementById('qtyInput');
        const totalPrice = document.getElementById('totalPrice');
        const qtyMinus = document.getElementById('qtyMinus');
        const qtyPlus = document.getElementById('qtyPlus');

        let qty = 1;

        function updateQty(newQty) {
            qty = Math.max(1, Math.min(20, newQty));
            qtyValue.textContent = qty;
            qtyInput.value = qty;
            totalPrice.textContent = 'Rs. ' + (price * qty).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        qtyMinus.addEventListener('click', () => updateQty(qty - 1));
        qtyPlus.addEventListener('click', () => updateQty(qty + 1));

        // Auto-hide toast
        const toast = document.getElementById('cartToast');
        if (toast) {
            setTimeout(() => toast.remove(), 3200);
        }
        const rToast = document.getElementById('reviewToast');
        if (rToast) {
            setTimeout(() => rToast.remove(), 3200);
        }
        const arToast = document.getElementById('alreadyReviewedToast');
        if (arToast) {
            setTimeout(() => arToast.remove(), 3200);
        }
    </script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/cart.js"></script>
</body>
</html>

