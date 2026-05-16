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
      <div class="section-tag">Browse Categories</div>
      <div class="section-title">What Are You<br />Craving Today?</div>
    </div>
    <a href="menu.php" class="view-all">View All →</a>
  </div>

  <div class="categories-grid">
    <?php if (empty($db_categories)): ?>
      <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px 0;">No categories found.</p>
    <?php else: ?>
      <?php foreach ($db_categories as $index => $cat): ?>
        <button class="cat-card <?php echo $index === 0 ? 'active' : ''; ?>" type="button" onclick="location.href='menu.php?category=<?php echo urlencode($cat['category']); ?>'">
          <div class="cat-icon overflow-hidden">
            <?php if (!empty($cat['category_image'])): ?>
              <img src="<?php echo htmlspecialchars($cat['category_image']); ?>" alt="<?php echo htmlspecialchars($cat['category']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
            <?php else: ?>
              <?php echo htmlspecialchars($cat['emoji'] ?: '<i class="fa-solid fa-bowl-food"></i>'); ?>
            <?php endif; ?>
          </div>
          <div class="cat-name"><?php echo htmlspecialchars($cat['category']); ?></div>
          <div class="cat-count"><?php echo (int)$cat['count']; ?> items</div>
        </button>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
