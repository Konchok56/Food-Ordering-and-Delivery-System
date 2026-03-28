<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<nav>
  <a class="logo" href="index.php">Swift<span>Bite</span></a>

  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="restaurants.php">Restaurants</a></li>
    <li><a href="menu.php">Menu</a></li>
    <li><a href="#categories">Categories</a></li>
    <li><a href="#offers">Offers</a></li>
    <li><a href="cart.php">Cart</a></li>
  </ul>

  <div class="nav-right">
    
    <a class="btn-ghost" href="#support">Support</a>

    <!-- LOGIN / LOGOUT + ADMIN SYSTEM -->
    <?php if(isset($_SESSION['user_name'])): ?>
        
        <span class="welcome-text">
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </span>

        <!-- ADMIN LINK - Only for Admins -->
        <?php
        if (isset($_SESSION['user_id'])) {
            include_once('includes/db.php');   // Change to '../includes/db.php' if error occurs

            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $role = $stmt->fetchColumn();

            if ($role === 'admin') {
                echo '<a href="admin/dashboard.php" class="btn-ghost">👨‍💼 Admin</a>';
            }
        }
        ?>

        <a href="/food/swiftbite_php_starter/auth/logout.php" class="btn-primary">
            Logout
        </a>

    <?php else: ?>

        <a href="/food/swiftbite_php_starter/auth/login.php" class="btn-primary">
            Login
        </a>

    <?php endif; ?>

    <a class="btn-primary" href="#menu">Order Now</a>

  </div>

  <button class="mobile-menu-btn" type="button">
    ☰
  </button>
</nav>