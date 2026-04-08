<?php
session_start();
include('../core/db.php');
include('../core/cart_helper.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all orders for this user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = getCartCount($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        .orders-page { padding: 100px 24px 60px; min-height: 100vh; background: var(--cream); }
        .orders-inner { max-width: 900px; margin: 0 auto; }
        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--dark); }
        
        .order-card { background: #fff; border-radius: 28px; padding: 28px; box-shadow: var(--shadow); margin-bottom: 24px; transition: transform 0.2s; }
        .order-card:hover { transform: translateY(-3px); box-shadow: 0 16px 50px rgba(255, 79, 0, 0.12); }
        
        .order-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px dashed var(--cream2); flex-wrap: wrap; gap: 16px; }
        .order-id { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-bottom: 4px; }
        .order-date { color: var(--muted); font-size: 0.9rem; }
        
        .order-status { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: rgba(255,184,48,0.15); color: #e6a200; }
        .status-confirmed, .status-preparing { background: rgba(0, 122, 255, 0.1); color: #007aff; }
        .status-out_for_delivery { background: rgba(255, 79, 0, 0.1); color: var(--orange); }
        .status-delivered { background: rgba(52, 199, 89, 0.15); color: #1a7a34; }
        .status-cancelled { background: rgba(255, 59, 48, 0.1); color: var(--red); }
        
        .order-body { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px; }
        .order-info { flex: 1; min-width: 250px; }
        .order-info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; color: var(--text); }
        .order-info-row.total { font-weight: 800; color: var(--dark); font-size: 1.1rem; margin-top: 12px; padding-top: 12px; border-top: 1px dotted var(--cream2); }
        
        .view-btn { display: inline-flex; padding: 12px 24px; background: var(--cream2); color: var(--dark); border-radius: 14px; font-weight: 700; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
        .view-btn:hover { background: var(--orange); color: #fff; }

        .empty-state { text-align: center; padding: 80px 20px; background: #fff; border-radius: 28px; box-shadow: var(--shadow); }
        .empty-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 1.5rem; color: var(--dark); margin-bottom: 8px; }
        .empty-state p { color: var(--muted); margin-bottom: 24px; max-width: 400px; margin-inline: auto; }
        .empty-btn { display: inline-flex; padding: 16px 32px; background: var(--orange); color: #fff; border-radius: 999px; font-weight: 700; text-decoration: none; box-shadow: 0 8px 30px rgba(255,79,0,0.3); transition: transform 0.2s; }
        .empty-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }

        @media (max-width: 500px) {
            .order-top { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="orders-page">
        <div class="orders-inner">
            
            <div class="page-header">
                <h1>📦 My Orders</h1>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🍽️</div>
                    <h3>No orders yet</h3>
                    <p>You haven't placed any orders. Start exploring our delicious menu to satisfy your cravings!</p>
                    <a href="menu.php" class="empty-btn">Explore Menu</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-top">
                            <div>
                                <div class="order-id">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date"><?php echo date('M d, Y • h:i A', strtotime($order['created_at'])); ?></div>
                            </div>
                            <?php
                                $statusMap = [
                                    'pending'          => ['icon' => '⏳', 'text' => 'Waiting for confirmation'],
                                    'confirmed'        => ['icon' => '👍', 'text' => 'Confirmed'],
                                    'preparing'        => ['icon' => '🧑‍🍳', 'text' => 'Preparing Food'],
                                    'ready'            => ['icon' => '✅', 'text' => 'Ready for Pickup'],
                                    'out_for_delivery' => ['icon' => '🛵', 'text' => 'Out for Delivery'],
                                    'delivered'        => ['icon' => '🎉', 'text' => 'Delivered'],
                                    'cancelled'        => ['icon' => '❌', 'text' => 'Cancelled']
                                ];
                                $s = $statusMap[$order['status']] ?? ['icon' => '📦', 'text' => ucfirst($order['status'])];
                            ?>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo $s['icon'] . ' ' . $s['text']; ?>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-info">
                                <div class="order-info-row">
                                    <span>Method:</span>
                                    <span style="text-transform:uppercase; font-weight:600;"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                </div>
                                <div class="order-info-row">
                                    <span>Items Subtotal:</span>
                                    <span>Rs. <?php echo number_format($order['subtotal'], 2); ?></span>
                                </div>
                                <div class="order-info-row total">
                                    <span>Total Paid:</span>
                                    <span>Rs. <?php echo number_format($order['total'], 2); ?></span>
                                </div>
                            </div>
                            <a href="user/order_details.php?id=<?php echo $order['id']; ?>" class="view-btn">View Details →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <?php include '../templates/floating_menu.php'; ?>

    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/cart.js"></script>
</body>
</html>
