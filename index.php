<?php
session_start();

// ✅ Remember me functionality - Check before session check
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    include('includes/db.php');
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token=?"); // Changed to remember_token
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    }
}

// Cart count calculation
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SwiftBite — Food Delivery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'sections/navbar.php'; ?>

  <main>
    <?php include 'sections/hero.php'; ?>
    <?php include 'sections/search.php'; ?>
    <?php include 'sections/categories.php'; ?>
    <?php include 'sections/foods.php'; ?>
    <?php include 'sections/howitworks.php'; ?>
    <?php include 'sections/promo.php'; ?>
    <?php include 'sections/testimonials.php'; ?>
  </main>

  <?php include 'sections/footer.php'; ?>

  <button class="cart-fab" type="button" aria-label="Open cart" onclick="window.location.href='cart.php'">
    🛒
    <span class="cart-count" id="cartCount"><?php echo $cartCount; ?></span>
  </button>

  <script src="assets/js/script.js"></script>
</body>
</html>