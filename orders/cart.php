<?php
require_once '../core/bootstrap.php';

$user_id = $_SESSION['user_id'] ?? null;

// Handle "Clear All" action
if (isset($_POST['clear_cart']) && $_POST['clear_cart'] === '1') {
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
    header("Location: cart.php");
    exit;
}

// <i class="fa-solid fa-cart-shopping"></i> Fetch cart items — JOIN with foods table for images
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
    <title><?php echo __('your_cart', 'Your Cart'); ?> — SwiftBite</title>
    <meta name="description" content="<?php echo __('cart_meta_desc', 'Review your SwiftBite cart. Manage quantities, view your order summary, and proceed to checkout.'); ?>" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                    <div class="section-tag"><i class="fa-solid fa-cart-shopping"></i> <?php echo __('your_order', 'Your Order'); ?></div>
                    <div class="section-title"><?php echo __('shopping_cart', 'Shopping Cart'); ?></div>
                </div>
                <div class="cart-header-actions">
                    <a href="../menu.php" class="cart-continue-btn"><?php echo __('continue_shopping_arrow', '← Continue Shopping'); ?></a>
                    <?php if (!empty($cart)): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo __('confirm_clear_cart', 'Clear all items from cart?'); ?>');">
                            <input type="hidden" name="clear_cart" value="1">
                            <button type="submit" class="cart-clear-btn"><i class="fa-solid fa-trash"></i> <?php echo __('clear_cart', 'Clear Cart'); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($cart)): ?>
                <!-- Empty Cart -->
                <div class="cart-empty-state">
                    <div class="cart-empty-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <h3><?php echo __('cart_is_empty', 'Your cart is empty'); ?></h3>
                    <p><?php echo __('cart_is_empty_desc', "Looks like you haven't added anything yet.<br>Start exploring our delicious menu!"); ?></p>
                    <a href="../menu.php" class="cart-empty-browse"><i class="fa-solid fa-utensils"></i> <?php echo __('browse_menu', 'Browse Menu'); ?></a>
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
                                $emojiIcon = !empty($item['food_emoji']) ? $item['food_emoji'] : (!empty($item['emoji']) ? $item['emoji'] : '<i class="fa-solid fa-burger"></i>');
                                $foodLink = $item['fid'] ? "../food_detail.php?id=" . (int)$item['fid'] : '#';
                            ?>

                            <div class="cart-item-card" data-cart-id="<?php echo (int)$item['id']; ?>" data-price="<?php echo (float)$item['price']; ?>">
                                <!-- Image -->
                                <div class="cart-item-image">
                                    <a href="<?php echo $foodLink; ?>">
                                        <?php if (!empty($imgPath) && file_exists(__DIR__ . '/../' . $imgPath)): ?>
                                            <img src="../<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars(__($item['food_name'], $item['food_name'])); ?>">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($emojiIcon); ?>
                                        <?php endif; ?>
                                    </a>
                                </div>

                                <!-- Body -->
                                <div class="cart-item-body">
                                    <div class="cart-item-name">
                                        <a href="<?php echo $foodLink; ?>"><?php echo htmlspecialchars(__($item['food_name'], $item['food_name'])); ?></a>
                                    </div>
                                    <div class="cart-item-unit-price">
                                        <?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format((float)$item['price'], 2)); ?> <?php echo __('per_item', 'per item'); ?>
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="cart-qty-controls">
                                        <button type="button" class="cart-qty-btn" data-action="decrease" data-cart-id="<?php echo (int)$item['id']; ?>" aria-label="Decrease quantity">−</button>
                                        <div class="cart-qty-value" id="qty-<?php echo (int)$item['id']; ?>"><?php echo t_num((int)$item['quantity']); ?></div>
                                        <button type="button" class="cart-qty-btn" data-action="increase" data-cart-id="<?php echo (int)$item['id']; ?>" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>

                                <!-- Right: Subtotal + Remove -->
                                <div class="cart-item-right">
                                    <div class="cart-item-subtotal" id="subtotal-<?php echo (int)$item['id']; ?>">
                                        <?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($itemSubtotal, 2)); ?>
                                    </div>
                                    <button type="button" class="cart-remove-btn" data-action="remove" data-cart-id="<?php echo (int)$item['id']; ?>" title="Remove item" aria-label="<?php echo sprintf(__('remove_item_aria', 'Remove %s'), htmlspecialchars(__($item['food_name'], $item['food_name']))); ?>">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                    <!-- Summary Panel -->
                    <div class="cart-summary-panel">
                        <div class="cart-summary-title"><?php echo __('order_summary', 'Order Summary'); ?></div>
                        
                        <div class="cart-summary-row">
                            <span><?php echo __('items_plural', 'Items'); ?> (<?php echo t_num($cartCount); ?>)</span>
                            <span id="summarySubtotal"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($subtotal, 2)); ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span><?php echo __('delivery_fee', 'Delivery Fee'); ?></span>
                            <span id="summaryDelivery"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($deliveryFee, 2)); ?></span>
                        </div>
                        <div class="cart-summary-row total">
                            <span><?php echo __('total', 'Total'); ?></span>
                            <strong id="summaryTotal"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($total, 2)); ?></strong>
                        </div>

                        <button class="cart-checkout-btn" type="button" id="checkoutBtn">
                            <i class="fa-solid fa-cart-shopping"></i> <?php echo __('proceed_to_checkout', 'Proceed to Checkout'); ?>
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
    const activeLang = '<?php echo $activeLang; ?>';

    function t_num_js(numStr) {
        if (activeLang !== 'ne') return numStr;
        const nepDigits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return numStr.toString().replace(/\d/g, d => nepDigits[d]);
    }

    function updateSummary() {
        // Recalculate from all visible items
        const cards = document.querySelectorAll('.cart-item-card:not(.removing)');
        let subtotal = 0;
        let itemCount = 0;
        cards.forEach(card => {
            const price = parseFloat(card.dataset.price) || 0;
            const qtyEl = card.querySelector('.cart-qty-value');
            
            // To safely extract number regardless of locale display
            let qtyText = qtyEl?.textContent || '0';
            if (activeLang === 'ne') {
                const nepDigits = {'०':0, '१':1, '२':2, '३':3, '४':4, '५':5, '६':6, '७':7, '८':8, '९':9};
                qtyText = qtyText.replace(/[०-९]/g, d => nepDigits[d]);
            }
            const qty = parseInt(qtyText) || 0;
            subtotal += price * qty;
            itemCount += qty;
        });

        const deliveryFee = subtotal > 0 ? DELIVERY_FEE : 0;
        const total = subtotal + deliveryFee;

        const fmt = (n) => n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        const subEl = document.getElementById('summarySubtotal');
        const delEl = document.getElementById('summaryDelivery');
        const totEl = document.getElementById('summaryTotal');
        const currencyRs = '<?php echo __('currency_rs', 'Rs.'); ?>';
        if (subEl) subEl.textContent = currencyRs + ' ' + t_num_js(fmt(subtotal));
        if (delEl) delEl.textContent = currencyRs + ' ' + t_num_js(fmt(deliveryFee));
        if (totEl) totEl.textContent = currencyRs + ' ' + t_num_js(fmt(total));

        // Update cart badges
        document.querySelectorAll('#cartCount, .cart-count, [data-cart-count]').forEach(el => {
            el.textContent = t_num_js(itemCount);
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
                if (qtyEl) qtyEl.textContent = t_num_js(json.new_quantity);
                if (subEl) subEl.textContent = '<?php echo __('currency_rs', 'Rs.'); ?> ' + t_num_js(json.item_subtotal);
                updateSummary();
            }

            // Update cart count badges
            document.querySelectorAll('#cartCount, .cart-count').forEach(el => {
                el.textContent = t_num_js(json.cart_count);
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
