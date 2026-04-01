<?php
session_start();

// ✅ Remember me functionality - Check before session check
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    include('includes/db.php');
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token=?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    }
}

// Cart count from DB
include('includes/db.php');
include('includes/cart_helper.php');
$cartCount = isset($_SESSION['user_id']) ? getCartCount($pdo, $_SESSION['user_id']) : 0;
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
</head>
<body>
  <?php include 'sections/navbar.php'; ?>

  <main>
    <?php include 'sections/hero.php'; ?>
    <?php include 'sections/search.php'; ?>
    <?php include 'sections/categories.php'; ?>
    <?php include 'sections/restaurants.php'; ?>
    <?php include 'sections/foods.php'; ?>
    <?php include 'sections/howitworks.php'; ?>
    <?php include 'sections/promo.php'; ?>
    <?php include 'sections/testimonials.php'; ?>
  </main>

  <?php include 'sections/footer.php'; ?>

  <?php include 'sections/floating_menu.php'; ?>

  <script src="assets/js/script.js"></script>
  <script src="assets/js/cart.js"></script>
  <script src="assets/js/search_autocomplete.js"></script>
</body>
</html>