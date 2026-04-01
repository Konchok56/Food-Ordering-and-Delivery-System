<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Fetch role if logged in
$nav_role = '';
if (isset($_SESSION['user_id'])) {
    $nav_db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';
    include_once($nav_db_path);
    $nav_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $nav_stmt->execute([$_SESSION['user_id']]);
    $nav_role = $nav_stmt->fetchColumn();
}

$nav_base_url = !file_exists('includes/db.php') ? '../' : '';
?>
<nav>
  <a class="logo" href="<?php echo $nav_base_url; ?>index.php">Swift<span>Bite</span></a>

  <ul class="nav-links">
    <li><a href="<?php echo $nav_base_url; ?>index.php">Home</a></li>
    <li><a href="<?php echo $nav_base_url; ?>restaurants.php">Restaurants</a></li>
    <li><a href="<?php echo $nav_base_url; ?>menu.php">Menu</a></li>
    <li><a href="#categories">Categories</a></li>
    <li><a href="#offers">Offers</a></li>
    <li><a href="<?php echo $nav_base_url; ?>cart.php">Cart</a></li>
  </ul>

  <div class="nav-right">
    
    <a class="btn-ghost" href="#support">Support</a>

    <?php if(isset($_SESSION['user_name'])): ?>
        <!-- Logged-in user nav buttons -->
        <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>profile.php" title="My Profile">
            <span class="nav-user-icon">👤</span>
            <span class="nav-user-label"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
        </a>
        <a class="nav-user-btn" href="<?php echo $nav_base_url; ?>order_history.php" title="My Orders">
            <span class="nav-user-icon">📦</span>
            <span class="nav-user-label">Orders</span>
        </a>
        <?php if ($nav_role === 'admin'): ?>
            <a class="nav-user-btn nav-admin-btn" href="<?php echo $nav_base_url; ?>admin/dashboard.php" title="Admin Panel">
                <span class="nav-user-icon">👨‍💼</span>
                <span class="nav-user-label">Admin</span>
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