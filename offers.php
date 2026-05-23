<?php
require_once 'core/bootstrap.php';

// Fetch all active offers from DB
try {
    $stmt = $pdo->query("SELECT * FROM offers WHERE is_active = 1 ORDER BY created_at DESC");
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $offers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('nav_offers', 'Offers'); ?> — SwiftBite</title>
    <meta name="description" content="Explore special deals, discounts, and promotional offers on SwiftBite. Save big on your favorite meals!" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=8" />
    <style>
        .offers-page {
            padding-top: 120px;
            min-height: 100vh;
            background: var(--cream);
            padding-bottom: 80px;
        }

        .offers-hero {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 48px;
            padding: 0 24px;
        }

        .offers-hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 800;
            color: var(--dark);
            line-height: 1.1;
            margin-bottom: 16px;
        }

        .offers-hero h1 em {
            font-style: normal;
            color: var(--orange);
        }

        .offers-hero p {
            font-size: 1.1rem;
            color: var(--muted);
            line-height: 1.6;
        }

        .offers-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 32px;
            padding: 0 24px;
        }

        .offer-card {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            border: 2px solid var(--cream2);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .offer-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(255, 79, 0, 0.08);
            border-color: rgba(255, 79, 0, 0.2);
        }

        .offer-banner {
            width: 100%;
            height: 200px;
            position: relative;
            background: linear-gradient(135deg, var(--orange), var(--yellow));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        .offer-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .offer-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--orange);
            font-weight: 800;
            font-size: 0.78rem;
            padding: 6px 14px;
            border-radius: 999px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .offer-body {
            padding: 28px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .offer-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .offer-desc {
            font-size: 0.95rem;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 24px;
            flex: 1;
        }

        /* Ticket Coupon Widget */
        .coupon-widget {
            background: var(--cream);
            border: 2px dashed rgba(255, 79, 0, 0.3);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            position: relative;
        }

        .coupon-widget::before,
        .coupon-widget::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: #fff;
            border: 2px solid rgba(255, 79, 0, 0.3);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }

        .coupon-widget::before {
            left: -10px;
            border-left-color: transparent;
            border-bottom-color: transparent;
            transform: translateY(-50%) rotate(45deg);
        }

        .coupon-widget::after {
            right: -10px;
            border-right-color: transparent;
            border-top-color: transparent;
            transform: translateY(-50%) rotate(45deg);
        }

        .coupon-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .coupon-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 0.5px;
        }

        .coupon-code {
            font-family: 'Syne', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--orange);
            letter-spacing: 1px;
        }

        .btn-copy {
            background: var(--orange);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-copy:hover {
            background: var(--dark);
        }

        .btn-copy.copied {
            background: var(--green);
        }

        .btn-claim {
            width: 100%;
            text-align: center;
            background: var(--dark);
            color: #fff;
            text-decoration: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-claim:hover {
            background: var(--orange);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 79, 0, 0.2);
        }

        .empty-offers {
            text-align: center;
            padding: 80px 24px;
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            border-radius: 28px;
            box-shadow: var(--shadow);
            border: 2px solid var(--cream2);
        }

        .empty-offers .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        .empty-offers h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .empty-offers p {
            color: var(--muted);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 480px) {
            .offers-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'templates/navbar.php'; ?>

    <div class="offers-page">
        <!-- Hero Section -->
        <div class="offers-hero">
            <h1>Special <em>Offers</em> & Deals</h1>
            <p>Delicious food at pocket-friendly prices. Claim active discount promo codes and enjoy delicious meals delivered hot to your doorstep!</p>
        </div>

        <!-- Offers Listing -->
        <div class="offers-grid">
            <?php if (empty($offers)): ?>
                <div class="empty-offers" style="grid-column: 1 / -1;">
                    <div class="icon">🎁</div>
                    <h3>No active offers right now</h3>
                    <p>We are currently cooking up some amazing new discount deals for you. Check back shortly!</p>
                    <a href="menu.php" class="btn-claim" style="width:auto; display:inline-flex; padding: 12px 28px;">Go to Menu</a>
                </div>
            <?php else: ?>
                <?php foreach ($offers as $off): ?>
                    <div class="offer-card">
                        <div class="offer-banner">
                            <?php if (!empty($off['image_path']) && file_exists($off['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($off['image_path']); ?>" alt="Offer Banner">
                            <?php else: ?>
                                🎁
                            <?php endif; ?>
                            <div class="offer-badge">
                                <i class="fa-solid fa-bolt"></i> Deal of the Day
                            </div>
                        </div>
                        <div class="offer-body">
                            <div class="offer-title"><?php echo htmlspecialchars($off['title']); ?></div>
                            <p class="offer-desc"><?php echo htmlspecialchars($off['description']); ?></p>

                            <?php if (!empty($off['promo_code'])): ?>
                                <div class="coupon-widget">
                                    <div class="coupon-info">
                                        <span class="coupon-label">Coupon Code</span>
                                        <span class="coupon-code"><?php echo htmlspecialchars($off['promo_code']); ?></span>
                                    </div>
                                    <button class="btn-copy" onclick="copyPromoCode(this, '<?php echo htmlspecialchars($off['promo_code']); ?>')">
                                        <i class="fa-solid fa-copy"></i> Copy
                                    </button>
                                </div>
                            <?php endif; ?>

                            <a href="menu.php" class="btn-claim">
                                Claim Offer <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>

    <script>
        function copyPromoCode(btn, code) {
            navigator.clipboard.writeText(code).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                btn.classList.add('copied');
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('copied');
                }, 2500);
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>
