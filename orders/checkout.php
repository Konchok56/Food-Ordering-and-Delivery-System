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
    <title><?php echo __('checkout', 'Checkout'); ?> — SwiftBite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

        .location-btn-wrap { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .use-location-btn {
            background: none; border: none; color: var(--orange); font-size: 0.85rem; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 5px; padding: 0; transition: all 0.2s;
        }
        .use-location-btn:hover { color: #ff2400; text-decoration: underline; }
        .use-location-btn i { font-size: 0.9rem; }
        .use-location-btn:disabled { color: var(--muted); cursor: not-allowed; text-decoration: none; }

        @media (max-width: 900px) {
            .checkout-inner { grid-template-columns: 1fr; }
            .summary-block { position: static; }
        }

        /* ── Map Modal ── */
        .map-modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);
        }
        .map-modal-overlay.open { display: flex; }
        .map-modal {
            background: #fff; border-radius: 28px; width: 90%; max-width: 720px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3); overflow: hidden;
            display: flex; flex-direction: column; max-height: 90vh;
        }
        .map-modal-header {
            padding: 20px 28px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--cream2);
        }
        .map-modal-header h3 { font-family: 'Syne',sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--dark); }
        .map-modal-close {
            width: 36px; height: 36px; border-radius: 50%; border: none; background: var(--cream2);
            font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--dark);
        }
        .map-modal-close:hover { background: #ffcfc0; }
        .map-modal-toolbar {
            padding: 14px 20px; display: flex; gap: 10px; align-items: center; background: var(--cream);
            border-bottom: 1px solid var(--cream2); flex-wrap: wrap;
        }
        .map-search-input {
            flex: 1; min-width: 180px; padding: 10px 14px; border: 2px solid var(--cream2);
            border-radius: 12px; font-family: 'DM Sans',sans-serif; font-size: 0.9rem; outline: none;
        }
        .map-search-input:focus { border-color: var(--orange); }
        .map-gps-btn {
            padding: 10px 16px; background: var(--orange); color: #fff; border: none;
            border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer;
            display: flex; align-items: center; gap: 6px; white-space: nowrap; transition: 0.2s;
        }
        .map-gps-btn:hover { background: #e04300; }
        .map-gps-btn:disabled { background: var(--muted); cursor: not-allowed; }
        #leafletMap { height: 360px; width: 100%; }
        .map-modal-footer {
            padding: 16px 20px; display: flex; gap: 10px; align-items: center; border-top: 1px solid var(--cream2);
        }
        .map-selected-addr {
            flex: 1; font-size: 0.85rem; color: var(--text); background: var(--cream);
            padding: 10px 14px; border-radius: 12px; line-height: 1.5; min-height: 42px;
        }
        .map-confirm-btn {
            padding: 12px 22px; background: linear-gradient(135deg,var(--orange),#ff2400); color: #fff;
            border: none; border-radius: 14px; font-weight: 800; font-size: 0.95rem; cursor: pointer; transition: 0.2s;
            white-space: nowrap;
        }
        .map-confirm-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,79,0,0.35); }
        .map-confirm-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }
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
                    <h2><i class="fa-solid fa-location-dot"></i> <?php echo __('delivery_details', 'Delivery Details'); ?></h2>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="name"><?php echo __('full_name_star', 'Full Name *'); ?></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email"><?php echo __('email_star', 'Email *'); ?></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><?php echo __('phone_number_star', 'Phone Number *'); ?></label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="<?php echo __('phone_placeholder', 'e.g. 9812345678'); ?>">
                        </div>
                        <div class="form-group full">
                            <div class="location-btn-wrap">
                                <label for="address"><?php echo __('delivery_address_star', 'Delivery Address *'); ?></label>
                                <button type="button" id="useLocationBtn" class="use-location-btn">
                                    <i class="fa-solid fa-location-crosshairs"></i> <?php echo __('use_current_location', 'Use Current Location'); ?>
                                </button>
                            </div>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required placeholder="<?php echo __('address_placeholder', 'Street, area, building...'); ?>">
                        </div>
                        <div class="form-group full">
                            <label for="city"><?php echo __('city_star', 'City *'); ?></label>
                            <select id="city" name="city" required>
                                <option value="Kathmandu" <?php echo ($user['city'] ?? 'Kathmandu') === 'Kathmandu' ? 'selected' : ''; ?>><?php echo __('kathmandu', 'Kathmandu'); ?></option>
                                <option value="Lalitpur" <?php echo ($user['city'] ?? '') === 'Lalitpur' ? 'selected' : ''; ?>><?php echo __('lalitpur', 'Lalitpur'); ?></option>
                                <option value="Bhaktapur" <?php echo ($user['city'] ?? '') === 'Bhaktapur' ? 'selected' : ''; ?>><?php echo __('bhaktapur', 'Bhaktapur'); ?></option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label for="notes"><?php echo __('delivery_notes_optional', 'Delivery Notes (Optional)'); ?></label>
                            <textarea id="notes" name="notes" placeholder="<?php echo __('notes_placeholder', 'e.g. Leave at the door, call when arrived...'); ?>"></textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Method -->
                <div class="checkout-block">
                    <h2>💳 <?php echo __('payment_method', 'Payment Method'); ?></h2>
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <span>💵 <?php echo __('cash_on_delivery_cod', 'Cash on Delivery (COD)'); ?></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="esewa">
                            <span>🟢 <?php echo __('esewa_coming_soon', 'eSewa (Coming Soon)'); ?></span>
                        </label>
                    </div>
                </div>

            </form>

            <!-- Sidebar Summary -->
            <div class="checkout-block summary-block">
                <h2><i class="fa-solid fa-cart-shopping"></i> <?php echo __('order_summary', 'Order Summary'); ?></h2>
                
                <div class="summary-items" style="margin-bottom: 20px;">
                    <?php foreach ($cart as $item): ?>
                        <?php 
                            $imgPath = !empty($item['food_image']) ? $item['food_image'] : (!empty($item['image_path']) ? $item['image_path'] : '');
                            $emojiIcon = !empty($item['food_emoji']) ? $item['food_emoji'] : (!empty($item['emoji']) ? $item['emoji'] : '<i class="fa-solid fa-burger"></i>');
                        ?>
                        <div class="summary-item">
                            <div class="summary-img">
                                <?php if (!empty($imgPath) && file_exists(__DIR__ . '/../' . $imgPath)): ?>
                                    <img src="../<?php echo htmlspecialchars($imgPath); ?>" alt="">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($emojiIcon); ?>
                                <?php endif; ?>
                            </div>
                            <div class="summary-info">
                                <div class="summary-name"><?php echo htmlspecialchars(__($item['food_name'], $item['food_name'])); ?></div>
                                <div class="summary-meta"><?php echo __('qty', 'Qty'); ?>: <?php echo t_num($item['quantity']); ?></div>
                            </div>
                            <div class="summary-price"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($item['price'] * $item['quantity'], 2)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="promo-code-block" style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <input type="text" id="promoCode" placeholder="<?php echo __('promo_code_placeholder', 'Promo Code'); ?>" style="flex: 1; padding: 12px; border-radius: 12px; border: 2px solid var(--cream2); font-family: 'DM Sans', sans-serif; outline: none; transition: 0.2s;">
                    <button type="button" id="applyPromoBtn" style="padding: 12px 20px; background: var(--dark); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;"><?php echo __('apply', 'Apply'); ?></button>
                </div>
                <div id="promoMessage" style="font-size: 0.85rem; margin-top: -12px; margin-bottom: 12px; font-weight: 600;"></div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span><?php echo __('subtotal', 'Subtotal'); ?></span>
                        <span><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($subtotal, 2)); ?></span>
                    </div>
                    <div class="summary-row">
                        <span><?php echo __('delivery_fee', 'Delivery Fee'); ?></span>
                        <span><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($deliveryFee, 2)); ?></span>
                    </div>
                    <div class="summary-row" id="discountRow" style="display: none; color: #34c759;">
                        <span><?php echo __('discount', 'Discount'); ?></span>
                        <span>- <?php echo __('currency_rs', 'Rs.'); ?> <span id="discountValue">०.००</span></span>
                    </div>
                    <div class="summary-row total">
                        <span><?php echo __('total', 'Total'); ?></span>
                        <span id="finalTotalStr"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format($total, 2)); ?></span>
                    </div>
                </div>

                <?php if (($user['status'] ?? 'active') === 'inactive'): ?>
                    <div style="margin-top: 24px; padding: 14px; background: rgba(255,59,48,0.1); border: 1px solid rgba(255,59,48,0.2); border-radius: 12px; color: #cc2d25; font-size: 0.9rem; font-weight: 600; text-align: center;">
                        <i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> <?php echo __('need_active_to_order', 'You need to be active to order. Please update your status in your profile.'); ?>
                    </div>
                    <button type="button" class="place-order-btn" style="background:#ccc; box-shadow:none; cursor:not-allowed;" disabled><i class="fa-solid fa-rocket"></i> <?php echo __('place_order', 'Place Order'); ?></button>
                <?php else: ?>
                    <button type="submit" form="checkoutForm" class="place-order-btn"><i class="fa-solid fa-rocket"></i> <?php echo __('place_order', 'Place Order'); ?></button>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ── Map Modal ── -->
    <div class="map-modal-overlay" id="mapModalOverlay">
        <div class="map-modal">
            <div class="map-modal-header">
                <h3><i class="fa-solid fa-map-location-dot" style="color:var(--orange);margin-right:8px;"></i><?php echo __('set_delivery_location', 'Set Delivery Location'); ?></h3>
                <button class="map-modal-close" id="mapModalClose" title="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="map-modal-toolbar">
                <input type="text" id="mapSearchInput" class="map-search-input" placeholder="<?php echo __('map_search_placeholder', 'Search an address or click on the map…'); ?>">
                <button type="button" id="mapSearchBtn" class="map-gps-btn" style="background:var(--dark);">
                    <i class="fa-solid fa-magnifying-glass"></i> <?php echo __('search', 'Search'); ?>
                </button>
                <button type="button" id="mapGpsBtn" class="map-gps-btn">
                    <i class="fa-solid fa-location-crosshairs"></i> <?php echo __('use_my_gps', 'Use My GPS'); ?>
                </button>
            </div>
            <div id="leafletMap"></div>
            <div class="map-modal-footer">
                <div class="map-selected-addr" id="mapSelectedAddr">
                    <span style="color:var(--muted);"><?php echo __('map_pin_instruction', 'Click anywhere on the map to pin your location.'); ?></span>
                </div>
                <button type="button" class="map-confirm-btn" id="mapConfirmBtn" disabled>
                    <i class="fa-solid fa-check"></i> <?php echo __('confirm', 'Confirm'); ?>
                </button>
            </div>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        const activeLang = '<?php echo $activeLang; ?>';

        function t_num_js(numStr) {
            if (activeLang !== 'ne') return numStr;
            const nepDigits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
            return numStr.toString().replace(/\d/g, d => nepDigits[d]);
        }

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
                applyPromoBtn.textContent = '<?php echo __('apply', 'Apply'); ?>';

                if (data.success) {
                    promoMessage.textContent = '✓ ' + data.message;
                    promoMessage.style.color = '#34c759';
                    
                    hiddenPromoCode.value = data.code;
                    
                    const discount = parseFloat(data.discount_amount);
                    const newTotal = originalTotal - discount;

                    discountRow.style.display = 'flex';
                    discountValue.textContent = t_num_js(discount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    
                    finalTotalStr.textContent = '<?php echo __('currency_rs', 'Rs.'); ?> ' + t_num_js(Math.max(0, newTotal).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                } else {
                    promoMessage.textContent = '✗ ' + data.message;
                    promoMessage.style.color = '#ff2400';
                    
                    hiddenPromoCode.value = '';
                    discountRow.style.display = 'none';
                    finalTotalStr.textContent = '<?php echo __('currency_rs', 'Rs.'); ?> ' + t_num_js(originalTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                }
            })
            .catch(err => {
                applyPromoBtn.disabled = false;
                applyPromoBtn.textContent = '<?php echo __('apply', 'Apply'); ?>';
                console.error(err);
            });
        });

        // Add promo input focus styles
        if (promoCodeInput) {
            promoCodeInput.addEventListener('focus', () => {
                promoCodeInput.style.borderColor = 'var(--orange)';
            });
            promoCodeInput.addEventListener('blur', () => {
                promoCodeInput.style.borderColor = 'var(--cream2)';
            });
        }

        // Open map modal when location button is clicked
        const useLocationBtn = document.getElementById('useLocationBtn');
        const addressInput = document.getElementById('address');
        const mapModalOverlay = document.getElementById('mapModalOverlay');
        const mapModalClose = document.getElementById('mapModalClose');
        const mapConfirmBtn = document.getElementById('mapConfirmBtn');
        const mapSelectedAddr = document.getElementById('mapSelectedAddr');
        const mapGpsBtn = document.getElementById('mapGpsBtn');
        const mapSearchBtn = document.getElementById('mapSearchBtn');
        const mapSearchInput = document.getElementById('mapSearchInput');

        let leafletMap = null, marker = null, pickedLat = null, pickedLon = null, pickedAddr = '';

        function initMap(lat, lon) {
            if (!leafletMap) {
                leafletMap = L.map('leafletMap').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors', maxZoom: 19
                }).addTo(leafletMap);
                leafletMap.on('click', function(e) { placeMarker(e.latlng.lat, e.latlng.lng); });
            } else {
                leafletMap.setView([lat, lon], 15);
            }
        }

        function placeMarker(lat, lon) {
            if (marker) marker.setLatLng([lat, lon]);
            else marker = L.marker([lat, lon]).addTo(leafletMap);
            pickedLat = lat; pickedLon = lon;
            mapSelectedAddr.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <?php echo __('fetching_address_dots', 'Fetching address…'); ?>';
            mapConfirmBtn.disabled = true;
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`)
                .then(r => r.json()).then(d => {
                    pickedAddr = d.display_name || `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
                    mapSelectedAddr.textContent = pickedAddr;
                    mapConfirmBtn.disabled = false;
                }).catch(() => {
                    pickedAddr = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
                    mapSelectedAddr.textContent = pickedAddr;
                    mapConfirmBtn.disabled = false;
                });
        }

        function openMapModal() {
            mapModalOverlay.classList.add('open');
            setTimeout(() => {
                initMap(27.7172, 85.3240); // default: Kathmandu
                leafletMap.invalidateSize();
                if (addressInput.value.trim()) mapSearchInput.value = addressInput.value.trim();
            }, 100);
        }

        if (useLocationBtn) useLocationBtn.addEventListener('click', openMapModal);

        mapModalClose.addEventListener('click', () => mapModalOverlay.classList.remove('open'));
        mapModalOverlay.addEventListener('click', (e) => { if (e.target === mapModalOverlay) mapModalOverlay.classList.remove('open'); });

        mapConfirmBtn.addEventListener('click', () => {
            if (!pickedAddr) return;
            addressInput.value = pickedAddr;
            mapModalOverlay.classList.remove('open');
        });

        // GPS inside modal
        mapGpsBtn.addEventListener('click', () => {
            if (!navigator.geolocation) { alert('<?php echo __('geolocation_not_supported', 'Geolocation not supported.'); ?>'); return; }
            mapGpsBtn.disabled = true;
            mapGpsBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <?php echo __('locating_dots', 'Locating…'); ?>';
            navigator.geolocation.getCurrentPosition(pos => {
                initMap(pos.coords.latitude, pos.coords.longitude);
                leafletMap.setView([pos.coords.latitude, pos.coords.longitude], 17);
                placeMarker(pos.coords.latitude, pos.coords.longitude);
                mapGpsBtn.disabled = false;
                mapGpsBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> <?php echo __('use_my_gps', 'Use My GPS'); ?>';
            }, err => {
                alert('<?php echo __('could_not_get_location', 'Could not get location: '); ?>' + (err.code === 1 ? '<?php echo __('permission_denied', 'Permission denied.'); ?>' : '<?php echo __('try_again', 'Try again.'); ?>'));
                mapGpsBtn.disabled = false;
                mapGpsBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> <?php echo __('use_my_gps', 'Use My GPS'); ?>';
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        // Search inside modal
        function doMapSearch() {
            const q = mapSearchInput.value.trim();
            if (!q) return;
            mapSearchBtn.disabled = true;
            mapSearchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`)
                .then(r => r.json()).then(results => {
                    if (results.length) {
                        const {lat, lon} = results[0];
                        initMap(parseFloat(lat), parseFloat(lon));
                        leafletMap.setView([parseFloat(lat), parseFloat(lon)], 16);
                        placeMarker(parseFloat(lat), parseFloat(lon));
                    } else { alert('<?php echo __('address_not_found', 'Address not found. Try a different search.'); ?>'); }
                    mapSearchBtn.disabled = false;
                    mapSearchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> <?php echo __('search', 'Search'); ?>';
                }).catch(() => {
                    mapSearchBtn.disabled = false;
                    mapSearchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> <?php echo __('search', 'Search'); ?>';
                });
        }
        mapSearchBtn.addEventListener('click', doMapSearch);
        mapSearchInput.addEventListener('keydown', e => { if (e.key === 'Enter') doMapSearch(); });
    </script>
</body>
</html>

