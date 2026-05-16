<?php
session_start();
include('../core/db.php');

// Admin check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user['role'] !== 'admin') {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied!</h2>";
    exit;
}

// Flash messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Get food for editing
$editFood = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editFood = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all restaurants for dropdown
$restaurants = $pdo->query("SELECT id, name FROM restaurants ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch filter
$filterRestId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;

// Fetch all foods (with restaurant name)
$foodQuery = "SELECT f.*, r.name AS restaurant_name FROM foods f
              LEFT JOIN restaurants r ON f.restaurant_id = r.id";
if ($filterRestId > 0) {
    $foodStmt = $pdo->prepare($foodQuery . " WHERE f.restaurant_id = ? ORDER BY f.id DESC");
    $foodStmt->execute([$filterRestId]);
} else {
    $foodStmt = $pdo->query($foodQuery . " ORDER BY f.id DESC");
}
$foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu — SwiftBite Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --orange: #ff4f00;
            --orange-light: #ff7340;
            --yellow: #ffb830;
            --dark: #1a1004;
            --dark2: #2c1a07;
            --cream: #fff8f0;
            --cream2: #fff0dc;
            --text: #3d2600;
            --muted: #8b6a44;
            --white: #ffffff;
            --green: #34c759;
            --red: #ff3b30;
            --shadow: 0 8px 40px rgba(255, 79, 0, 0.10);
            --shadow-lg: 0 20px 60px rgba(255, 79, 0, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Top Bar ── */
        .admin-topbar {
            background: var(--dark);
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-topbar .logo {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--orange);
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
        }
        .topbar-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }

        /* ── Layout ── */
        .admin-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px 60px;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }
        .page-header h1 em {
            font-style: normal;
            color: var(--orange);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-orange {
            background: var(--orange);
            color: #fff;
            box-shadow: 0 6px 20px rgba(255,79,0,0.25);
        }
        .btn-orange:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,79,0,0.35); }
        .btn-outline {
            background: none;
            border: 2px solid var(--orange);
            color: var(--orange);
        }
        .btn-outline:hover { background: var(--orange); color: #fff; }
        .btn-danger {
            background: var(--red);
            color: #fff;
        }
        .btn-danger:hover { opacity: 0.9; }
        .btn-sm { padding: 8px 16px; font-size: 0.82rem; border-radius: 10px; }

        /* ── Alert Messages ── */
        .alert {
            padding: 16px 22px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-weight: 600;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        .alert-success { background: rgba(52,199,89,0.12); color: #1a7a34; border: 1px solid rgba(52,199,89,0.2); }
        .alert-error { background: rgba(255,59,48,0.1); color: #cc2d25; border: 1px solid rgba(255,59,48,0.2); }

        /* ── Food Form ── */
        .form-card {
            background: #fff;
            border-radius: 28px;
            padding: 36px;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
            animation: fadeIn 0.4s ease;
        }
        .form-card h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--dark);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid var(--cream2);
            border-radius: 14px;
            font-size: 0.95rem;
            color: var(--text);
            background: var(--cream);
            transition: border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--orange);
            background: #fff;
        }
        .form-group textarea { resize: vertical; min-height: 80px; }

        /* ── Image Upload Zone ── */
        .upload-zone {
            border: 2px dashed var(--cream2);
            border-radius: 20px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--cream);
            position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--orange);
            background: rgba(255,79,0,0.04);
        }
        .upload-zone .upload-icon {
            font-size: 2.6rem;
            margin-bottom: 12px;
        }
        .upload-zone .upload-text {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }
        .upload-zone .upload-hint {
            font-size: 0.82rem;
            color: var(--muted);
        }
        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .upload-preview {
            margin-top: 16px;
            display: none;
        }
        .upload-preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 16px;
            border: 3px solid var(--orange);
        }
        .upload-preview .remove-img {
            display: inline-block;
            margin-top: 8px;
            color: var(--red);
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
        }
        .current-image {
            margin-top: 12px;
        }
        .current-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid var(--cream2);
        }
        .current-image span {
            display: block;
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            justify-content: flex-end;
        }

        /* ── Toggle Switch ── */
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toggle {
            position: relative;
            width: 48px;
            height: 26px;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: var(--cream2);
            border-radius: 999px;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .toggle input:checked + .toggle-slider { background: var(--orange); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(22px); }

        /* ── Food Table ── */
        .table-card {
            background: #fff;
            border-radius: 28px;
            padding: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .table-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
        }
        .item-count {
            background: var(--cream2);
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--orange);
        }
        .food-table {
            width: 100%;
            border-collapse: collapse;
        }
        .food-table thead th {
            text-align: left;
            padding: 14px 16px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            border-bottom: 2px solid var(--cream2);
        }
        .food-table tbody tr {
            transition: background 0.2s;
        }
        .food-table tbody tr:hover {
            background: rgba(255,79,0,0.03);
        }
        .food-table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--cream2);
        }
        .food-table .food-cell {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .food-thumb {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            overflow: hidden;
            background: linear-gradient(135deg, #fff6ea, rgba(255,79,0,0.12));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .food-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .food-thumb .emoji {
            font-size: 1.8rem;
        }
        .food-cell-info .food-cell-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
        }
        .food-cell-info .food-cell-cat {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 2px;
        }
        .badge-cell {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .badge-hot { background: rgba(255,79,0,0.1); color: var(--orange); }
        .badge-new { background: rgba(52,199,89,0.12); color: var(--green); }
        .badge-none { color: var(--muted); font-size: 0.8rem; }
        .rating-cell { color: var(--yellow); font-weight: 600; }
        .price-cell {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            color: var(--dark);
        }
        .actions-cell {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .action-edit { background: rgba(255,184,48,0.15); color: #e6a200; }
        .action-edit:hover { background: var(--yellow); color: #fff; }
        .action-delete { background: rgba(255,59,48,0.1); color: var(--red); }
        .action-delete:hover { background: var(--red); color: #fff; }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state .empty-icon { font-size: 3.5rem; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Syne', sans-serif; color: var(--dark); margin-bottom: 8px; }
        .empty-state p { color: var(--muted); }

        /* ── Animations ── */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .admin-topbar { padding: 14px 20px; }
            .admin-wrapper { padding: 20px 14px 40px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-card, .table-card { padding: 20px; border-radius: 20px; }
            .page-header h1 { font-size: 1.5rem; }
            .food-table { font-size: 0.85rem; }
            .food-table thead { display: none; }
            .food-table tbody tr {
                display: block;
                padding: 16px;
                margin-bottom: 12px;
                border: 1px solid var(--cream2);
                border-radius: 16px;
            }
            .food-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: none;
            }
            .food-table td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: 0.78rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: var(--muted);
            }
        }

        /* Delete confirmation modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 24px;
            padding: 36px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.3s ease;
        }
        .modal-box .modal-icon { font-size: 3rem; margin-bottom: 16px; }
        .modal-box h3 { font-family: 'Syne', sans-serif; margin-bottom: 8px; color: var(--dark); }
        .modal-box p { color: var(--muted); margin-bottom: 24px; font-size: 0.92rem; }
        .modal-actions { display: flex; gap: 12px; justify-content: center; }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="logo">Swift<span>Bite</span></div>
            <span class="admin-tag">Admin Panel</span>
        </div>
        <div class="topbar-links">
            <a href="dashboard.php"><i class="fa-solid fa-chart-bar"></i> Dashboard</a>
            <a href="manage_foods.php" style="color:#fff; background:rgba(255,255,255,0.08);"><i class="fa-solid fa-burger"></i> Menu</a>
            <a href="manage_restaurants.php">🏪 Restaurants</a>
            <a href="../index.php"><i class="fa-solid fa-house"></i> View Site</a>
        </div>
    </div>

    <div class="admin-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa-solid fa-utensils"></i> Manage <em>Menu</em></h1>
            <?php if (!$editFood): ?>
                <a href="#foodForm" class="btn btn-orange" onclick="document.getElementById('foodForm').scrollIntoView({behavior:'smooth'})">
                    ➕ Add New Food
                </a>
            <?php endif; ?>
        </div>

        <!-- Flash Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add / Edit Form -->
        <div class="form-card" id="foodForm">
            <h2>
                <?php echo $editFood ? '✏️ Edit Food Item' : '➕ Add New Food Item'; ?>
            </h2>

            <form action="../actions/save_food.php" method="POST" enctype="multipart/form-data">
                <?php if ($editFood): ?>
                    <input type="hidden" name="food_id" value="<?php echo (int)$editFood['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Food Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Classic Smash Burger"
                               value="<?php echo $editFood ? htmlspecialchars($editFood['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <input type="text" id="category" name="category" required placeholder="e.g. Burgers, Pizza, Drinks"
                               value="<?php echo $editFood ? htmlspecialchars($editFood['category']) : ''; ?>">
                    </div>

                    <div class="form-group full">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required placeholder="Describe this delicious dish..."><?php echo $editFood ? htmlspecialchars($editFood['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (Rs.) *</label>
                        <input type="number" id="price" name="price" required step="0.01" min="0" placeholder="499.00"
                               value="<?php echo $editFood ? htmlspecialchars($editFood['price']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="delivery_time">Delivery Time *</label>
                        <input type="text" id="delivery_time" name="delivery_time" required placeholder="e.g. 25-35 min"
                               value="<?php echo $editFood ? htmlspecialchars($editFood['delivery_time']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating (0.0 - 5.0)</label>
                        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" placeholder="4.5"
                               value="<?php echo $editFood ? htmlspecialchars($editFood['rating']) : '4.5'; ?>">
                    </div>

                    <div class="form-group">
                        <label for="badge">Badge (optional)</label>
                        <select id="badge" name="badge">
                            <option value="">No Badge</option>
                            <option value="Hot" <?php echo ($editFood && $editFood['badge'] === 'Hot') ? 'selected' : ''; ?>><i class="fa-solid fa-fire" style="color:#ef4444"></i> Hot</option>
                            <option value="New" <?php echo ($editFood && $editFood['badge'] === 'New') ? 'selected' : ''; ?>>🆕 New</option>
                            <option value="Popular" <?php echo ($editFood && $editFood['badge'] === 'Popular') ? 'selected' : ''; ?>><i class="fa-solid fa-star" style="color:#f59e0b"></i> Popular</option>
                            <option value="Sale" <?php echo ($editFood && $editFood['badge'] === 'Sale') ? 'selected' : ''; ?>><i class="fa-solid fa-coins"></i> Sale</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="emoji">Emoji (fallback) *</label>
                        <input type="text" id="emoji" name="emoji" required placeholder="<i class="fa-solid fa-burger"></i>"
                               value="<?php echo $editFood ? htmlspecialchars($editFood['emoji']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="restaurant_id">Restaurant (optional)</label>
                        <select id="restaurant_id" name="restaurant_id">
                            <option value="">— No restaurant —</option>
                            <?php foreach ($restaurants as $rest): ?>
                                <option value="<?php echo (int)$rest['id']; ?>"
                                    <?php echo ($editFood && (int)$editFood['restaurant_id'] === (int)$rest['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rest['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Options</label>
                        <div style="display:flex; gap:24px; padding-top:8px;">
                            <div class="toggle-row">
                                <label class="toggle">
                                    <input type="checkbox" name="is_featured" value="1"
                                        <?php echo (!$editFood || $editFood['is_featured']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span style="font-size:0.88rem; font-weight:600;">Featured</span>
                            </div>
                            <div class="toggle-row">
                                <label class="toggle">
                                    <input type="checkbox" name="is_favorite" value="1"
                                        <?php echo ($editFood && $editFood['is_favorite']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span style="font-size:0.88rem; font-weight:600;">Favorite</span>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="form-group full">
                        <label>Food Image</label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">Click or drag & drop an image</div>
                            <div class="upload-hint">JPG, PNG or WebP • Max 2MB</div>
                            <input type="file" name="food_image" id="foodImage" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImg" src="" alt="Preview">
                            <div class="remove-img" onclick="removePreview()">✕ Remove</div>
                        </div>
                        <?php if ($editFood && !empty($editFood['image_path'])): ?>
                            <div class="current-image">
                                <span>Current image:</span>
                                <img src="../<?php echo htmlspecialchars($editFood['image_path']); ?>" alt="Current">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <?php if ($editFood): ?>
                        <a href="manage_foods.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-orange">
                        <?php echo $editFood ? '💾 Update Food' : '➕ Add Food'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Food Listing Table -->
        <div class="table-card">
            <div class="table-header">
                <h2>📋 All Menu Items</h2>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <!-- Filter by restaurant -->
                    <form method="GET" style="display:flex;gap:8px;align-items:center;">
                        <select name="restaurant_id" onchange="this.form.submit()"
                            style="padding:8px 14px;border:2px solid var(--cream2);border-radius:12px;font-size:0.82rem;font-weight:600;color:var(--text);background:var(--cream);font-family:'DM Sans',sans-serif;cursor:pointer;">
                            <option value="">All Restaurants</option>
                            <?php foreach ($restaurants as $rest): ?>
                                <option value="<?php echo (int)$rest['id']; ?>"
                                    <?php echo ($filterRestId === (int)$rest['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rest['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <span class="item-count"><?php echo count($foods); ?> items</span>
                </div>
            </div>

            <?php if (empty($foods)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
                    <h3>No food items yet</h3>
                    <p>Start by adding your first delicious dish!</p>
                </div>
            <?php else: ?>
                <table class="food-table">
                    <thead>
                        <tr>
                            <th>Food</th>
                            <th>Restaurant</th>
                            <th>Price</th>
                            <th>Rating</th>
                            <th>Badge</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($foods as $food): ?>
                            <tr>
                                <td data-label="Food">
                                    <div class="food-cell">
                                        <div class="food-thumb">
                                            <?php if (!empty($food['image_path'])): ?>
                                                <img src="../<?php echo htmlspecialchars($food['image_path']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>">
                                            <?php else: ?>
                                                <span class="emoji"><?php echo htmlspecialchars($food['emoji']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="food-cell-info">
                                            <div class="food-cell-name"><?php echo htmlspecialchars($food['name']); ?></div>
                                            <div class="food-cell-cat"><?php echo htmlspecialchars($food['category']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Restaurant">
                                    <?php if (!empty($food['restaurant_name'])): ?>
                                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:rgba(255,79,0,0.08);border-radius:999px;font-size:0.78rem;font-weight:700;color:var(--orange);">
                                            🏪 <?php echo htmlspecialchars($food['restaurant_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--muted);font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Price" class="price-cell">Rs. <?php echo number_format((float)$food['price'], 2); ?></td>
                                <td data-label="Rating" class="rating-cell"><i class="fa-solid fa-star" style="color:#f59e0b"></i> <?php echo htmlspecialchars($food['rating']); ?></td>
                                <td data-label="Badge">
                                    <?php if (!empty($food['badge'])): ?>
                                        <span class="badge-cell <?php echo $food['badge'] === 'New' ? 'badge-new' : 'badge-hot'; ?>">
                                            <?php echo htmlspecialchars($food['badge']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-none">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Time">🕐 <?php echo htmlspecialchars($food['delivery_time']); ?></td>
                                <td data-label="Actions">
                                    <div class="actions-cell">
                                        <a href="manage_foods.php?edit=<?php echo (int)$food['id']; ?>#foodForm" class="action-btn action-edit" title="Edit">✏️</a>
                                        <button type="button" class="action-btn action-delete" title="Delete"
                                                onclick="confirmDelete(<?php echo (int)$food['id']; ?>, '<?php echo htmlspecialchars(addslashes($food['name'])); ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i></div>
            <h3>Delete Food Item?</h3>
            <p>Are you sure you want to delete <strong id="deleteFoodName"></strong>? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-outline btn-sm" onclick="closeModal()">Cancel</button>
                <a id="deleteLink" href="#" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete</a>
            </div>
        </div>
    </div>

    <script>
        // Image upload preview
        const fileInput = document.getElementById('foodImage');
        const uploadZone = document.getElementById('uploadZone');
        const uploadPreview = document.getElementById('uploadPreview');
        const previewImg = document.getElementById('previewImg');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image must be less than 2MB!');
                    fileInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    uploadPreview.style.display = 'block';
                    uploadZone.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag & drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        uploadZone.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        function removePreview() {
            fileInput.value = '';
            uploadPreview.style.display = 'none';
            uploadZone.style.display = '';
        }

        // Delete modal
        function confirmDelete(id, name) {
            document.getElementById('deleteFoodName').textContent = name;
            document.getElementById('deleteLink').href = '../actions/delete_food.php?id=' + id;
            document.getElementById('deleteModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(function(el) {
            setTimeout(function() { el.style.opacity = '0'; el.style.transform = 'translateY(-10px)'; el.style.transition = 'all 0.3s'; }, 4000);
        });
    </script>
</body>
</html>

