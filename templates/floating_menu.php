<?php 
// Ensure session and DB are available if needed
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$is_admin_dir = !file_exists('core/db.php');
$base_url = $is_admin_dir ? '../' : '';
?>

<button class="cart-fab" type="button" aria-label="Open cart" onclick="window.location.href='<?php echo $base_url; ?>orders/cart.php'">
    🛒
    <span class="cart-count" id="cartCount"><?php echo isset($cartCount) ? $cartCount : 0; ?></span>
</button>
