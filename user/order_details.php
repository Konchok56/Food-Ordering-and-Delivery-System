<?php
session_start();
include('../core/config.php');
include('../core/db.php');
include('../core/cart_helper.php');
include('../core/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
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
$csrfToken = generateCsrfToken();

define('CANCEL_WINDOW_SECONDS', 30 * 60);
$deadline  = strtotime($order['created_at']) + CANCEL_WINDOW_SECONDS;
$canCancel = ($order['status'] === 'pending') && (time() < $deadline);
$deadlineMs = $deadline * 1000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Details #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?> — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
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

        /* ── Cancel Section ── */
        .cancel-section {
            background: #fff; border-radius: 28px; padding: 28px 32px;
            box-shadow: var(--shadow); margin-bottom: 24px;
            border: 2px solid #ffe8e6;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 20px;
        }
        .cancel-section-info h3 {
            font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 800;
            color: #d93025; margin-bottom: 6px;
        }
        .cancel-section-info p { color: var(--muted); font-size: 0.92rem; line-height: 1.5; }
        .cancel-countdown-detail {
            display: flex; align-items: center; gap: 8px; margin-top: 8px;
            font-size: 0.85rem; color: var(--muted); font-weight: 600;
        }
        .timer-pill {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(255,184,48,0.12); color: #b87a00;
            padding: 4px 12px; border-radius: 999px; font-weight: 700;
            font-size: 0.85rem;
        }
        .timer-pill.urgent { background: rgba(217,48,37,0.12); color: #d93025; }
        .cancel-detail-btn {
            padding: 14px 28px; background: #d93025;
            color: #fff; border: none; border-radius: 16px; font-weight: 800;
            font-size: 1rem; cursor: pointer; transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(217,48,37,0.3); white-space: nowrap;
        }
        .cancel-detail-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(217,48,37,0.4); }
        .cancel-detail-btn:disabled { opacity: 0.45; pointer-events: none; }

        /* Window Expired Notice */
        .cancel-expired {
            background: #fff; border-radius: 28px; padding: 24px 32px;
            box-shadow: var(--shadow); margin-bottom: 24px;
            border: 2px solid var(--cream2);
            display: flex; align-items: center; gap: 14px; color: var(--muted);
            font-size: 0.95rem;
        }
        .cancel-expired span { font-size: 1.6rem; }

        /* Cancel Confirmation Modal */
        .cancel-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.55); backdrop-filter: blur(6px);
            z-index: 9999; align-items: center; justify-content: center;
        }
        .cancel-modal-overlay.active { display: flex; }
        .cancel-modal {
            background: #fff; border-radius: 28px; padding: 40px 36px;
            max-width: 440px; width: 90%; text-align: center;
            box-shadow: 0 30px 80px rgba(0,0,0,0.2);
            animation: modalSlide 0.35s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes modalSlide {
            from { opacity: 0; transform: scale(0.85) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .cancel-modal .modal-icon {
            width: 72px; height: 72px; margin: 0 auto 20px;
            background: #fff0f0; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem;
        }
        .cancel-modal h3 {
            font-family: 'Syne', sans-serif; font-size: 1.5rem;
            font-weight: 800; color: var(--dark); margin-bottom: 10px;
        }
        .cancel-modal p { color: var(--muted); font-size: 1rem; line-height: 1.6; margin-bottom: 28px; }
        .cancel-modal p strong { color: var(--dark); }
        .modal-btns { display: flex; gap: 12px; }
        .modal-btns button {
            flex: 1; padding: 14px; border-radius: 14px; font-weight: 700;
            font-size: 1rem; cursor: pointer; border: none; transition: all 0.2s;
        }
        .modal-keep { background: var(--cream2); color: var(--dark); }
        .modal-keep:hover { background: #ffe4c4; }
        .modal-confirm-cancel {
            background: linear-gradient(135deg, #d93025, #ff4136);
            color: #fff; box-shadow: 0 8px 24px rgba(217,48,37,0.3);
        }
        .modal-confirm-cancel:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(217,48,37,0.4); }
        .modal-confirm-cancel:disabled { opacity: 0.5; pointer-events: none; }

        /* Toast */
        .order-toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(20px);
            background: #111; color: #fff; padding: 14px 28px; border-radius: 999px;
            font-weight: 600; font-size: 0.95rem; z-index: 10000;
            opacity: 0; transition: all 0.35s ease; pointer-events: none;
            white-space: nowrap; box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .order-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .order-toast.success { background: linear-gradient(135deg, #1a7a34, #34c759); }
        .order-toast.error { background: linear-gradient(135deg, #d93025, #ff4136); }

        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .cancel-section { flex-direction: column; }
            .modal-btns { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <!-- Cancel Confirmation Modal -->
    <div class="cancel-modal-overlay" id="cancelModal">
        <div class="cancel-modal">
            <div class="modal-icon"><i class="fa-solid fa-trash"></i></div>
            <h3>Cancel Order?</h3>
            <p>Are you sure you want to cancel <strong>Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>? This action <strong>cannot be undone</strong> and the order will be permanently removed.</p>
            <div class="modal-btns">
                <button class="modal-keep" id="modalKeepBtn">Keep Order</button>
                <button class="modal-confirm-cancel" id="modalCancelBtn">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="order-toast" id="orderToast"></div>

    <div class="page">
        <div class="inner">
            <div class="header">
                <a href="order_history.php" class="back-btn">← Back</a>
                <h1>Order Details</h1>
            </div>

            <!-- ── Cancellation Banner ── -->
            <?php if ($canCancel): ?>
                <div class="cancel-section" id="cancelSection">
                    <div class="cancel-section-info">
                        <h3><i class="fa-solid fa-trash"></i> Cancel This Order</h3>
                        <p>You can cancel this order while it's still pending. Once confirmed or being prepared, cancellation is no longer possible.</p>
                        <div class="cancel-countdown-detail">
                            <span>Cancellation window closes in:</span>
                            <span class="timer-pill" id="detailTimer">--:--</span>
                        </div>
                    </div>
                    <button class="cancel-detail-btn" id="openCancelModal"><i class="fa-solid fa-trash"></i> Cancel Order</button>
                </div>
            <?php elseif ($order['status'] === 'pending'): ?>
                <div class="cancel-expired">
                    <span><i class="fa-regular fa-clock"></i></span>
                    <span>The 30-minute cancellation window for this order has expired. Please contact support if you need assistance.</span>
                </div>
            <?php endif; ?>

            <!-- ── Live Tracking Section ── -->
            <?php if ($order['status'] === 'out_for_delivery'): ?>
                <div class="card" style="border: 2px solid var(--orange); background: linear-gradient(135deg, #fff, #fff8f0);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                        <div>
                            <h3 style="font-family:'Syne', sans-serif; font-size:1.3rem; margin:0 0 4px; color:var(--dark);"><i class="fa-solid fa-motorcycle"></i> Your order is on the way!</h3>
                            <p style="color:var(--muted); margin:0; font-size:0.95rem;">Track your rider's live location on the map.</p>
                        </div>
                        <a href="../orders/track.php?id=<?php echo $order['id']; ?>" class="cancel-detail-btn" style="background:var(--orange); box-shadow: 0 8px 24px rgba(255,79,0,0.3); text-decoration:none; display:inline-block;">
                            <i class="fa-solid fa-location-dot"></i> Track Live Location
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Items Card -->
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
                                    <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($item['image_path']); ?>" alt="">
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
                    <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                    <div class="summary-row" style="color: #1a7a34;">
                        <span>Promo Discount</span>
                        <span>− Rs. <?php echo number_format($order['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span>Rs. <?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Delivery Details Card -->
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

    <?php include '../templates/floating_menu.php'; ?>
    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/cart.js"></script>

    <?php if ($canCancel): ?>
    <script>
    (function () {
        const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
        const ORDER_ID   = <?php echo (int)$order['id']; ?>;
        const DEADLINE   = <?php echo $deadlineMs; ?>;

        const modal      = document.getElementById('cancelModal');
        const keepBtn    = document.getElementById('modalKeepBtn');
        const confirmBtn = document.getElementById('modalCancelBtn');
        const openBtn    = document.getElementById('openCancelModal');
        const timerEl    = document.getElementById('detailTimer');
        const toast      = document.getElementById('orderToast');

        /* ── Countdown Timer ── */
        function updateTimer() {
            const remaining = Math.max(0, Math.floor((DEADLINE - Date.now()) / 1000));
            if (remaining <= 0) {
                openBtn.disabled     = true;
                openBtn.textContent  = '⛔ Window Expired';
                timerEl.textContent  = 'Expired';
                timerEl.classList.add('urgent');
                return;
            }
            const mins = String(Math.floor(remaining / 60)).padStart(2, '0');
            const secs = String(remaining % 60).padStart(2, '0');
            timerEl.textContent = `⏱ ${mins}:${secs}`;
            if (remaining <= 120) timerEl.classList.add('urgent');
            else timerEl.classList.remove('urgent');
            setTimeout(updateTimer, 1000);
        }
        updateTimer();

        /* ── Modal controls ── */
        openBtn.addEventListener('click', () => {
            confirmBtn.disabled     = false;
            confirmBtn.textContent  = 'Yes, Cancel';
            modal.classList.add('active');
        });
        keepBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        function closeModal() { modal.classList.remove('active'); }

        /* ── Confirm Cancellation ── */
        confirmBtn.addEventListener('click', function () {
            this.disabled      = true;
            this.textContent   = 'Cancelling…';

            const body = new FormData();
            body.append('csrf_token', CSRF_TOKEN);
            body.append('order_id',   ORDER_ID);

            fetch('../actions/cancel_order.php', { method: 'POST', body })
                .then(r => r.json())
                .then(data => {
                    closeModal();
                    if (data.success) {
                        showToast('<i class="fa-solid fa-circle-check" style="color:#22c55e"></i> ' + data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'order_history.php';
                        }, 2000);
                    } else {
                        confirmBtn.disabled    = false;
                        confirmBtn.textContent = 'Yes, Cancel';
                        showToast('<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> ' + data.message, 'error');
                    }
                })
                .catch(() => {
                    closeModal();
                    showToast('<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> Network error. Please try again.', 'error');
                });
        });

        /* ── Toast ── */
        function showToast(msg, type = '') {
            toast.textContent = msg;
            toast.className   = 'order-toast' + (type ? ' ' + type : '');
            void toast.offsetWidth;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 4000);
        }
    })();
    </script>
    <?php endif; ?>
</body>
</html>
