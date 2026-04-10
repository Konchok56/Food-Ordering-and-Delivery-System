<?php 
// Ensure session and DB are available if needed
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$is_admin_dir = !file_exists('core/db.php');
$base_url = $is_admin_dir ? '../' : '';

// Fetch real count if not provided
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
?>

<style>
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

<button class="cart-fab" type="button" aria-label="Open cart"
  id="cartFabBtn"
  style="position:fixed!important;bottom:40px!important;right:40px!important;z-index:9999!important;display:flex!important;visibility:visible!important;opacity:1!important;"
  onclick="window.location.href='<?php echo $base_url; ?>orders/cart.php'">
    <span class="cart-fab-icon">🛒</span>
    <span class="cart-count" id="cartCountBadge"><?php echo $displayCount; ?></span>
</button>
<script>
(function(){
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
