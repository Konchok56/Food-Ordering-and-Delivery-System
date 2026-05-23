<?php
// templates/recommended_foods.php
// Requires $recommended_foods to be set before inclusion
if (!empty($recommended_foods)):
  ?>
  <section class="section" id="recommended" style="padding-top: 0; margin-top: 32px;">
    <div class="section-header">
      <div>
        <div class="section-tag" style="background-color: var(--accent); color: white;"><i class="fa-solid fa-wand-magic-sparkles" style="color:#f59e0b"></i> <?php echo __('recommended_for_you', 'Recommended For You'); ?></div>
        <div class="section-title">Based on Your Past Orders</div>
      </div>
    </div>

    <div class="foods-grid">
        <?php foreach ($recommended_foods as $food): ?>
        <a href="food_detail.php?id=<?php echo (int) $food['id']; ?>" class="food-card-link">
          <article class="food-card reveal-on-scroll" style="border: 1px solid var(--accent);">
            <div class="food-img">
              <?php if (!empty($food['image_path']) && file_exists(PROJECT_ROOT . $food['image_path'])): ?>
                <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($food['image_path']); ?>" alt="<?php echo htmlspecialchars(__($food['name'])); ?>" class="food-photo">
              <?php elseif (!empty($food['image_path'])): ?>
                <img src="https://placehold.co/400x300/1a0a00/ff4f00?text=<?php echo urlencode($food['name']); ?>" alt="<?php echo htmlspecialchars(__($food['name'])); ?>" class="food-photo">
              <?php else: ?>
                <?php echo htmlspecialchars($food['emoji']); ?>
              <?php endif; ?>
              <span class="food-badge new"><?php echo __('recommended_for_you', 'Recommended'); ?></span>
              <div class="food-fav"><?php echo $food['is_favorite'] ? '<i class="fa-solid fa-heart" style="color:#ef4444"></i>' : '<i class="fa-regular fa-heart"></i>'; ?></div>
            </div>

            <div class="food-info">
              <div class="food-meta">
                <span class="food-category"><?php echo htmlspecialchars(__($food['category'])); ?></span>
                <span class="food-rating"><i class="fa-solid fa-star" style="color:#f59e0b"></i> <?php echo htmlspecialchars(t_num($food['rating'])); ?></span>
              </div>

              <div class="food-name"><?php echo htmlspecialchars(__($food['name'])); ?></div>
              <div class="food-desc"><?php echo htmlspecialchars(__($food['description'])); ?></div>

              <div class="food-footer">
                <div>
                  <div class="food-time">🕐 <?php echo htmlspecialchars(t_delivery_time($food['delivery_time'])); ?></div>
                  <div class="food-price"><?php echo __('currency_rs', 'Rs.'); ?> <?php echo htmlspecialchars(t_num(number_format((float) $food['price'], 2))); ?></div>
                </div>

                <form action="actions/add_to_cart.php" method="post" onclick="event.stopPropagation();">
                  <input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>" />
                  <input type="hidden" name="food_name" value="<?php echo htmlspecialchars(__($food['name'])); ?>" />
                  <input type="hidden" name="price" value="<?php echo (float) $food['price']; ?>" />
                  <button class="add-btn" type="submit"
                    aria-label="Add <?php echo htmlspecialchars(__($food['name'])); ?> to cart"
                    data-name="<?php echo htmlspecialchars(__($food['name'])); ?>">+</button>
                </form>
              </div>
            </div>
          </article>
        </a>
        <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>