<?php
// Fetch categories and their counts from the database, including a representative image
try {
    $sql = "SELECT 
                f.category, 
                MAX(f.emoji) as emoji, 
                (SELECT image_path FROM foods WHERE category = f.category AND image_path IS NOT NULL AND image_path != '' LIMIT 1) as category_image,
                COUNT(*) as count 
            FROM foods f 
            GROUP BY f.category 
            ORDER BY count DESC";
    $stmt = $pdo->query($sql);
    $db_categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_categories = [];
}
?>
<section class="section" id="categories">
  <div class="section-header">
    <div>
      <div class="section-tag"><?php echo __('browse_categories', 'Browse Categories'); ?></div>
      <div class="section-title"><?php echo __('craving_today', 'What Are You<br />Craving Today?'); ?></div>
    </div>
    <a href="menu.php" class="view-all"><?php echo __('view_all', 'View All'); ?> <i class="fa-solid fa-arrow-right"></i></a>
  </div>

  <div class="categories-grid">
    <?php if (empty($db_categories)): ?>
      <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px 0;"><?php echo __('no_categories_found', 'No categories found.'); ?></p>
    <?php else: ?>
      <?php foreach ($db_categories as $index => $cat): ?>
        <button class="cat-card <?php echo $index === 0 ? 'active' : ''; ?>" type="button" onclick="location.href='menu.php?category=<?php echo urlencode($cat['category']); ?>'">
          <div class="cat-icon overflow-hidden">
            <?php if (!empty($cat['category_image']) && file_exists(PROJECT_ROOT . $cat['category_image'])): ?>
              <img src="<?php echo SITE_BASE_URL . '/' . htmlspecialchars($cat['category_image']); ?>" alt="<?php echo htmlspecialchars($cat['category']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
            <?php elseif (!empty($cat['category_image'])): ?>
              <img src="https://placehold.co/150x150/1a0a00/ff4f00?text=<?php echo urlencode($cat['category']); ?>" alt="<?php echo htmlspecialchars($cat['category']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
            <?php else: ?>
              <?php echo htmlspecialchars($cat['emoji'] ?: '<i class="fa-solid fa-bowl-food"></i>'); ?>
            <?php endif; ?>
          </div>
          <div class="cat-name"><?php echo htmlspecialchars(__($cat['category'], $cat['category'])); ?></div>
          <div class="cat-count"><?php echo t_num($cat['count']) . ' ' . __('items', 'items'); ?></div>
        </button>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
