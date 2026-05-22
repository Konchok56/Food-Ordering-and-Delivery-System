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

// Get the active language for the dropdown flag indicator
$nav_current_lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
?>
<!-- Include Flag Dropdown Styles -->
<link rel="stylesheet" href="<?php echo $nav_base_url; ?>assets/css/lang.css?v=2" />

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
        <li><a href="<?php echo $nav_base_url; ?>index.php"><?php echo __('nav_home', 'Home'); ?></a></li>
        <li><a href="<?php echo $nav_base_url; ?>restaurants.php"><?php echo __('nav_restaurants', 'Restaurants'); ?></a></li>
        <li><a href="<?php echo $nav_base_url; ?>menu.php"><?php echo __('nav_menu', 'Menu'); ?></a></li>
        <li><a href="#categories"><?php echo __('nav_categories', 'Categories'); ?></a></li>
        <li><a href="#offers"><?php echo __('nav_offers', 'Offers'); ?></a></li>
        <li>
            <a href="<?php echo $nav_base_url; ?>orders/cart.php" class="nav-cart-link">
                <?php echo __('nav_cart', 'Cart'); ?>
                <span class="cart-badge" id="cartCount"
                    data-cart-count><?php echo isset($_SESSION['user_id']) ? getCartCount($pdo, $_SESSION['user_id']) : 0; ?></span>
            </a>
        </li>
    </ul>

    <div class="nav-right">

        <!-- Theme Toggle -->
        <button id="theme-toggle" class="theme-toggle-btn" title="Switch to Dark Mode"
            aria-label="Toggle dark/light mode">
            <span class="theme-icon theme-icon-sun"><i class="fa-solid fa-sun"></i></span>
            <span class="theme-icon theme-icon-moon"><i class="fa-solid fa-moon"></i></span>
        </button>

        <!-- Premium Language Selector -->
        <div class="lang-selector">
            <?php
            $lang_flags = [
                'en' => '🇬🇧 EN',
                'ne' => '🇳🇵 NE',
                'ja' => '🇯🇵 JA'
            ];
            $active_flag = $lang_flags[$nav_current_lang] ?? '🇬🇧 EN';
            ?>
            <button class="lang-btn" type="button" aria-haspopup="true" aria-expanded="false">
                <span><?php echo $active_flag; ?></span> <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="lang-dropdown">
                <a href="?lang=en" class="lang-option <?php echo $nav_current_lang === 'en' ? 'active' : ''; ?>">🇬🇧 English</a>
                <a href="?lang=ne" class="lang-option <?php echo $nav_current_lang === 'ne' ? 'active' : ''; ?>">🇳🇵 नेपाली</a>
                <a href="?lang=ja" class="lang-option <?php echo $nav_current_lang === 'ja' ? 'active' : ''; ?>">🇯🇵 日本語</a>
            </div>
        </div>

        <a class="btn-ghost" href="#support"><?php echo __('nav_support', 'Support'); ?></a>

        <?php if (isset($_SESSION['user_name'])): ?>
            <!-- Logged-in user nav buttons -->
            <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/profile.php" title="<?php echo __('nav_profile', 'My Profile'); ?>">
                <span class="nav-user-icon"><i class="fa-solid fa-user"></i></span>
                <span class="nav-user-label"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
            </a>

            <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/user_dashboard.php" title="<?php echo __('nav_dashboard', 'My Dashboard'); ?>">
                <span class="nav-user-icon"><i class="fa-solid fa-chart-bar"></i></span>
                <span class="nav-user-label"><?php echo __('nav_dashboard', 'Dashboard'); ?></span>
            </a>

            <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/order_history.php" title="<?php echo __('nav_orders', 'My Orders'); ?>">
                <span class="nav-user-icon"><i class="fa-solid fa-box"></i></span>
                <span class="nav-user-label"><?php echo __('nav_orders', 'Orders'); ?></span>
            </a>
            <?php if ($nav_role === 'admin'): ?>
                <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/dashboard.php"
                    title="Admin Panel">
                    <span class="nav-user-icon"><i class="fa-solid fa-user-tie"></i></span>
                    <span class="nav-user-label"><?php echo __('nav_admin', 'Admin'); ?></span>
                </a>

                <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/delivery_partner.php"
                    title="Delivery Partner Panel">
                    <span class="nav-user-icon"><i class="fa-solid fa-truck"></i></span>
                    <span class="nav-user-label"><?php echo __('nav_delivery', 'Delivery'); ?></span>
                </a>
            <?php endif; ?>

            <?php if ($nav_role === 'restaurant'): ?>
                <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>owner/dashboard.php" title="My Restaurant"
                    style="background:rgba(255,79,0,0.08); border:1px solid rgba(255,79,0,0.2);">
                    <span class="nav-user-icon"><i class="fa-solid fa-utensils"></i></span>
                    <span class="nav-user-label"><?php echo __('nav_my_restaurant', 'My Restaurant'); ?></span>
                </a>
            <?php endif; ?>
            <a class="nav-user-btn nav-logout-btn" href="<?php echo $nav_base_url; ?>auth/logout.php" title="<?php echo __('nav_logout', 'Logout'); ?>">
                <span class="nav-user-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span class="nav-user-label"><?php echo __('nav_logout', 'Logout'); ?></span>
            </a>
        <?php else: ?>
            <a href="<?php echo $nav_base_url; ?>auth/login.php" class="btn-primary">
                <?php echo __('nav_login', 'Login'); ?>
            </a>
        <?php endif; ?>

        <a class="btn-primary" href="#menu"><?php echo __('nav_order_now', 'Order Now'); ?></a>

    </div>

    <button class="mobile-menu-btn" type="button" data-mobile-toggle>
        <i class="fa-solid fa-bars"></i>
    </button>
</nav>

<script>
// ── Language Switcher Toggle ──
(function () {
    const selector = document.querySelector('.lang-selector');
    const btn      = selector ? selector.querySelector('.lang-btn') : null;
    if (!btn) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        selector.classList.toggle('open');
        btn.setAttribute('aria-expanded', selector.classList.contains('open'));
    });

    // Close when clicking anywhere outside
    document.addEventListener('click', function () {
        selector.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    });
})();
</script>