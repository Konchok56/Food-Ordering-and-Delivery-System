<?php
session_start();
include('../core/db.php');
include('../core/cart_helper.php');
include('../core/csrf.php');

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
$csrfToken = generateCsrfToken();

define('CANCEL_WINDOW_SECONDS', 30 * 60); // 30-minute cancellation window
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/style.css?v=8" />
    <style>
        .orders-page { padding: 100px 24px 60px; min-height: 100vh; background: var(--cream); }
        .orders-inner { max-width: 900px; margin: 0 auto; }
        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--dark); }

        .order-card { background: #fff; border-radius: 28px; padding: 28px; box-shadow: var(--shadow); margin-bottom: 24px; transition: transform 0.2s; }
        .order-card:hover { transform: translateY(-3px); box-shadow: 0 16px 50px rgba(255, 79, 0, 0.12); }
        .order-card.cancelling { opacity: 0.5; pointer-events: none; transition: opacity 0.4s; }

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

        .order-actions { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; }
        .view-btn { display: inline-flex; padding: 12px 24px; background: var(--cream2); color: var(--dark); border-radius: 14px; font-weight: 700; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
        .view-btn:hover { background: var(--orange); color: #fff; }

        /* Cancel Button */
        .cancel-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 20px; background: #fff0f0; color: #d93025;
            border: 2px solid #ffd0ce; border-radius: 14px; font-weight: 700;
            font-size: 0.9rem; cursor: pointer; transition: all 0.25s;
            white-space: nowrap;
        }
        .cancel-btn:hover { background: #d93025; color: #fff; border-color: #d93025; }
        .cancel-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Countdown Timer */
        .cancel-countdown {
            font-size: 0.78rem; font-weight: 600; color: var(--muted);
            text-align: right; display: flex; align-items: center; gap: 5px;
        }
        .cancel-countdown .timer-pill {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(255,184,48,0.12); color: #b87a00;
            padding: 3px 10px; border-radius: 999px; font-weight: 700;
            font-size: 0.8rem;
        }
        .cancel-countdown .timer-pill.urgent { background: rgba(217,48,37,0.12); color: #d93025; }

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

        /* Toast Notification */
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

        .empty-state { text-align: center; padding: 80px 20px; background: #fff; border-radius: 28px; box-shadow: var(--shadow); }
        .empty-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 1.5rem; color: var(--dark); margin-bottom: 8px; }
        .empty-state p { color: var(--muted); margin-bottom: 24px; max-width: 400px; margin-inline: auto; }
        .empty-btn { display: inline-flex; padding: 16px 32px; background: var(--orange); color: #fff; border-radius: 999px; font-weight: 700; text-decoration: none; box-shadow: 0 8px 30px rgba(255,79,0,0.3); transition: transform 0.2s; }
        .empty-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }

        @media (max-width: 500px) {
            .order-top { flex-direction: column; }
            .order-actions { align-items: stretch; width: 100%; }
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
            <p>Are you sure you want to cancel <strong id="modalOrderLabel">this order</strong>? This action <strong>cannot be undone</strong> and the order will be permanently removed.</p>
            <div class="modal-btns">
                <button class="modal-keep" id="modalKeepBtn">Keep Order</button>
                <button class="modal-confirm-cancel" id="modalCancelBtn">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="order-toast" id="orderToast"></div>

    <div class="orders-page">
        <div class="orders-inner">

            <div class="page-header">
                <h1><i class="fa-solid fa-box"></i> My Orders</h1>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
                    <h3>No orders yet</h3>
                    <p>You haven't placed any orders. Start exploring our delicious menu to satisfy your cravings!</p>
                    <a href="../menu.php" class="empty-btn">Explore Menu</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                        $statusMap = [
                            'pending'          => ['icon' => '<i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i>', 'text' => 'Waiting for confirmation'],
                            'confirmed'        => ['icon' => '👍', 'text' => 'Confirmed'],
                            'preparing'        => ['icon' => '🧑‍🍳', 'text' => 'Preparing Food'],
                            'ready'            => ['icon' => '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i>', 'text' => 'Ready for Pickup'],
                            'out_for_delivery' => ['icon' => '<i class="fa-solid fa-motorcycle"></i>', 'text' => 'Out for Delivery'],
                            'delivered'        => ['icon' => '<i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i>', 'text' => 'Delivered'],
                            'cancelled'        => ['icon' => '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>', 'text' => 'Cancelled'],
                        ];
                        $s = $statusMap[$order['status']] ?? ['icon' => '<i class="fa-solid fa-box"></i>', 'text' => ucfirst($order['status'])];

                        $deadline       = strtotime($order['created_at']) + CANCEL_WINDOW_SECONDS;
                        $canCancel      = ($order['status'] === 'pending') && (time() < $deadline);
                        $deadlineMs     = $deadline * 1000; // JS uses ms
                    ?>
                    <div class="order-card" id="order-card-<?php echo $order['id']; ?>">
                        <div class="order-top">
                            <div>
                                <div class="order-id">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date"><?php echo date('M d, Y • h:i A', strtotime($order['created_at'])); ?></div>
                            </div>
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

                            <div class="order-actions">
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="view-btn">View Details →</a>

                                <?php if ($canCancel): ?>
                                    <button
                                        class="cancel-btn"
                                        id="cancel-btn-<?php echo $order['id']; ?>"
                                        data-order-id="<?php echo $order['id']; ?>"
                                        data-order-label="Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>"
                                        data-deadline="<?php echo $deadlineMs; ?>"
                                    >
                                        <i class="fa-solid fa-trash"></i> Cancel Order
                                    </button>
                                    <div class="cancel-countdown" id="countdown-<?php echo $order['id']; ?>">
                                        <span>Cancel within:</span>
                                        <span class="timer-pill" id="timer-<?php echo $order['id']; ?>">--:--</span>
                                    </div>
                                <?php endif; ?>
                            </div>
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
    <script>
    (function () {
        const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
        const modal      = document.getElementById('cancelModal');
        const keepBtn    = document.getElementById('modalKeepBtn');
        const confirmBtn = document.getElementById('modalCancelBtn');
        const toast      = document.getElementById('orderToast');

        let pendingOrderId    = null;
        let pendingOrderLabel = null;

        /* ── Countdown Timers ── */
        document.querySelectorAll('.cancel-btn[data-deadline]').forEach(btn => {
            const orderId  = btn.dataset.orderId;
            const deadline = parseInt(btn.dataset.deadline, 10);
            const timerEl  = document.getElementById('timer-' + orderId);
            const pillEl   = timerEl;

            function updateTimer() {
                const remaining = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
                if (remaining <= 0) {
                    btn.disabled = true;
                    btn.textContent = '⛔ Window Expired';
                    const countdownEl = document.getElementById('countdown-' + orderId);
                    if (countdownEl) countdownEl.style.display = 'none';
                    return;
                }
                const mins = String(Math.floor(remaining / 60)).padStart(2, '0');
                const secs = String(remaining % 60).padStart(2, '0');
                timerEl.textContent = `⏱ ${mins}:${secs}`;
                if (remaining <= 120) pillEl.classList.add('urgent');
                else pillEl.classList.remove('urgent');
                setTimeout(updateTimer, 1000);
            }
            updateTimer();
        });

        /* ── Open Modal ── */
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.disabled) return;
                pendingOrderId    = this.dataset.orderId;
                pendingOrderLabel = this.dataset.orderLabel;
                document.getElementById('modalOrderLabel').textContent = pendingOrderLabel;
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Yes, Cancel';
                modal.classList.add('active');
            });
        });

        /* ── Close Modal ── */
        keepBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
        function closeModal() {
            modal.classList.remove('active');
            pendingOrderId    = null;
            pendingOrderLabel = null;
        }

        /* ── Confirm Cancellation ── */
        confirmBtn.addEventListener('click', function () {
            if (!pendingOrderId) return;
            this.disabled       = true;
            this.textContent    = 'Cancelling…';

            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            formData.append('order_id',   pendingOrderId);

            fetch('../actions/cancel_order.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    closeModal();
                    if (data.success) {
                        // Animate card out and remove it
                        const card = document.getElementById('order-card-' + pendingOrderId);
                        if (card) {
                            card.style.transition = 'all 0.45s ease';
                            card.style.opacity    = '0';
                            card.style.transform  = 'scale(0.95)';
                            card.style.maxHeight  = card.offsetHeight + 'px';
                            setTimeout(() => {
                                card.style.maxHeight = '0';
                                card.style.margin    = '0';
                                card.style.padding   = '0';
                                setTimeout(() => card.remove(), 300);
                            }, 350);
                        }
                        showToast('<i class="fa-solid fa-circle-check" style="color:#22c55e"></i> ' + data.message, 'success');
                    } else {
                        showToast('<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> ' + data.message, 'error');
                    }
                })
                .catch(() => {
                    closeModal();
                    showToast('<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> Network error. Please try again.', 'error');
                });
        });

        /* ── Toast Helper ── */
        function showToast(msg, type = '') {
            toast.textContent  = msg;
            toast.className    = 'order-toast' + (type ? ' ' + type : '');
            void toast.offsetWidth;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 4000);
        }
    })();
    </script>
</body>
</html>
