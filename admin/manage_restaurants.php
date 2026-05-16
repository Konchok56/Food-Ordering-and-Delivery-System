<?php
session_start();
include('../core/db.php');

// Admin check
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user['role'] !== 'admin') { echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied!</h2>"; exit; }

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Get restaurant for editing
$editRest = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRest = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all restaurants
$restaurants = $pdo->query("SELECT * FROM restaurants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Food counts
$fcStmt = $pdo->query("SELECT restaurant_id, COUNT(*) as cnt FROM foods WHERE restaurant_id IS NOT NULL GROUP BY restaurant_id");
$foodCounts = [];
while ($row = $fcStmt->fetch(PDO::FETCH_ASSOC)) {
    $foodCounts[$row['restaurant_id']] = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>" <?= isRtlLang() ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants — SwiftBite Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --orange: #ff4f00; --orange-light: #ff7340; --yellow: #ffb830;
            --dark: #1a1004; --dark2: #2c1a07; --cream: #fff8f0; --cream2: #fff0dc;
            --text: #3d2600; --muted: #8b6a44; --white: #ffffff;
            --green: #34c759; --red: #ff3b30;
            --shadow: 0 8px 40px rgba(255, 79, 0, 0.10);
            --shadow-lg: 0 20px 60px rgba(255, 79, 0, 0.15);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--text); min-height: 100vh; }

        .admin-topbar {
            background: var(--dark); padding: 18px 40px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .admin-topbar .logo { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--orange); }
        .admin-topbar .logo span { color: #fff; }
        .admin-topbar .admin-tag {
            background: rgba(255,79,0,0.15); color: var(--orange);
            padding: 5px 14px; border-radius: 999px;
            font-size: 0.78rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
        }
        .topbar-links { display: flex; gap: 12px; align-items: center; }
        .topbar-links a {
            color: rgba(255,255,255,0.7); text-decoration: none;
            font-size: 0.9rem; font-weight: 500; padding: 8px 18px;
            border-radius: 10px; transition: all 0.2s;
        }
        .topbar-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }

        .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 32px 24px 60px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; gap: 16px; flex-wrap: wrap; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--dark); }
        .page-header h1 em { font-style: normal; color: var(--orange); }

        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 14px; font-weight: 700; font-size: 0.92rem; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
        .btn-orange { background: var(--orange); color: #fff; box-shadow: 0 6px 20px rgba(255,79,0,0.25); }
        .btn-orange:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,79,0,0.35); }
        .btn-outline { background: none; border: 2px solid var(--orange); color: var(--orange); }
        .btn-outline:hover { background: var(--orange); color: #fff; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-sm { padding: 8px 16px; font-size: 0.82rem; border-radius: 10px; }

        .alert { padding: 16px 22px; border-radius: 16px; margin-bottom: 24px; font-weight: 600; font-size: 0.92rem; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease; }
        .alert-success { background: rgba(52,199,89,0.12); color: #1a7a34; border: 1px solid rgba(52,199,89,0.2); }
        .alert-error { background: rgba(255,59,48,0.1); color: #cc2d25; border: 1px solid rgba(255,59,48,0.2); }

        .form-card { background: #fff; border-radius: 28px; padding: 36px; box-shadow: var(--shadow); margin-bottom: 32px; }
        .form-card h2 { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-bottom: 28px; display: flex; align-items: center; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 0.88rem; color: var(--dark); }
        .form-group input, .form-group textarea, .form-group select {
            padding: 12px 16px; border: 2px solid var(--cream2); border-radius: 14px;
            font-size: 0.95rem; color: var(--text); background: var(--cream);
            transition: border-color 0.2s; font-family: 'DM Sans', sans-serif;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--orange); background: #fff; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-actions { display: flex; gap: 12px; margin-top: 28px; justify-content: flex-end; }

        .upload-zone { border: 2px dashed var(--cream2); border-radius: 20px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.3s; background: var(--cream); position: relative; }
        .upload-zone:hover { border-color: var(--orange); background: rgba(255,79,0,0.04); }
        .upload-zone .upload-icon { font-size: 2.6rem; margin-bottom: 12px; }
        .upload-zone .upload-text { font-weight: 600; color: var(--dark); margin-bottom: 4px; }
        .upload-zone .upload-hint { font-size: 0.82rem; color: var(--muted); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-preview { margin-top: 16px; display: none; }
        .upload-preview img { width: 120px; height: 120px; object-fit: cover; border-radius: 16px; border: 3px solid var(--orange); }
        .current-image { margin-top: 12px; }
        .current-image img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 2px solid var(--cream2); }
        .current-image span { display: block; font-size: 0.78rem; color: var(--muted); margin-top: 4px; }

        .toggle-row { display: flex; align-items: center; gap: 12px; }
        .toggle { position: relative; width: 48px; height: 26px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; inset: 0; background: var(--cream2); border-radius: 999px; cursor: pointer; transition: 0.3s; }
        .toggle-slider::before { content: ''; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: 0.3s; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
        .toggle input:checked + .toggle-slider { background: var(--orange); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(22px); }

        /* Restaurant cards grid */
        .rest-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .rest-admin-card { background: #fff; border-radius: 24px; padding: 24px; box-shadow: var(--shadow); transition: all 0.2s; }
        .rest-admin-card:hover { transform: translateY(-2px); }
        .rest-admin-top { display: flex; gap: 14px; align-items: center; margin-bottom: 14px; }
        .rest-admin-emoji { width: 56px; height: 56px; border-radius: 16px; background: var(--cream2); display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0; overflow: hidden; }
        .rest-admin-emoji img { width: 100%; height: 100%; object-fit: cover; }
        .rest-admin-name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.05rem; color: var(--dark); }
        .rest-admin-cuisine { font-size: 0.82rem; color: var(--muted); margin-top: 2px; }
        .rest-admin-desc { font-size: 0.85rem; color: var(--muted); line-height: 1.6; margin-bottom: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .rest-admin-stats { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
        .rest-admin-stat { padding: 4px 12px; background: var(--cream); border-radius: 999px; font-size: 0.78rem; font-weight: 600; }
        .rest-admin-actions { display: flex; gap: 8px; }
        .action-btn { width: 36px; height: 36px; border-radius: 10px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; transition: all 0.2s; }
        .action-edit { background: rgba(255,184,48,0.15); color: #e6a200; }
        .action-edit:hover { background: var(--yellow); color: #fff; }
        .action-delete { background: rgba(255,59,48,0.1); color: var(--red); }
        .action-delete:hover { background: var(--red); color: #fff; }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state .empty-icon { font-size: 3.5rem; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Syne', sans-serif; color: var(--dark); margin-bottom: 8px; }
        .empty-state p { color: var(--muted); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 24px; padding: 36px; max-width: 400px; width: 90%; text-align: center; box-shadow: var(--shadow-lg); }
        .modal-box .modal-icon { font-size: 3rem; margin-bottom: 16px; }
        .modal-box h3 { font-family: 'Syne', sans-serif; margin-bottom: 8px; color: var(--dark); }
        .modal-box p { color: var(--muted); margin-bottom: 24px; font-size: 0.92rem; }
        .modal-actions { display: flex; gap: 12px; justify-content: center; }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .admin-topbar { padding: 14px 20px; }
            .admin-wrapper { padding: 20px 14px 40px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-card { padding: 20px; border-radius: 20px; }
            .rest-admin-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="logo">Swift<span>Bite</span></div>
            <span class="admin-tag">Admin Panel</span>
        </div>
        <div class="topbar-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="manage_foods.php">🍔 Menu</a>
            <a href="manage_restaurants.php" style="color:#fff; background:rgba(255,255,255,0.08);">🏪 Restaurants</a>
            <a href="../index.php">🏠 View Site</a>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="page-header">
            <h1>🏪 Manage <em>Restaurants</em></h1>
            <?php if (!$editRest): ?>
                <a href="#restForm" class="btn btn-orange" onclick="document.getElementById('restForm').scrollIntoView({behavior:'smooth'})">
                    ➕ Add New Restaurant
                </a>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="form-card" id="restForm">
            <h2><?php echo $editRest ? '✏️ Edit Restaurant' : '➕ Add New Restaurant'; ?></h2>
            <form action="../actions/save_restaurant.php" method="POST" enctype="multipart/form-data">
                <?php if ($editRest): ?>
                    <input type="hidden" name="restaurant_id" value="<?php echo (int)$editRest['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Restaurant Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Burger Palace"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="cuisine_type">Cuisine Type *</label>
                        <select id="cuisine_type" name="cuisine_type" required>
                            <?php
                            $cuisineOptions = ['Fast Food','Nepali','Italian','Chinese','Japanese','Healthy','Indian','Thai','Mexican','Korean','BBQ','Cafe','Bakery','Seafood','Mixed'];
                            foreach ($cuisineOptions as $c):
                            ?>
                                <option value="<?php echo $c; ?>" <?php echo ($editRest && $editRest['cuisine_type'] === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required placeholder="Describe the restaurant..."><?php echo $editRest ? htmlspecialchars($editRest['description']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <input type="text" id="address" name="address" required placeholder="e.g. Thamel, Kathmandu"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['address']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City *</label>
                        <select id="city" name="city" required>
                            <option value="Kathmandu" <?php echo ($editRest && $editRest['city'] === 'Kathmandu') ? 'selected' : ''; ?>>Kathmandu</option>
                            <option value="Lalitpur" <?php echo ($editRest && $editRest['city'] === 'Lalitpur') ? 'selected' : ''; ?>>Lalitpur</option>
                            <option value="Bhaktapur" <?php echo ($editRest && $editRest['city'] === 'Bhaktapur') ? 'selected' : ''; ?>>Bhaktapur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" placeholder="01-4567890"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating (0.0-5.0)</label>
                        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" placeholder="4.5"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['rating']) : '4.5'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="delivery_time">Delivery Time</label>
                        <input type="text" id="delivery_time" name="delivery_time" placeholder="25-35 min"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['delivery_time']) : '30-45 min'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="delivery_fee">Delivery Fee (Rs.)</label>
                        <input type="number" id="delivery_fee" name="delivery_fee" step="0.01" min="0" placeholder="50.00"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['delivery_fee']) : '50.00'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="min_order">Min Order (Rs.)</label>
                        <input type="number" id="min_order" name="min_order" step="0.01" min="0" placeholder="200.00"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['min_order']) : '200.00'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="logo_emoji">Logo Emoji *</label>
                        <input type="text" id="logo_emoji" name="logo_emoji" required placeholder="🍔"
                               value="<?php echo $editRest ? htmlspecialchars($editRest['logo_emoji']) : '🍴'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Options</label>
                        <div style="display:flex; gap:24px; padding-top:8px;">
                            <div class="toggle-row">
                                <label class="toggle">
                                    <input type="checkbox" name="is_featured" value="1"
                                        <?php echo (!$editRest || $editRest['is_featured']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span style="font-size:0.88rem; font-weight:600;">Featured</span>
                            </div>
                            <div class="toggle-row">
                                <label class="toggle">
                                    <input type="checkbox" name="is_open" value="1"
                                        <?php echo (!$editRest || $editRest['is_open']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span style="font-size:0.88rem; font-weight:600;">Open</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label>Cover Image</label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">Click or drag & drop an image</div>
                            <div class="upload-hint">JPG, PNG or WebP • Max 2MB</div>
                            <input type="file" name="restaurant_image" id="restImage" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                        <?php if ($editRest && !empty($editRest['image_path'])): ?>
                            <div class="current-image">
                                <span>Current image:</span>
                                <img src="../<?php echo htmlspecialchars($editRest['image_path']); ?>" alt="Current">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <?php if ($editRest): ?>
                        <a href="manage_restaurants.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-orange">
                        <?php echo $editRest ? '💾 Update Restaurant' : '➕ Add Restaurant'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Restaurant Listing -->
        <div style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
            <h2 style="font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; color:var(--dark);">📋 All Restaurants</h2>
            <span style="background:var(--cream2); padding:4px 14px; border-radius:999px; font-size:0.82rem; font-weight:700; color:var(--orange);"><?php echo count($restaurants); ?> restaurants</span>
        </div>

        <?php if (empty($restaurants)): ?>
            <div class="empty-state">
                <div class="empty-icon">🏪</div>
                <h3>No restaurants yet</h3>
                <p>Start by adding your first restaurant!</p>
            </div>
        <?php else: ?>
            <div class="rest-admin-grid">
                <?php foreach ($restaurants as $r): ?>
                    <div class="rest-admin-card">
                        <div class="rest-admin-top">
                            <div class="rest-admin-emoji">
                                <?php if (!empty($r['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($r['image_path']); ?>" alt="">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($r['logo_emoji']); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="rest-admin-name"><?php echo htmlspecialchars($r['name']); ?></div>
                                <div class="rest-admin-cuisine"><?php echo htmlspecialchars($r['cuisine_type']); ?> • <?php echo htmlspecialchars($r['city']); ?></div>
                            </div>
                        </div>
                        <div class="rest-admin-desc"><?php echo htmlspecialchars($r['description']); ?></div>
                        <div class="rest-admin-stats">
                            <span class="rest-admin-stat">⭐ <?php echo $r['rating']; ?></span>
                            <span class="rest-admin-stat">🕐 <?php echo $r['delivery_time']; ?></span>
                            <span class="rest-admin-stat">🍽️ <?php echo $foodCounts[$r['id']] ?? 0; ?> items</span>
                            <span class="rest-admin-stat" style="<?php echo $r['is_open'] ? 'color:var(--green);' : 'color:var(--red);'; ?>">
                                <?php echo $r['is_open'] ? '● Open' : '● Closed'; ?>
                            </span>
                        </div>
                        <div class="rest-admin-actions" style="flex-wrap:wrap;">
                            <a href="manage_foods.php?restaurant_id=<?php echo (int)$r['id']; ?>" class="action-btn" title="Manage Foods"
                               style="background:rgba(52,199,89,0.12);color:#1a7a34;width:auto;padding:0 12px;font-size:0.78rem;font-weight:700;gap:4px;">
                                🍽️ Foods (<?php echo $foodCounts[$r['id']] ?? 0; ?>)
                            </a>
                            <a href="manage_restaurants.php?edit=<?php echo (int)$r['id']; ?>#restForm" class="action-btn action-edit" title="Edit">✏️</a>
                            <button type="button" class="action-btn action-delete" title="Delete"
                                    onclick="confirmDelete(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars(addslashes($r['name'])); ?>')">
                                🗑️
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon">⚠️</div>
            <h3>Delete Restaurant?</h3>
            <p>Are you sure you want to delete <strong id="deleteName"></strong>? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-outline btn-sm" onclick="closeModal()">Cancel</button>
                <a id="deleteLink" href="#" class="btn btn-danger btn-sm">🗑️ Delete</a>
            </div>
        </div>
    </div>

    <script>
        // Image preview
        const fileInput = document.getElementById('restImage');
        const uploadPreview = document.getElementById('uploadPreview');
        const previewImg = document.getElementById('previewImg');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) { alert('Image must be less than 2MB!'); fileInput.value = ''; return; }
                const reader = new FileReader();
                reader.onload = (ev) => { previewImg.src = ev.target.result; uploadPreview.style.display = 'block'; };
                reader.readAsDataURL(file);
            }
        });

        // Delete modal
        function confirmDelete(id, name) {
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteLink').href = '../actions/delete_restaurant.php?id=' + id;
            document.getElementById('deleteModal').classList.add('active');
        }
        function closeModal() { document.getElementById('deleteModal').classList.remove('active'); }
        document.getElementById('deleteModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>

