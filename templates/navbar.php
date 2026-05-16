<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Load lang helper if not already loaded by bootstrap.php
if (!function_exists('__')) {
    $nav_lang_path = file_exists('core/lang.php') ? 'core/lang.php' : '../core/lang.php';
    require_once $nav_lang_path;
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
<!-- GDODS-38: Multi-language CSS -->
<link rel="stylesheet" href="<?php echo $nav_base_url; ?>assets/css/lang.css">

<script>
    window.SwiftBiteConfig = {
        baseUrl: '<?php echo $nav_base_url; ?>'
    };
</script>

<nav <?php echo isRtlLang() ? 'dir="rtl"' : ''; ?>>
  <a class="logo" href="<?php echo $nav_base_url; ?>index.php">Swift<span>Bite</span></a>

  <ul class="nav-links">
    <li><a href="<?php echo $nav_base_url; ?>index.php"><?= __('nav.home') ?></a></li>
    <li><a href="<?php echo $nav_base_url; ?>restaurants.php"><?= __('nav.restaurants') ?></a></li>
    <li><a href="<?php echo $nav_base_url; ?>menu.php"><?= __('nav.menu') ?></a></li>
    <li><a href="#categories"><?= __('nav.categories') ?></a></li>
    <li><a href="#offers"><?= __('nav.offers') ?></a></li>
    <li>
      <a href="<?php echo $nav_base_url; ?>orders/cart.php" class="nav-cart-link">
        <?= __('nav.cart') ?>
        <span class="cart-badge" id="cartCount" data-cart-count><?php echo isset($_SESSION['user_id']) ? getCartCount($pdo, $_SESSION['user_id']) : 0; ?></span>
      </a>
    </li>
  </ul>

  <div class="nav-right">
    
    <a class="btn-ghost" href="#support"><?= __('nav.support') ?></a>

    <!-- ── Language Switcher ─────────────────────────────── -->
    <div class="lang-switcher" title="<?= __('lang.label') ?>">
      <span class="lang-globe">🌐</span>
      <div class="lang-dropdown">
        <?php foreach (SUPPORTED_LANGS as $code => $label): ?>
          <a href="<?= htmlspecialchars(langSwitchUrl($code)) ?>"
             class="lang-option <?= currentLang() === $code ? 'lang-active' : '' ?>">
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- ────────────────────────────────────────────────── -->

    <!-- ── Dark Mode Toggle ────────────────────────────── -->
    <button id="theme-toggle" type="button" title="Switch to Dark Mode" aria-label="Switch to Dark Mode"
            style="background:none;border:none;cursor:pointer;font-size:1.25rem;padding:6px 8px;border-radius:8px;line-height:1;transition:background .2s;"
            onmouseover="this.style.background='rgba(0,0,0,0.08)'" onmouseout="this.style.background='none'">
      <span class="theme-icon-light">🌙</span>
      <span class="theme-icon-dark" style="display:none">☀️</span>
    </button>
    <!-- ────────────────────────────────────────────────── -->


    <?php if(isset($_SESSION['user_name'])): ?>
        <!-- Logged-in user nav buttons -->
        <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/profile.php" title="<?= __('nav.profile') ?>">
    <span class="nav-user-icon">👤</span>
    <span class="nav-user-label"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
</a>

<a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/user_dashboard.php" title="<?= __('nav.dashboard') ?>">
    <span class="nav-user-icon">📊</span>
    <span class="nav-user-label"><?= __('nav.dashboard') ?></span>
</a>

<a class="nav-user-btn" href="<?php echo $nav_base_url; ?>user/order_history.php" title="My Orders">
    <span class="nav-user-icon">📦</span>
    <span class="nav-user-label">Orders</span>
</a>
        <?php if ($nav_role === 'admin'): ?>
    <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/dashboard.php" title="<?= __('nav.admin') ?>">
        <span class="nav-user-icon">👨‍💼</span>
        <span class="nav-user-label">Admin</span>
    </a>

    <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/delivery_partner.php" title="Delivery Partner Panel">
        <span class="nav-user-icon">🚚</span>
        <span class="nav-user-label">Delivery</span>
    </a>
<?php endif; ?>

        <?php if ($nav_role === 'restaurant'): ?>
    <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>owner/dashboard.php" title="<?= __('nav.owner') ?>"
       style="background:rgba(255,79,0,0.08); border:1px solid rgba(255,79,0,0.2);">
        <span class="nav-user-icon">🍽️</span>
        <span class="nav-user-label"><?= __('nav.owner') ?></span>
    </a>
<?php endif; ?>
        <a class="nav-user-btn nav-logout-btn" href="<?php echo $nav_base_url; ?>auth/logout.php" title="<?= __('nav.logout') ?>">
            <span class="nav-user-icon">🚪</span>
            <span class="nav-user-label"><?= __('nav.logout') ?></span>
        </a>
    <?php else: ?>
        <a href="<?php echo $nav_base_url; ?>auth/login.php" class="btn-primary">
            <?= __('nav.login') ?>
        </a>
    <?php endif; ?>

    <a class="btn-primary" href="#menu"><?= __('btn.order_now') ?></a>

  </div>

  <button class="mobile-menu-btn" type="button" data-mobile-toggle>
    ☰
  </button>
</nav>

