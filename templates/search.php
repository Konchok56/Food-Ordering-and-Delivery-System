<?php
// Fetch categories for dropdown
if (!isset($pdo)) require_once __DIR__ . '/../core/db.php';
$searchCats = $pdo->query("SELECT DISTINCT category FROM foods ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="search-section">
  <form class="search-box" action="menu.php" method="GET" id="homeSearchForm">

    <!-- ac-wrap lets the dropdown anchor directly under the text input -->
    <div class="search-input-wrap ac-wrap">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
      <input type="text" name="keyword" id="homeSearchInput"
             placeholder="Search for food, restaurants…"
             autocomplete="off"
             data-autocomplete
             aria-label="Search food or restaurants"
             aria-autocomplete="list"
             aria-haspopup="listbox" />
    </div>

    <div class="search-divider"></div>

    <div class="search-cat">
      <select name="category">
        <option value="">All Categories</option>
        <?php foreach ($searchCats as $sc): ?>
          <option value="<?php echo htmlspecialchars($sc); ?>"><?php echo htmlspecialchars($sc); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="search-divider"></div>

    <div class="search-cat">
      <select name="city">
        <option value=""><i class="fa-solid fa-location-dot"></i> All Cities</option>
        <option value="Kathmandu"><i class="fa-solid fa-location-dot"></i> Kathmandu</option>
        <option value="Lalitpur"><i class="fa-solid fa-location-dot"></i> Lalitpur</option>
        <option value="Bhaktapur"><i class="fa-solid fa-location-dot"></i> Bhaktapur</option>
      </select>
    </div>

    <button class="search-btn" type="submit">Search</button>
  </form>
</section>

