<?php
// templates/recommended_foods.php
// Requires $recommended_foods to be set before inclusion
if (!empty($recommended_foods)):
?>
<section class="section" id="recommended" style="padding-top: 0; margin-top: 32px;">
  <div class="section-header">
    <div>
      <div class="section-tag">✨ Recommended For You</div>
      <div class="section-title">Based on your<br />past orders</div>
    </div>
  </div>

  <div class="foods-grid">
    <?php foreach ($recommended_foods as $food): ?>
      <a href="../food_detail.php?id=<?php echo (int) $food['id']; ?>" class="food-card-link">
      <article class="food-card reveal-on-scroll">
        <div class="food-img">
          <?php if (!empty($food['image_path'])): ?>
            <!-- Ensure correct path relative to the caller (user_dashboard.php is in user/, index.php is in root) -->
            <?php 
            $basePath = (strpos($_SERVER['SCRIPT_NAME'], '/user/') !== false) ? '../' : '';
            ?>
            <img src="<?php echo htmlspecialchars($basePath . $food['image_path']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="food-photo">
          <?php else: ?>
            <?php echo htmlspecialchars($food['emoji']); ?>
          <?php endif; ?>
          <?php if (!empty($food['badge'])): ?>
            <span class="food-badge<?php echo $food['badge'] === 'New' ? ' new' : ''; ?>">
              <?php echo htmlspecialchars($food['badge']); ?>
            </span>
          <?php endif; ?>
          <div class="food-fav"><?php echo $food['is_favorite'] ? '❤️' : '🤍'; ?></div>
        </div>

        <div class="food-info">
          <div class="food-meta">
            <span class="food-category"><?php echo htmlspecialchars($food['category']); ?></span>
            <span class="food-rating">⭐ <?php echo htmlspecialchars($food['rating']); ?></span>
          </div>

          <div class="food-name"><?php echo htmlspecialchars($food['name']); ?></div>
          <div class="food-desc"><?php echo htmlspecialchars($food['description']); ?></div>

          <div class="food-footer">
            <div>
              <div class="food-time">🕐 <?php echo htmlspecialchars($food['delivery_time']); ?></div>
              <div class="food-price">Rs. <?php echo number_format((float) $food['price'], 2); ?></div>
            </div>
            <?php $baseActionPath = (strpos($_SERVER['SCRIPT_NAME'], '/user/') !== false) ? '../actions/' : 'actions/'; ?>
            <form action="<?php echo $baseActionPath; ?>add_to_cart.php" method="post" onclick="event.stopPropagation();">
              <input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>" />
              <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>" />
              <input type="hidden" name="price" value="<?php echo (float) $food['price']; ?>" />
              <button class="add-btn" type="submit" aria-label="Add <?php echo htmlspecialchars($food['name']); ?> to cart" data-name="<?php echo htmlspecialchars($food['name']); ?>">+</button>
            </form>
          </div>
        </div>
      </article>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
