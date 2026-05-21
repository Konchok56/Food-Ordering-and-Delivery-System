<?php 
// Ensure session and DB are available if needed
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$is_admin_dir = !file_exists('core/db.php');
$base_url = $is_admin_dir ? '../' : '';

// Fetch real cart count if not provided
if (!isset($cartCount) && isset($_SESSION['user_id'])) {
    if (!function_exists('getCartCount')) {
        $helper_path = $is_admin_dir ? '../core/cart_helper.php' : 'core/cart_helper.php';
        if (file_exists($helper_path)) include_once($helper_path);
    }
    if (function_exists('getCartCount')) {
        $db_path = $is_admin_dir ? '../core/db.php' : 'core/db.php';
        include_once($db_path);
        $cartCount = getCartCount($pdo, $_SESSION['user_id']);
    }
}
$displayCount = isset($cartCount) ? (int)$cartCount : 0;

// Fetch unread notification count
$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    $notif_helper_path = $is_admin_dir ? '../core/notification_helper.php' : 'core/notification_helper.php';
    if (file_exists($notif_helper_path)) {
        include_once($notif_helper_path);
        if (!isset($pdo)) {
            $db_path = $is_admin_dir ? '../core/db.php' : 'core/db.php';
            include_once($db_path);
        }
        if (function_exists('getUnreadNotificationCount')) {
            $notifCount = getUnreadNotificationCount($pdo, (int)$_SESSION['user_id']);
        }
    }
}
?>

<style>
  /* Notification Bell FAB */
  .notif-fab {
    position: fixed !important;
    bottom: 120px !important;
    right: 40px !important;
    z-index: 9999 !important;
    width: 56px !important;
    height: 56px !important;
    border: none !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #1a1004, #2d1f0f) !important;
    color: #fff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.4rem !important;
    cursor: pointer !important;
    box-shadow: 0 8px 32px rgba(26,16,4,0.35) !important;
    opacity: 1 !important;
    visibility: visible !important;
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1) !important;
  }
  .notif-fab:hover {
    transform: scale(1.1) translateY(-4px) !important;
    box-shadow: 0 14px 44px rgba(26,16,4,0.45) !important;
    background: linear-gradient(135deg, #ff4f00, #ff2400) !important;
  }
  .notif-fab .notif-badge {
    position: absolute !important;
    top: -4px !important;
    right: -4px !important;
    background: #ff2400 !important;
    color: #fff !important;
    font-size: 0.7rem !important;
    font-weight: 800 !important;
    min-width: 20px !important;
    height: 20px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 2px solid #fff !important;
    box-shadow: 0 2px 8px rgba(255,36,0,0.4) !important;
    padding: 0 4px !important;
  }
  .notif-fab .notif-badge:empty,
  .notif-fab .notif-badge[data-count="0"] {
    display: none !important;
  }

  /* Notification bell ring animation */
  @keyframes bellRing {
    0%, 100% { transform: rotate(0deg); }
    15% { transform: rotate(14deg); }
    30% { transform: rotate(-14deg); }
    45% { transform: rotate(8deg); }
    60% { transform: rotate(-8deg); }
    75% { transform: rotate(3deg); }
  }
  .notif-fab .bell-icon {
    display: inline-block;
    animation: bellRing 2.5s ease-in-out infinite;
    animation-delay: 3s;
  }

  /* Cart FAB */
  .cart-fab {
    position: fixed !important;
    bottom: 40px !important;
    right: 40px !important;
    z-index: 9999 !important;
    width: 64px !important;
    height: 64px !important;
    border: none !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #ff4f00, #ff2400) !important;
    color: #fff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.7rem !important;
    cursor: pointer !important;
    box-shadow: 0 10px 40px rgba(255,79,0,0.4) !important;
    opacity: 1 !important;
    visibility: visible !important;
  }
  .cart-fab:hover {
    transform: scale(1.12) translateY(-5px) !important;
    box-shadow: 0 16px 50px rgba(255,79,0,0.55) !important;
  }
  .cart-fab .cart-count {
    position: absolute !important;
    top: -3px !important;
    right: -3px !important;
    background: #1a1004 !important;
    color: #fff !important;
    font-size: 0.78rem !important;
    font-weight: 800 !important;
    width: 22px !important;
    height: 22px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 2px solid #fff !important;
  }
</style>

<!-- Notification Bell FAB -->
<?php if (isset($_SESSION['user_id'])): ?>
<button class="notif-fab" type="button" aria-label="Notifications"
  id="notifFabBtn"
  onclick="window.location.href='<?php echo $base_url; ?>user/notifications.php'">
    <span class="bell-icon">🔔</span>
    <span class="notif-badge" id="notifCountBadge" data-count="<?php echo $notifCount; ?>">
        <?php echo $notifCount > 0 ? $notifCount : ''; ?>
    </span>
</button>
<?php endif; ?>

<!-- Cart FAB -->
<button class="cart-fab" type="button" aria-label="Open cart"
  id="cartFabBtn"
  style="position:fixed!important;bottom:40px!important;right:40px!important;z-index:9999!important;display:flex!important;visibility:visible!important;opacity:1!important;"
  onclick="window.location.href='<?php echo $base_url; ?>orders/cart.php'">
    <span class="cart-fab-icon">🛒</span>
    <span class="cart-count" id="cartCountBadge"><?php echo $displayCount; ?></span>
</button>
<script>
(function(){
  // Fix cart FAB positioning
  var btn = document.getElementById('cartFabBtn');
  if(!btn) return;
  function fix() {
    btn.style.setProperty('position','fixed','important');
    btn.style.setProperty('bottom','40px','important');
    btn.style.setProperty('right','40px','important');
    btn.style.setProperty('z-index','9999','important');
    btn.style.setProperty('display','flex','important');
    btn.style.setProperty('visibility','visible','important');
    btn.style.setProperty('opacity','1','important');
  }
  fix();
  document.addEventListener('DOMContentLoaded', fix);
  window.addEventListener('load', fix);
})();
</script>

<?php if (isset($_SESSION['user_id'])): ?>
<script src="<?php echo $base_url; ?>assets/js/notifications.js"></script>
<?php endif; ?>

