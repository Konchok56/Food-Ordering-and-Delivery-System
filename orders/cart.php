<?php
require_once '../core/bootstrap.php';

$user_id = $_SESSION['user_id'];

// Handle "Clear All" action
if (isset($_POST['clear_cart']) && $_POST['clear_cart'] === '1') {
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
    header("Location: cart.php");
    exit;
}

// 🛒 Fetch cart items — JOIN with foods table for images
$stmt = $pdo->prepare("
    SELECT c.*, 
           f.image_path AS food_image, 
           f.emoji AS food_emoji,
           f.id AS fid,
           f.category,
           f.rating
    FROM cart c 
    LEFT JOIN foods f ON c.food_id = f.id 
    WHERE c.user_id = ? 
    ORDER BY c.id DESC
");
$stmt->execute([$user_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$deliveryFee = $subtotal > 0 ? 50 : 0; // Rs. 50 delivery fee
$total = $subtotal + $deliveryFee;
$cartCount = getCartCount($pdo, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Cart — SwiftBite</title>
    <meta name="description" content="Review your SwiftBite cart. Manage quantities, view your order summary, and proceed to checkout." />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
</head>

<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="cart-page">
        <div class="cart-page-inner">

            <!-- Header -->
            <div class="cart-header">
                <div class="cart-header-left">
                    <div class="section-tag">🛒 Your Order</div>
                    <div class="section-title">Shopping Cart</div>
                </div>
                <div class="cart-header-actions">
                    <a href="../menu.php" class="cart-continue-btn">← Continue Shopping</a>
                    <?php if (!empty($cart)): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Clear all items from cart?');">
                            <input type="hidden" name="clear_cart" value="1">
                            <button type="submit" class="cart-clear-btn">🗑️ Clear Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($cart)): ?>
                <!-- Empty Cart -->
                <div class="cart-empty-state">
                    <div class="cart-empty-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet.<br>Start exploring our delicious menu!</p>
                    <a href="../menu.php" class="cart-empty-browse">🍽️ Browse Menu</a>
                </div>

            <?php else: ?>
                <!-- Cart Layout: Items + Summary -->
                <div class="cart-layout">
                    <!-- Items Panel -->
                    <div class="cart-items-panel" id="cartItemsPanel">

                        <?php foreach ($cart as $item): ?>
                            <?php 
                                $itemSubtotal = $item['price'] * $item['quantity'];
                                // Determine image: prefer foods table, fallback to cart stored, fallback to emoji
                                $imgPath = !empty($item['food_image']) ? $item['food_image'] : (!empty($item['image_path']) ? $item['image_path'] : '');
                                $emojiIcon = !empty($item['food_emoji']) ? $item['food_emoji'] : (!empty($item['emoji']) ? $item['emoji'] : '🍔');
                                $foodLink = $item['fid'] ? "../food_detail.php?id=" . (int)$item['fid'] : '#';
                            ?>

                            <div class="cart-item-card" data-cart-id="<?php echo (int)$item['id']; ?>" data-price="<?php echo (float)$item['price']; ?>">
                                <!-- Image -->
                                <div class="cart-item-image">
                                    <a href="<?php echo $foodLink; ?>">
                                        <?php if (!empty($imgPath)): ?>
                                            <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($item['food_name']); ?>">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($emojiIcon); ?>
                                        <?php endif; ?>
                                    </a>
                                </div>

                                <!-- Body -->
                                <div class="cart-item-body">
                                    <div class="cart-item-name">
                                        <a href="<?php echo $foodLink; ?>"><?php echo htmlspecialchars($item['food_name']); ?></a>
                                    </div>
                                    <div class="cart-item-unit-price">
                                        Rs. <?php echo number_format((float)$item['price'], 2); ?> per item
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="cart-qty-controls">
                                        <button type="button" class="cart-qty-btn" data-action="decrease" data-cart-id="<?php echo (int)$item['id']; ?>" aria-label="Decrease quantity">−</button>
                                        <div class="cart-qty-value" id="qty-<?php echo (int)$item['id']; ?>"><?php echo (int)$item['quantity']; ?></div>
                                        <button type="button" class="cart-qty-btn" data-action="increase" data-cart-id="<?php echo (int)$item['id']; ?>" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>

                                <!-- Right: Subtotal + Remove -->
                                <div class="cart-item-right">
                                    <div class="cart-item-subtotal" id="subtotal-<?php echo (int)$item['id']; ?>">
                                        Rs. <?php echo number_format($itemSubtotal, 2); ?>
                                    </div>
                                    <button type="button" class="cart-remove-btn" data-action="remove" data-cart-id="<?php echo (int)$item['id']; ?>" title="Remove item" aria-label="Remove <?php echo htmlspecialchars($item['food_name']); ?>">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                    <!-- Summary Panel -->
                    <div class="cart-summary-panel">
                        <div class="cart-summary-title">Order Summary</div>
                        
                        <div class="cart-summary-row">
                            <span>Items (<?php echo $cartCount; ?>)</span>
                            <span id="summarySubtotal">Rs. <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Delivery Fee</span>
                            <span id="summaryDelivery">Rs. <?php echo number_format($deliveryFee, 2); ?></span>
                        </div>
                        <div class="cart-summary-row total">
                            <span>Total</span>
                            <strong id="summaryTotal">Rs. <?php echo number_format($total, 2); ?></strong>
                        </div>

                        <button class="cart-checkout-btn" type="button" id="checkoutBtn">
                            🛒 Proceed to Checkout
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include '../templates/footer.php'; ?>

    <?php include '../templates/floating_menu.php'; ?>

    <script>
    // ── Cart Page AJAX Controls ──────────────────────────────
    const DELIVERY_FEE = 50;

    function updateSummary() {
        // Recalculate from all visible items
        const cards = document.querySelectorAll('.cart-item-card:not(.removing)');
        let subtotal = 0;
        let itemCount = 0;
        cards.forEach(card => {
            const price = parseFloat(card.dataset.price) || 0;
            const qtyEl = card.querySelector('.cart-qty-value');
            const qty = parseInt(qtyEl?.textContent) || 0;
            subtotal += price * qty;
            itemCount += qty;
        });

        const deliveryFee = subtotal > 0 ? DELIVERY_FEE : 0;
        const total = subtotal + deliveryFee;

        const fmt = (n) => n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        const subEl = document.getElementById('summarySubtotal');
        const delEl = document.getElementById('summaryDelivery');
        const totEl = document.getElementById('summaryTotal');
        if (subEl) subEl.textContent = 'Rs. ' + fmt(subtotal);
        if (delEl) delEl.textContent = 'Rs. ' + fmt(deliveryFee);
        if (totEl) totEl.textContent = 'Rs. ' + fmt(total);

        // Update cart badges
        document.querySelectorAll('#cartCount, .cart-count, [data-cart-count]').forEach(el => {
            el.textContent = itemCount;
        });
    }

    function cartAction(cartId, action) {
        const data = new FormData();
        data.append('cart_id', cartId);
        data.append('action', action);

        fetch('../actions/update_cart.php', {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(json => {
            if (!json.success) return;

            const card = document.querySelector(`.cart-item-card[data-cart-id="${cartId}"]`);
            if (!card) return;

            if (action === 'remove' || json.new_quantity === 0) {
                // Animate removal
                card.classList.add('removing');
                setTimeout(() => {
                    card.remove();
                    updateSummary();
                    // Check if cart is now empty
                    if (json.is_empty) {
                        location.reload();
                    }
                }, 380);
            } else {
                // Update quantity display
                const qtyEl = document.getElementById('qty-' + cartId);
                const subEl = document.getElementById('subtotal-' + cartId);
                if (qtyEl) qtyEl.textContent = json.new_quantity;
                if (subEl) subEl.textContent = 'Rs. ' + json.item_subtotal;
                updateSummary();
            }

            // Update cart count badges
            document.querySelectorAll('#cartCount, .cart-count').forEach(el => {
                el.textContent = json.cart_count;
            });
        })
        .catch(err => {
            console.error('Cart update failed:', err);
        });
    }

    // Delegated event listeners
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const cartId = btn.dataset.cartId;
        if (!cartId) return;

        cartAction(cartId, action);
    });

    // Checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            window.location.href = 'checkout.php';
        });
    }
    </script>
    <script src="../assets/js/cart.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
