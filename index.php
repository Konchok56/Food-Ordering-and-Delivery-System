<?php
require_once 'core/bootstrap.php';
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
  <?php include 'templates/navbar.php'; ?>

  <main>
    <?php include 'templates/hero.php'; ?>
    <?php include 'templates/search.php'; ?>
    <?php include 'templates/categories.php'; ?>
    <?php include 'templates/restaurants.php'; ?>
    <?php include 'templates/foods.php'; ?>
    <?php include 'templates/howitworks.php'; ?>
    <?php include 'templates/promo.php'; ?>
    <?php include 'templates/testimonials.php'; ?>
  </main>

  <?php include 'templates/footer.php'; ?>

  <?php include 'templates/floating_menu.php'; ?>

  <script src="assets/js/script.js"></script>
  <script src="assets/js/cart.js"></script>
  <script src="assets/js/search_autocomplete.js"></script>
</body>
</html>
