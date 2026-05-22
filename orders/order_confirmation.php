<?php
require_once '../core/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Verify Order belongs to User
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h2 style='text-align:center; margin-top:50px;'>" . __('order_not_found', 'Order not found or access denied.') . "</h2>");
}

$cartCount = getCartCount($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo __('order_confirmed', 'Order Confirmed'); ?> — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
    <style>
        .conf-page { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 120px 24px 60px; }
        .conf-card { background: #fff; border-radius: 36px; padding: 50px 40px; text-align: center; max-width: 500px; width: 100%; box-shadow: 0 20px 60px rgba(52, 199, 89, 0.15); animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .conf-icon { width: 90px; height: 90px; background: rgba(52, 199, 89, 0.12); color: #34c759; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 24px; animation: scaleUp 0.6s ease forwards; opacity: 0; transform: scale(0.5); }
        .conf-title { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--dark); margin-bottom: 12px; }
        .conf-msg { color: var(--muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 32px; }
        
        .conf-details { background: var(--cream); border-radius: 20px; padding: 24px; margin-bottom: 32px; text-align: left; }
        .conf-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; }
        .conf-row span:first-child { color: var(--muted); }
        .conf-row span:last-child { font-weight: 700; color: var(--dark); }
        
        .conf-actions { display: flex; flex-direction: column; gap: 14px; }
        .btn-track { padding: 16px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff; border: none; border-radius: 16px; font-weight: 800; text-decoration: none; transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3); }
        .btn-track:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }
        .btn-home { padding: 16px; background: var(--cream2); color: var(--dark); border-radius: 16px; font-weight: 700; text-decoration: none; transition: background 0.2s; }
        .btn-home:hover { background: #ffe4c4; }

        @keyframes popIn {
            0% { opacity: 0; transform: translateY(30px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes scaleUp {
            0% { opacity: 0; transform: scale(0.5); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="conf-page">
        <div class="conf-card">
            <div class="conf-icon" style="animation-delay: 0.2s;">✓</div>
            <h1 class="conf-title"><?php echo __('order_received', 'Order Received!'); ?></h1>
            <p class="conf-msg"><?php echo sprintf(__('thank_you_order_msg', 'Thank you, %s. Your delicious food is being prepared.'), htmlspecialchars(explode(' ', $order['customer_name'])[0])); ?></p>
            
            <div class="conf-details">
                <div class="conf-row">
                    <span><?php echo __('order_id', 'Order ID'); ?></span>
                    <span>#<?php echo t_num(str_pad($order['id'], 5, '0', STR_PAD_LEFT)); ?></span>
                </div>
                <div class="conf-row">
                    <span><?php echo __('total_amount', 'Total Amount'); ?></span>
                    <span><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($order['total'], 2)); ?></span>
                </div>
                <div class="conf-row">
                    <span><?php echo __('payment', 'Payment'); ?></span>
                    <span style="text-transform:uppercase;"><?php echo $order['payment_method'] === 'cod' ? __('cash_on_delivery_cod', 'Cash on Delivery (COD)') : htmlspecialchars(strtoupper($order['payment_method'])); ?></span>
                </div>
                <div class="conf-row" style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--cream2);">
                    <span><?php echo __('estimated_delivery', 'Estimated Delivery'); ?></span>
                    <span><?php echo t_delivery_time('30 - 45 min'); ?></span>
                </div>
            </div>

            <div class="conf-actions">
                <a href="../user/order_history.php" class="btn-track">🔍 <?php echo __('trace_order', 'Trace Order'); ?></a>
                <a href="../index.php" class="btn-home"><?php echo __('back_to_home_arrow', '← Back to Home'); ?></a>
            </div>
        </div>
    </div>

    <?php include '../templates/floating_menu.php'; ?>

    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/cart.js"></script>
</body>
</html>
