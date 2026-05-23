<?php
require_once __DIR__ . '/../core/db.php';

// Fetch featured restaurants
$stmt = $pdo->query('SELECT * FROM restaurants WHERE is_featured = 1 ORDER BY rating DESC LIMIT 4');
$featuredRests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Food counts per restaurant
$fcStmt = $pdo->query("SELECT restaurant_id, COUNT(*) as cnt FROM foods WHERE restaurant_id IS NOT NULL GROUP BY restaurant_id");
$foodCounts = [];
while ($row = $fcStmt->fetch(PDO::FETCH_ASSOC)) {
    $foodCounts[$row['restaurant_id']] = $row['cnt'];
}

$cuisineEmojis = [
    'Fast Food' => '<i class="fa-solid fa-burger"></i>', 'Nepali' => '<i class="fa-solid fa-bowl-food"></i>', 'Italian' => '<i class="fa-solid fa-pizza-slice"></i>', 'Chinese' => '<i class="fa-solid fa-bowl-rice"></i>',
    'Japanese' => '<i class="fa-solid fa-fish"></i>', 'Healthy' => '<i class="fa-solid fa-leaf"></i>', 'Indian' => '<i class="fa-solid fa-bowl-food"></i>', 'Thai' => '<i class="fa-solid fa-bowl-food"></i>',
    'Mexican' => '<i class="fa-solid fa-bowl-food"></i>', 'Korean' => '<i class="fa-solid fa-bowl-food"></i>', 'BBQ' => '<i class="fa-solid fa-drumstick-bite"></i>', 'Cafe' => '<i class="fa-solid fa-mug-hot"></i>',
    'Bakery' => '<i class="fa-solid fa-cake-candles"></i>', 'Seafood' => '<i class="fa-solid fa-fish"></i>', 'Mixed' => '<i class="fa-solid fa-utensils"></i>',
];

if (!empty($featuredRests)):
?>
<section class="section" id="restaurants">
  <div class="section-header">
    <div>
      <div class="section-tag"><?php echo __('popular_restaurants', 'Popular Restaurants'); ?></div>
      <div class="section-title"><?php echo __('top_rated_near_you', 'Top Rated<br />Near You'); ?></div>
    </div>
    <a href="restaurants.php" class="view-all"><?php echo __('view_all', 'View All'); ?> <i class="fa-solid fa-arrow-right"></i></a>
  </div>

  <div class="rest-home-grid">
    <?php foreach ($featuredRests as $rest): ?>
      <a href="restaurant.php?id=<?php echo (int) $rest['id']; ?>" class="rest-card-link">
        <div class="rest-home-card">
          <div class="rest-home-cover">
            <?php if (!empty($rest['image_path']) && file_exists(PROJECT_ROOT . $rest['image_path'])): ?>
              <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($rest['image_path']); ?>" alt="<?php echo htmlspecialchars($rest['name']); ?>">
            <?php elseif (!empty($rest['image_path'])): ?>
              <img src="https://placehold.co/400x250/1a0a00/ff4f00?text=<?php echo urlencode($rest['name']); ?>" alt="<?php echo htmlspecialchars($rest['name']); ?>">
            <?php else: ?>
              <span class="rest-home-emoji"><?php echo htmlspecialchars($rest['logo_emoji']); ?></span>
            <?php endif; ?>
            <span class="rest-home-status <?php echo $rest['is_open'] ? 'open' : 'closed'; ?>">
              <?php echo $rest['is_open'] ? '● ' . __('status_open', 'Open') : '● ' . __('status_closed', 'Closed'); ?>
            </span>
          </div>
          <div class="rest-home-body">
            <div class="rest-home-top">
              <div>
                <div class="rest-home-name"><?php echo htmlspecialchars(__($rest['name'], $rest['name'])); ?></div>
                <div class="rest-home-cuisine">
                  <?php echo $cuisineEmojis[$rest['cuisine_type']] ?? '<i class="fa-solid fa-utensils"></i>'; ?>
                  <?php echo htmlspecialchars(__($rest['cuisine_type'], $rest['cuisine_type'])); ?>
                </div>
              </div>
              <div class="rest-home-rating"><i class="fa-solid fa-star" style="color:#f59e0b"></i> <?php echo t_num($rest['rating']); ?></div>
            </div>
            <div class="rest-home-meta">
              <span>🕐 <?php echo t_delivery_time($rest['delivery_time']); ?></span>
              <span><i class="fa-solid fa-truck"></i> <?php echo __('currency_rs', 'Rs.'); ?> <?php echo t_num(number_format((float) $rest['delivery_fee'], 0)); ?></span>
              <?php if (isset($foodCounts[$rest['id']])): ?>
                <span><i class="fa-solid fa-utensils"></i> <?php echo t_num($foodCounts[$rest['id']]) . ' ' . __('items', 'items'); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

