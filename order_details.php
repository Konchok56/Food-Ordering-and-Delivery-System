<?php
session_start();
include('includes/db.php');
include('includes/cart_helper.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get Order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h2 style='text-align:center;margin-top:50px;'>Order not found!</h2>");
}

// Get Order Items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = getCartCount($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Details #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?> — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        .page { padding: 100px 24px 60px; min-height: 100vh; background: var(--cream); }
        .inner { max-width: 800px; margin: 0 auto; }
        .header { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
        .back-btn { padding: 10px 16px; background: #fff; border-radius: 12px; font-weight: 700; color: var(--dark); text-decoration: none; box-shadow: var(--shadow); transition: transform 0.2s; }
        .back-btn:hover { transform: translateY(-2px); color: var(--orange); }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--dark); margin: 0; }
        
        .card { background: #fff; border-radius: 28px; padding: 32px; box-shadow: var(--shadow); margin-bottom: 24px; }
        .card h2 { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed var(--cream2); padding-bottom: 16px; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: rgba(255,184,48,0.15); color: #e6a200; }
        .status-confirmed, .status-preparing { background: rgba(0, 122, 255, 0.1); color: #007aff; }
        .status-out_for_delivery { background: rgba(255, 79, 0, 0.1); color: var(--orange); }
        .status-delivered { background: rgba(52, 199, 89, 0.15); color: #1a7a34; }
        .status-cancelled { background: rgba(255, 59, 48, 0.1); color: var(--red); }

        .items-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
        .item-row { display: flex; align-items: center; gap: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--cream2); }
        .item-img { width: 60px; height: 60px; background: var(--cream2); border-radius: 14px; display: flex; align-items: center; justify-content: center; overflow: hidden; font-size: 1.8rem; flex-shrink: 0; }
        .item-img img { width: 100%; height: 100%; object-fit: cover; }
        .item-info { flex: 1; }
        .item-name { font-weight: 700; font-size: 1.05rem; color: var(--dark); }
        .item-meta { font-size: 0.9rem; color: var(--muted); margin-top: 4px; }
        .item-price { font-weight: 800; color: var(--dark); white-space: nowrap; }

        .summary-totals { margin-top: 24px; padding: 24px; background: var(--cream); border-radius: 20px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: 500; font-size: 1.05rem; color: var(--text); }
        .summary-row.total { font-weight: 800; font-size: 1.3rem; color: var(--orange); margin-top: 16px; padding-top: 16px; border-top: 2px solid #e2d5c5; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .info-block h3 { font-size: 0.95rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .info-block p { color: var(--dark); font-weight: 600; line-height: 1.6; font-size: 1.05rem; }
        
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'sections/navbar.php'; ?>

    <div class="page">
        <div class="inner">
            <div class="header">
                <a href="order_history.php" class="back-btn">← Back</a>
                <h1>Order Details</h1>
            </div>

            <div class="card">
                <h2>
                    <span>Invoice #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </h2>

                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <div class="item-img">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item['emoji']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                                <div class="item-meta">Rs. <?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="item-price">Rs. <?php echo number_format($item['subtotal'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Items Subtotal</span>
                        <span>Rs. <?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span>Rs. <?php echo number_format($order['delivery_fee'], 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span>Rs. <?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 style="border:none; margin-bottom:16px;">Delivery Details</h2>
                <div class="info-grid">
                    <div class="info-block">
                        <h3>Customer</h3>
                        <p><?php echo htmlspecialchars($order['customer_name']); ?><br>
                           <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                           <span style="font-weight:400; color:var(--muted); font-size:0.95rem;"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </p>
                    </div>
                    <div class="info-block">
                        <h3>Delivery Address</h3>
                        <p><?php echo htmlspecialchars($order['delivery_address']); ?><br>
                           <?php echo htmlspecialchars($order['delivery_city']); ?></p>
                    </div>
                    <div class="info-block">
                        <h3>Payment Method</h3>
                        <p style="text-transform:uppercase;"><?php echo htmlspecialchars($order['payment_method']); ?></p>
                    </div>
                    <div class="info-block">
                        <h3>Date</h3>
                        <p><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($order['notes'])): ?>
                    <div class="info-block" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--cream2);">
                        <h3>Delivery Notes</h3>
                        <p style="font-weight:400; font-style:italic;">"<?php echo htmlspecialchars($order['notes']); ?>"</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php include 'sections/floating_menu.php'; ?>

    <?php include 'sections/footer.php'; ?>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/cart.js"></script>
</body>
</html>
