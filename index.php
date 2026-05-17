<?php
require_once 'core/bootstrap.php';
require_once 'core/recommendation_helper.php';

$recommended_foods = [];
if (isLoggedIn() && hasRole('user')) {
  $user_id = $_SESSION['user_id'] ?? 0;
  if ($user_id) {
    $recommended_foods = getRecommendations($pdo, $user_id, 4);
  }
}

// Riders and Restaurant owners should not access the main landing page
if (isLoggedIn()) {
  if (hasRole('delivery_partner')) redirect('delivery/dashboard.php');
  if (hasRole('restaurant')) redirect('owner/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SwiftBite — Food Delivery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css?v=2" />
</head>

<body>
  <?php include 'templates/navbar.php'; ?>

  <main>
    <?php include 'templates/hero.php'; ?>
    <?php include 'templates/search.php'; ?>
    <?php include 'templates/categories.php'; ?>
    <?php include 'templates/restaurants.php'; ?>
    <?php if (isLoggedIn() && !empty($recommended_foods)) {
      include 'templates/recommended_foods.php';
    } ?>
    <?php include 'templates/foods.php'; ?>
    <?php include 'templates/howitworks.php'; ?>
    <?php include 'templates/promo.php'; ?>
    <?php include 'templates/testimonials.php'; ?>
  </main>

  <?php include 'templates/footer.php'; ?>

  <?php include 'templates/floating_menu.php'; ?>
  <?php include 'templates/chatbot.php'; ?>

  <script src="assets/js/script.js"></script>
  <script src="assets/js/cart.js"></script>
  <script src="assets/js/search_autocomplete.js"></script>
</body>

</html>