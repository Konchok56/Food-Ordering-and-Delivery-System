<?php
// 🔒 Protect page + start session
include('includes/auth.php');

// 🔌 DB connection
include('includes/db.php');

$user_id = $_SESSION['user_id'];

// 🛒 Fetch cart items from database
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Cart — SwiftBite</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css" />
</head>

<body>

<div class="cart-page">
    <div class="cart-page-inner">
        
        <!-- Header -->
        <div class="section-header">
            <div>
                <div class="section-tag">Your Order</div>
                <div class="section-title">Shopping Cart</div>
            </div>
            <a href="index.php" class="view-all">← Continue Shopping</a>
        </div>

        <!-- Empty Cart -->
        <?php if (empty($cart)): ?>
            <div class="empty-cart">
                Your cart is empty right now.
            </div>

        <!-- Cart Items -->
        <?php else: ?>
            <div class="cart-list">

                <?php foreach ($cart as $item): ?>
                    <?php 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>

                    <div class="cart-item">
                        <div class="cart-item-left">
                            <div class="cart-item-emoji">🍔</div>

                            <div>
                                <div class="food-name">
                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                </div>

                                <div class="food-time">
                                    Quantity: <?php echo (int)$item['quantity']; ?>
                                </div>
                            </div>
                        </div>

                        <div class="food-price">
                            Rs. <?php echo number_format($subtotal, 2); ?>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Total -->
            <div class="cart-total-box">
                <div class="cart-total-row">
                    <span>Total</span>
                    <strong>Rs. <?php echo number_format($total, 2); ?></strong>
                </div>

                <button class="btn-primary cart-checkout-btn" type="button">
                    Proceed to Checkout
                </button>
            </div>

        <?php endif; ?>

    </div>
</div>

</body>
</html>