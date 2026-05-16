<?php
require_once '../core/bootstrap.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Cart Items
$stmt = $pdo->prepare("
    SELECT c.*, 
           f.image_path AS food_image, 
           f.emoji AS food_emoji,
           f.id AS fid
    FROM cart c 
    LEFT JOIN foods f ON c.food_id = f.id 
    WHERE c.user_id = ? 
    ORDER BY c.id DESC
");
$stmt->execute([$user_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// Get User details for pre-filling form
$stmt = $pdo->prepare("SELECT name, email, phone, address, city, status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$deliveryFee = $subtotal > 0 ? 50 : 0;
$total = $subtotal + $deliveryFee;
$cartCount = getCartCount($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout — SwiftBite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
    <style>
        .checkout-page { padding: 100px 24px 60px; min-height: 100vh; }
        .checkout-inner { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1fr 380px; gap: 32px; align-items: start; }
        .checkout-block { background: #fff; border-radius: 28px; padding: 32px; box-shadow: var(--shadow); margin-bottom: 24px; }
        .checkout-block h2 { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--dark); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 0.9rem; color: var(--dark); }
        .form-group input, .form-group textarea, .form-group select {
            padding: 14px 18px; border: 2px solid var(--cream2); border-radius: 16px;
            font-size: 0.95rem; color: var(--text); background: var(--cream); outline: none; transition: border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--orange); background: #fff; }
        
        .payment-methods { display: flex; flex-direction: column; gap: 12px; }
        .payment-option {
            display: flex; align-items: center; gap: 12px; padding: 16px; border: 2px solid var(--cream2);
            border-radius: 16px; cursor: pointer; transition: all 0.2s;
        }
        .payment-option:hover { border-color: var(--orange); background: rgba(255,79,0,0.02); }
        .payment-option input[type="radio"] { width: 20px; height: 20px; accent-color: var(--orange); }
        .payment-option span { font-weight: 600; font-size: 1rem; color: var(--dark); }
        
        /* Summary Sidebar */
        .summary-block { position: sticky; top: 100px; }
        .summary-item { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .summary-img { width: 50px; height: 50px; background: var(--cream2); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; font-size: 1.5rem; flex-shrink: 0; }
        .summary-img img { width: 100%; height: 100%; object-fit: cover; }
        .summary-info { flex: 1; min-width: 0; }
        .summary-name { font-weight: 700; font-size: 0.95rem; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .summary-meta { font-size: 0.85rem; color: var(--muted); margin-top: 2px; }
        .summary-price { font-weight: 700; color: var(--dark); white-space: nowrap; }
        
        .summary-totals { margin-top: 24px; padding-top: 20px; border-top: 2px dashed var(--cream2); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: 500; color: var(--text); }
        .summary-row.total { font-weight: 800; font-size: 1.25rem; color: var(--orange); margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--cream2); }
        
        .place-order-btn {
            width: 100%; padding: 18px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff;
            border: none; border-radius: 18px; font-weight: 800; font-size: 1.1rem; cursor: pointer;
            transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3); margin-top: 24px;
        }
        .place-order-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }

        @media (max-width: 900px) {
            .checkout-inner { grid-template-columns: 1fr; }
            .summary-block { position: static; }
        }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="checkout-page">
        <div class="checkout-inner">
            
            <div style="grid-column: 1 / -1; margin-bottom: 20px;">
                <?php echo renderFlash(); ?>
            </div>

            <form action="../actions/place_order.php" method="POST" id="checkoutForm">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="promo_code" id="hiddenPromoCode" value="">
                
                <!-- 1. Contact & Delivery -->
                <div class="checkout-block">
                    <h2><i class="fa-solid fa-location-dot"></i> Delivery Details</h2>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="e.g. 9812345678">
                        </div>
                        <div class="form-group full">
                            <label for="address">Delivery Address *</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required placeholder="Street, area, building...">
                        </div>
                        <div class="form-group full">
                            <label for="city">City *</label>
                            <select id="city" name="city" required>
                                <option value="Kathmandu" <?php echo ($user['city'] ?? 'Kathmandu') === 'Kathmandu' ? 'selected' : ''; ?>>Kathmandu</option>
                                <option value="Lalitpur" <?php echo ($user['city'] ?? '') === 'Lalitpur' ? 'selected' : ''; ?>>Lalitpur</option>
                                <option value="Bhaktapur" <?php echo ($user['city'] ?? '') === 'Bhaktapur' ? 'selected' : ''; ?>>Bhaktapur</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label for="notes">Delivery Notes (Optional)</label>
                            <textarea id="notes" name="notes" placeholder="e.g. Leave at the door, call when arrived..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Method -->
                <div class="checkout-block">
                    <h2>💳 Payment Method</h2>
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <span>💵 Cash on Delivery (COD)</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="esewa">
                            <span>🟢 eSewa (Coming Soon)</span>
                        </label>
                    </div>
                </div>

            </form>

            <!-- Sidebar Summary -->
            <div class="checkout-block summary-block">
                <h2><i class="fa-solid fa-cart-shopping"></i> Order Summary</h2>
                
                <div class="summary-items" style="margin-bottom: 20px;">
                    <?php foreach ($cart as $item): ?>
                        <?php 
                            $imgPath = !empty($item['food_image']) ? $item['food_image'] : (!empty($item['image_path']) ? $item['image_path'] : '');
                            $emojiIcon = !empty($item['food_emoji']) ? $item['food_emoji'] : (!empty($item['emoji']) ? $item['emoji'] : '<i class="fa-solid fa-burger"></i>');
                        ?>
                        <div class="summary-item">
                            <div class="summary-img">
                                <?php if (!empty($imgPath)): ?>
                                    <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($imgPath); ?>" alt="">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($emojiIcon); ?>
                                <?php endif; ?>
                            </div>
                            <div class="summary-info">
                                <div class="summary-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                                <div class="summary-meta">Qty: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="summary-price">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="promo-code-block" style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <input type="text" id="promoCode" placeholder="Promo Code" style="flex: 1; padding: 12px; border-radius: 12px; border: 2px solid var(--cream2); font-family: 'DM Sans', sans-serif; outline: none; transition: 0.2s;">
                    <button type="button" id="applyPromoBtn" style="padding: 12px 20px; background: var(--dark); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;">Apply</button>
                </div>
                <div id="promoMessage" style="font-size: 0.85rem; margin-top: -12px; margin-bottom: 12px; font-weight: 600;"></div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span>Rs. <?php echo number_format($deliveryFee, 2); ?></span>
                    </div>
                    <div class="summary-row" id="discountRow" style="display: none; color: #34c759;">
                        <span>Discount</span>
                        <span>- Rs. <span id="discountValue">0.00</span></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="finalTotalStr">Rs. <?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <?php if (($user['status'] ?? 'active') === 'inactive'): ?>
                    <div style="margin-top: 24px; padding: 14px; background: rgba(255,59,48,0.1); border: 1px solid rgba(255,59,48,0.2); border-radius: 12px; color: #cc2d25; font-size: 0.9rem; font-weight: 600; text-align: center;">
                        <i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> You need to be active to order. Please update your status in your profile.
                    </div>
                    <button type="button" class="place-order-btn" style="background:#ccc; box-shadow:none; cursor:not-allowed;" disabled><i class="fa-solid fa-rocket"></i> Place Order</button>
                <?php else: ?>
                    <button type="submit" form="checkoutForm" class="place-order-btn"><i class="fa-solid fa-rocket"></i> Place Order</button>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        const originalTotal = <?php echo $total; ?>;
        const subtotal = <?php echo $subtotal; ?>;
        const applyPromoBtn = document.getElementById('applyPromoBtn');
        const promoCodeInput = document.getElementById('promoCode');
        const promoMessage = document.getElementById('promoMessage');
        const discountRow = document.getElementById('discountRow');
        const discountValue = document.getElementById('discountValue');
        const finalTotalStr = document.getElementById('finalTotalStr');
        const hiddenPromoCode = document.getElementById('hiddenPromoCode');

        applyPromoBtn.addEventListener('click', function() {
            const code = promoCodeInput.value.trim().toUpperCase();
            if (!code) return;

            applyPromoBtn.disabled = true;
            applyPromoBtn.textContent = '...';

            const formData = new FormData();
            formData.append('promo_code', code);
            formData.append('subtotal', subtotal);

            fetch('../actions/apply_promo.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                applyPromoBtn.disabled = false;
                applyPromoBtn.textContent = 'Apply';

                if (data.success) {
                    promoMessage.textContent = '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i> ' + data.message;
                    promoMessage.style.color = '#34c759';
                    
                    hiddenPromoCode.value = data.code;
                    
                    const discount = parseFloat(data.discount_amount);
                    const newTotal = originalTotal - discount;

                    discountRow.style.display = 'flex';
                    discountValue.textContent = discount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    
                    finalTotalStr.textContent = 'Rs. ' + Math.max(0, newTotal).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                } else {
                    promoMessage.textContent = '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> ' + data.message;
                    promoMessage.style.color = '#ff2400';
                    
                    hiddenPromoCode.value = '';
                    discountRow.style.display = 'none';
                    finalTotalStr.textContent = 'Rs. ' + originalTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            })
            .catch(err => {
                applyPromoBtn.disabled = false;
                applyPromoBtn.textContent = 'Apply';
                console.error(err);
            });
        });

        // Add promo input focus styles
        promoCodeInput.addEventListener('focus', () => {
            promoCodeInput.style.borderColor = 'var(--orange)';
        });
        promoCodeInput.addEventListener('blur', () => {
            promoCodeInput.style.borderColor = 'var(--cream2)';
        });
    </script>
</body>
</html>
