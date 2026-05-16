<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch role if logged in
$nav_role = '';
if (isset($_SESSION['user_id'])) {
    $nav_db_path = file_exists('core/db.php') ? 'core/db.php' : '../core/db.php';
    include_once($nav_db_path);
    $nav_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $nav_stmt->execute([$_SESSION['user_id']]);
    $nav_role = $nav_stmt->fetchColumn();
}

$nav_base_url = !file_exists('core/db.php') ? '../' : '';

// Include cart helper if not already included (e.g. by bootstrap.php)
if (!function_exists('getCartCount')) {
    $nav_helper_path = file_exists('core/cart_helper.php') ? 'core/cart_helper.php' : '../core/cart_helper.php';
    include_once($nav_helper_path);
}
?>

<script>
    window.SwiftBiteConfig = {
        baseUrl: '<?php echo $nav_base_url; ?>'
    };
    /* Apply theme IMMEDIATELY to avoid flash of wrong theme */
    (function () { var t = localStorage.getItem('sb-theme') || 'light'; document.documentElement.setAttribute('data-theme', t); })();
</script>
<script src="<?php echo $nav_base_url; ?>assets/js/theme.js"></script>

<nav>
    <a class="logo" href="<?php echo $nav_base_url; ?>index.php">Swift<span>Bite</span></a>

    <ul class="nav-links">
        <li><a href="<?php echo $nav_base_url; ?>index.php">Home</a></li>
        <li><a href="<?php echo $nav_base_url; ?>restaurants.php">Restaurants</a></li>
        <li><a href="<?php echo $nav_base_url; ?>menu.php">Menu</a></li>
        <li><a href="#categories">Categories</a></li>
        <li><a href="#offers">Offers</a></li>
        <li>
            <a href="<?php echo $nav_base_url; ?>orders/cart.php" class="nav-cart-link">
                Cart
                <span class="cart-badge" id="cartCount"
                    data-cart-count><?php echo isset($_SESSION['user_id']) ? getCartCount($pdo, $_SESSION['user_id']) : 0; ?></span>
            </a>
        </li>
    </ul>

    <div class="nav-right">

        <!-- Theme Toggle -->
        <button id="theme-toggle" class="theme-toggle-btn" title="Switch to Dark Mode"
            aria-label="Toggle dark/light mode">
            <span class="theme-icon theme-icon-sun">&#9728;&#65039;</span>
            <span class="theme-icon theme-icon-moon">&#127769;</span>
        </button>

        <a class="btn-ghost" href="#support">Support</a>

        <?php if (isset($_SESSION['user_name'])): ?>
            <!-- Logged-in user nav buttons -->
            <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/profile.php" title="My Profile">
                <span class="nav-user-icon">👤</span>
                <span class="nav-user-label"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
            </a>

            <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/user_dashboard.php" title="My Dashboard">
                <span class="nav-user-icon">📊</span>
                <span class="nav-user-label">Dashboard</span>
            </a>

            <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/order_history.php" title="My Orders">
                <span class="nav-user-icon">📦</span>
                <span class="nav-user-label">Orders</span>
            </a>
            <?php if ($nav_role === 'admin'): ?>
                <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/dashboard.php"
                    title="Admin Panel">
                    <span class="nav-user-icon">👨‍💼</span>
                    <span class="nav-user-label">Admin</span>
                </a>

                <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/delivery_partner.php"
                    title="Delivery Partner Panel">
                    <span class="nav-user-icon">🚚</span>
                    <span class="nav-user-label">Delivery</span>
                </a>
            <?php endif; ?>

            <?php if ($nav_role === 'restaurant'): ?>
                <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>owner/dashboard.php" title="My Restaurant"
                    style="background:rgba(255,79,0,0.08); border:1px solid rgba(255,79,0,0.2);">
                    <span class="nav-user-icon">🍽️</span>
                    <span class="nav-user-label">My Restaurant</span>
                </a>
            <?php endif; ?>
            <a class="nav-user-btn nav-logout-btn" href="<?php echo $nav_base_url; ?>auth/logout.php" title="Logout">
                <span class="nav-user-icon">🚪</span>
                <span class="nav-user-label">Logout</span>
            </a>
        <?php else: ?>
            <a href="<?php echo $nav_base_url; ?>auth/login.php" class="btn-primary">
                Login
            </a>
        <?php endif; ?>

        <a class="btn-primary" href="#menu">Order Now</a>

    </div>

    <button class="mobile-menu-btn" type="button" data-mobile-toggle>
        ☰
    </button>
</nav>