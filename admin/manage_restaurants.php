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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants — SwiftBite Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

        /* ── Top Bar ── */
        .admin-topbar {
            background: var(--dark);
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .admin-topbar .logo {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--orange);
            text-decoration: none;
        }
        .admin-topbar .logo span { color: #fff; }
        .admin-topbar .admin-tag {
            background: rgba(255,79,0,0.15);
            color: var(--orange);
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .topbar-links { display: flex; gap: 12px; align-items: center; }
        .topbar-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 10px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .topbar-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
        .topbar-links a.active { color: #fff; background: rgba(255,255,255,0.12); }

        /* ── Layout ── */
        .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 40px 24px 60px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; gap: 16px; flex-wrap: wrap; }
        .page-header-left { display: flex; flex-direction: column; gap: 4px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 2.4rem; font-weight: 800; color: var(--dark); }
        .page-header h1 em { font-style: normal; color: var(--orange); }

        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 14px; font-weight: 700; font-size: 0.92rem; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
        .btn-orange { background: var(--orange); color: #fff; box-shadow: 0 6px 20px rgba(255,79,0,0.25); }
        .btn-orange:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,79,0,0.35); }
        .btn-outline { background: none; border: 2px solid var(--orange); color: var(--orange); }
        .btn-outline:hover { background: var(--orange); color: #fff; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-sm { padding: 8px 16px; font-size: 0.82rem; border-radius: 10px; }

        .alert { padding: 16px 22px; border-radius: 16px; margin-bottom: 24px; font-weight: 600; font-size: 0.92rem; display: flex; align-items: center; gap: 10px; }
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
        .upload-zone .upload-icon { font-size: 2.6rem; margin-bottom: 12px; color: var(--orange); }
        .upload-zone .upload-text { font-weight: 600; color: var(--dark); margin-bottom: 4px; }
        .upload-zone .upload-hint { font-size: 0.82rem; color: var(--muted); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-preview { margin-top: 16px; display: none; }
        .upload-preview img { width: 120px; height: 120px; object-fit: cover; border-radius: 16px; border: 3px solid var(--orange); }

        .rest-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
        .rest-admin-card { background: #fff; border-radius: 28px; overflow: hidden; box-shadow: var(--shadow); transition: all 0.3s ease; border: 1px solid rgba(0,0,0,0.03); }
        .rest-admin-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .rest-admin-banner { width: 100%; height: 180px; position: relative; overflow: hidden; background: var(--cream2); }
        .rest-admin-banner img { width: 100%; height: 100%; object-fit: cover; }
        .rest-admin-badge { position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px); padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; color: var(--dark); text-transform: uppercase; }
        
        .rest-admin-body { padding: 24px; }
        .rest-admin-name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.25rem; color: var(--dark); margin-bottom: 4px; }
        .rest-admin-cuisine { font-size: 0.88rem; color: var(--orange); font-weight: 700; margin-bottom: 12px; }
        .rest-admin-desc { font-size: 0.9rem; color: var(--muted); line-height: 1.6; margin-bottom: 20px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        .rest-admin-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid var(--cream2); }
        .rest-admin-actions { display: flex; gap: 10px; }
        .action-btn { width: 36px; height: 36px; border-radius: 10px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; }
        .action-edit { background: rgba(255,184,48,0.15); color: #e6a200; }
        .action-edit:hover { background: var(--yellow); color: #fff; }
        .action-delete { background: rgba(255,59,48,0.1); color: var(--red); }
        .action-delete:hover { background: var(--red); color: #fff; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 24px; padding: 36px; max-width: 400px; width: 90%; text-align: center; }

        @media (max-width: 768px) {
            .admin-topbar { padding: 14px 20px; }
            .admin-wrapper { padding: 20px 14px 40px; }
            .form-grid { grid-template-columns: 1fr; }
            .rest-admin-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="dashboard.php" class="logo">Swift<span>Bite</span></a>
            <span class="admin-tag">Admin Panel</span>
        </div>
        <div class="topbar-links">
            <a href="dashboard.php"><i class="fa-solid fa-chart-bar"></i> Dashboard</a>
            <a href="manage_foods.php"><i class="fa-solid fa-burger"></i> Menu</a>
            <a href="manage_restaurants.php" class="active">🏪 Restaurants</a>
            <a href="../index.php"><i class="fa-solid fa-house"></i> View Site</a>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Manage <em>Restaurants</em></h1>
            </div>
            <?php if (!$editRest): ?>
                <a href="#restForm" class="btn btn-orange" onclick="document.getElementById('restForm').scrollIntoView({behavior:'smooth'})">
                    <i class="fa-solid fa-plus"></i> Add New Restaurant
                </a>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="form-card" id="restForm">
            <h2><?php echo $editRest ? '<i class="fa-solid fa-pen-to-square"></i> Edit Restaurant' : '<i class="fa-solid fa-plus-circle"></i> Add New Restaurant'; ?></h2>
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
                    <div class="form-group full">
                        <label for="address">Location Address *</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="address" name="address" required placeholder="e.g. Thamel, Kathmandu"
                                   value="<?php echo $editRest ? htmlspecialchars($editRest['address']) : ''; ?>" style="flex:1;">
                            <button type="button" onclick="openAdminMap()" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-map-location-dot"></i> Pick on Map
                            </button>
                        </div>
                        <input type="hidden" id="adminLatInput" name="latitude" value="<?php echo $editRest ? $editRest['latitude'] : ''; ?>">
                        <input type="hidden" id="adminLngInput" name="longitude" value="<?php echo $editRest ? $editRest['longitude'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City *</label>
                        <select id="city" name="city" required>
                            <?php $cities = ['Kathmandu','Lalitpur','Bhaktapur','Pokhara','Chitwan','Butwal','Dharan','Biratnagar'];
                            foreach($cities as $ct): ?>
                                <option value="<?php echo $ct; ?>" <?php echo ($editRest && $editRest['city']===$ct)?'selected':''; ?>><?php echo $ct; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" placeholder="01-4567890" value="<?php echo $editRest ? htmlspecialchars($editRest['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating (0.0 - 5.0)</label>
                        <input type="number" step="0.1" id="rating" name="rating" min="0" max="5" value="<?php echo $editRest ? $editRest['rating'] : '4.5'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="delivery_time">Delivery Time</label>
                        <input type="text" id="delivery_time" name="delivery_time" placeholder="30-45 min" value="<?php echo $editRest ? htmlspecialchars($editRest['delivery_time']) : '30-45 min'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="delivery_fee">Delivery Fee (Rs.)</label>
                        <input type="number" step="0.01" id="delivery_fee" name="delivery_fee" value="<?php echo $editRest ? $editRest['delivery_fee'] : '50.00'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="min_order">Min Order (Rs.)</label>
                        <input type="number" step="0.01" id="min_order" name="min_order" value="<?php echo $editRest ? $editRest['min_order'] : '200.00'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="logo_emoji">Logo Emoji *</label>
                        <input type="text" id="logo_emoji" name="logo_emoji" placeholder="e.g. 🍔" value="<?php echo $editRest ? htmlspecialchars($editRest['logo_emoji']) : '🏪'; ?>">
                    </div>
                    <div class="form-group full">
                        <label>Options</label>
                        <div style="display:flex; gap:20px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="is_featured" <?php echo ($editRest && $editRest['is_featured']) ? 'checked' : ''; ?>> Featured
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="is_open" <?php echo (!$editRest || $editRest['is_open']) ? 'checked' : ''; ?>> Open
                            </label>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label for="restImage">Cover Image</label>
                        <div class="upload-zone">
                            <i class="fa-solid fa-image upload-icon"></i>
                            <p class="upload-text">Click or drag & drop an image</p>
                            <p class="upload-hint">JPG, PNG or WebP • Max 2MB</p>
                            <input type="file" id="restImage" name="restaurant_image" accept="image/*">
                        </div>
                        <div id="uploadPreview" class="upload-preview">
                            <img id="previewImg" src="#" alt="Preview">
                        </div>
                        <?php if ($editRest && $editRest['image_path']): ?>
                            <div style="margin-top:12px;">
                                <p style="font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Current image:</p>
                                <img src="../<?php echo $editRest['image_path']; ?>" style="width:100px; height:100px; object-fit:cover; border-radius:12px; border:1px solid var(--cream2);">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <?php if ($editRest): ?>
                        <a href="manage_restaurants.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-orange">
                        <i class="fa-solid fa-save"></i> <?php echo $editRest ? 'Update Restaurant' : 'Add Restaurant'; ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="page-header" style="margin-top:60px;">
            <h1>📋 All <em>Restaurants</em></h1>
            <span style="font-weight:700; color:var(--orange); font-size:0.9rem;"><?php echo count($restaurants); ?> restaurants</span>
        </div>

        <?php if (empty($restaurants)): ?>
            <div class="form-card" style="text-align:center; padding:60px;">
                <i class="fa-solid fa-store-slash" style="font-size:3rem; color:var(--cream2); margin-bottom:20px;"></i>
                <h3>No restaurants found</h3>
                <p style="color:var(--muted);">Add your first restaurant using the form above.</p>
            </div>
        <?php else: ?>
            <div class="rest-admin-grid">
                <?php foreach ($restaurants as $r): ?>
                    <div class="rest-admin-card">
                        <div class="rest-admin-banner">
                            <?php if ($r['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($r['image_path']); ?>" alt="">
                            <?php else: ?>
                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:3.5rem; background:var(--cream2);"><?php echo htmlspecialchars($r['logo_emoji'] ?: '🏪'); ?></div>
                            <?php endif; ?>
                            <div class="rest-admin-badge"><?php echo htmlspecialchars($r['cuisine_type']); ?></div>
                        </div>
                        <div class="rest-admin-body">
                            <div class="rest-admin-name"><?php echo htmlspecialchars($r['name']); ?></div>
                            <div class="rest-admin-cuisine"><?php echo htmlspecialchars($r['cuisine_type']); ?> • <?php echo htmlspecialchars($r['city']); ?></div>
                            <p class="rest-admin-desc"><?php echo htmlspecialchars($r['description']); ?></p>
                            
                            <div class="rest-admin-footer">
                                <div class="rest-admin-actions" style="gap:8px;">
                                    <a href="manage_foods.php?restaurant_id=<?php echo (int)$r['id']; ?>" class="action-btn" title="Manage Menu" style="background:rgba(52,199,89,0.1); color:#1a7a34; width:auto; padding:0 12px; font-weight:700; font-size:0.8rem; gap:4px;">
                                        <i class="fa-solid fa-utensils"></i> Foods (<?php echo $foodCounts[$r['id']] ?? 0; ?>)
                                    </a>
                                    <a href="?edit=<?php echo $r['id']; ?>#restForm" class="action-btn action-edit" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $r['id']; ?>, '<?php echo addslashes($r['name']); ?>')" class="action-btn action-delete" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modals & Scripts -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div style="font-size:3.5rem; color:var(--red); margin-bottom:16px;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3>Delete Restaurant?</h3>
            <p>Are you sure you want to delete <strong id="deleteName"></strong>? This action cannot be undone.</p>
            <div style="display:flex; gap:12px; justify-content:center;">
                <button class="btn btn-outline btn-sm" onclick="closeModal()">Cancel</button>
                <a id="deleteLink" href="#" class="btn btn-danger btn-sm">Delete Now</a>
            </div>
        </div>
    </div>

    <div id="adminMapOverlay" class="modal-overlay">
      <div style="background:#fff;border-radius:24px;width:92%;max-width:720px;overflow:hidden;display:flex;flex-direction:column;max-height:90vh;">
        <div style="padding:18px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--cream2);">
          <strong><i class="fa-solid fa-map-location-dot" style="color:var(--orange);margin-right:8px;"></i>Set Location</strong>
          <button onclick="closeAdminMap()" style="width:34px;height:34px;border-radius:50%;border:none;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="adminLeafletMap" style="height:380px;width:100%;"></div>
        <div style="padding:14px 18px;display:flex;gap:10px;align-items:center;border-top:1px solid var(--cream2);">
          <div id="adminPickedAddr" style="flex:1;font-size:0.85rem;color:var(--text);background:var(--cream);padding:10px 14px;border-radius:12px;min-height:40px;">Click map to pick…</div>
          <button id="adminConfirmBtn" onclick="confirmAdminMap()" disabled class="btn btn-orange btn-sm">Confirm</button>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Image preview
        const fileInput = document.getElementById('restImage');
        const uploadPreview = document.getElementById('uploadPreview');
        const previewImg = document.getElementById('previewImg');
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
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

        // Map logic
        let adminMap, adminMarker;
        let adminPickedLat, adminPickedLng, adminPickedAddr;

        function openAdminMap() {
            document.getElementById('adminMapOverlay').style.display = 'flex';
            setTimeout(() => {
                if (!adminMap) {
                    adminMap = L.map('adminLeafletMap').setView([27.7172, 85.3240], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(adminMap);
                    adminMap.on('click', e => {
                        const {lat, lng} = e.latlng;
                        if (adminMarker) adminMarker.setLatLng(e.latlng);
                        else adminMarker = L.marker(e.latlng).addTo(adminMap);
                        adminPickedLat = lat; adminPickedLng = lng;
                        document.getElementById('adminPickedAddr').textContent = "Fetching address...";
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                            .then(r => r.json()).then(d => {
                                adminPickedAddr = d.display_name;
                                document.getElementById('adminPickedAddr').textContent = adminPickedAddr;
                                document.getElementById('adminConfirmBtn').disabled = false;
                            });
                    });
                }
                adminMap.invalidateSize();
            }, 100);
        }
        function closeAdminMap() { document.getElementById('adminMapOverlay').style.display = 'none'; }
        function confirmAdminMap() {
            document.getElementById('address').value = adminPickedAddr;
            document.getElementById('adminLatInput').value = adminPickedLat;
            document.getElementById('adminLngInput').value = adminPickedLng;
            closeAdminMap();
        }
    </script>
</body>
</html>
