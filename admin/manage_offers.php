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

// Handle Delete/Toggle
if (isset($_GET['delete'])) {
    $offerId = (int)$_GET['delete'];
    
    // Optional: Delete old image from disk
    $oldStmt = $pdo->prepare("SELECT image_path FROM offers WHERE id = ?");
    $oldStmt->execute([$offerId]);
    $oldImage = $oldStmt->fetchColumn();
    if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
        unlink(__DIR__ . '/../' . $oldImage);
    }

    $pdo->prepare("DELETE FROM offers WHERE id = ?")->execute([$offerId]);
    header("Location: manage_offers.php?success=Offer deleted successfully");
    exit;
}

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE offers SET is_active = NOT is_active WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header("Location: manage_offers.php?success=Offer status updated");
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['offer_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $promo_code = strtoupper(trim($_POST['promo_code'] ?? ''));
    $type = $_POST['type'] ?? 'percent';
    $value = (float)($_POST['value'] ?? 0);
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || empty($description)) {
        header("Location: manage_offers.php?error=Title and Description are required");
        exit;
    }

    // Handle image upload
    $image_path = null;
    $uploadDir = __DIR__ . '/../uploads/offers/';
    if (isset($_FILES['offer_image']) && $_FILES['offer_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['offer_image'];

        // Validate size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            header("Location: manage_offers.php?error=Image must be less than 2MB");
            exit;
        }

        // Validate file type
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            header("Location: manage_offers.php?error=Only JPG, PNG and WebP images are allowed");
            exit;
        }

        // Generate unique filename
        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg'
        };
        $filename = 'offer_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadDir . $filename;

        // Create dir if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path = 'uploads/offers/' . $filename;

            // If editing, delete old image
            if ($id > 0) {
                $oldStmt = $pdo->prepare("SELECT image_path FROM offers WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldImage = $oldStmt->fetchColumn();
                if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                    unlink(__DIR__ . '/../' . $oldImage);
                }
            }
        } else {
            header("Location: manage_offers.php?error=Failed to upload image. Please try again.");
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        // Manage Promo Code inside this Offer
        if (!empty($promo_code)) {
            // Check if promo code exists
            $promoStmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ?");
            $promoStmt->execute([$promo_code]);
            $existingPromo = $promoStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingPromo) {
                // Update existing promo code
                $updatePromo = $pdo->prepare("UPDATE promo_codes SET type = ?, value = ?, expiry_date = ?, is_active = ? WHERE code = ?");
                $updatePromo->execute([$type, $value, $expiry_date, $is_active, $promo_code]);
            } else {
                // Create new promo code
                $insertPromo = $pdo->prepare("INSERT INTO promo_codes (code, type, value, expiry_date, is_active) VALUES (?, ?, ?, ?, ?)");
                $insertPromo->execute([$promo_code, $type, $value, $expiry_date, $is_active]);
            }
        } else {
            $promo_code = null;
        }

        if ($id > 0) {
            // Update Offer
            if ($image_path) {
                $stmt = $pdo->prepare("UPDATE offers SET title = ?, description = ?, promo_code = ?, image_path = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $description, $promo_code, $image_path, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE offers SET title = ?, description = ?, promo_code = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $description, $promo_code, $is_active, $id]);
            }
            $msg = "Offer updated successfully";
        } else {
            // Insert Offer
            $stmt = $pdo->prepare("INSERT INTO offers (title, description, promo_code, image_path, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $promo_code, $image_path, $is_active]);
            $msg = "Offer added successfully";
        }

        $pdo->commit();
        header("Location: manage_offers.php?success=" . urlencode($msg));
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: manage_offers.php?error=Failed to save offer. Details: " . urlencode($e->getMessage()));
        exit;
    }
}

// Get offer for editing
$editOffer = null;
$linkedPromo = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editOffer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editOffer && !empty($editOffer['promo_code'])) {
        $promoStmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ?");
        $promoStmt->execute([$editOffer['promo_code']]);
        $linkedPromo = $promoStmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Fetch all offers
$offers = $pdo->query("SELECT * FROM offers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offers — SwiftBite Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --orange: #ff4f00;
            --orange-light: #ff7340;
            --yellow: #ffb830;
            --dark: #1a1004;
            --cream: #fff8f0;
            --cream2: #fff0dc;
            --text: #3d2600;
            --muted: #8b6a44;
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

        .topbar-links {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .topbar-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .topbar-links a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }

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
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-orange {
            background: var(--orange);
            color: #fff;
            box-shadow: 0 6px 20px rgba(255, 79, 0, 0.25);
        }

        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 79, 0, 0.35);
        }

        .btn-outline {
            background: none;
            border: 2px solid var(--orange);
            color: var(--orange);
        }

        .btn-outline:hover {
            background: var(--orange);
            color: #fff;
        }

        .alert {
            padding: 16px 22px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-weight: 600;
            font-size: 0.92rem;
        }

        .alert-success {
            background: rgba(52, 199, 89, 0.12);
            color: #1a7a34;
        }

        .alert-error {
            background: rgba(255, 59, 48, 0.1);
            color: #cc2d25;
        }

        .form-card,
        .table-card {
            background: #fff;
            border-radius: 28px;
            padding: 36px;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }

        .form-card h2,
        .table-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 28px;
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

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.88rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid var(--cream2);
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--orange);
        }

        /* File Upload */
        .upload-zone {
            border: 2px dashed var(--cream2);
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--cream);
            position: relative;
        }
        .upload-zone:hover { border-color: var(--orange); }
        .upload-zone input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .upload-icon { font-size: 2rem; margin-bottom: 8px; }
        .upload-text { font-size: 0.88rem; font-weight: 700; color: var(--text); }
        .upload-hint { font-size: 0.78rem; color: var(--muted); margin-top: 4px; }
        
        .upload-preview {
            display: none;
            margin-top: 12px;
            position: relative;
            max-width: 200px;
        }
        .upload-preview img { width: 100%; border-radius: 12px; }
        .remove-img {
            position: absolute;
            top: -8px; right: -8px;
            background: var(--red);
            color: #fff;
            border-radius: 50%;
            width: 24px; height: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: bold; cursor: pointer;
        }

        .current-image {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .current-image img { width: 120px; border-radius: 12px; border: 2px solid var(--cream2); }

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

        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .toggle input:checked+.toggle-slider {
            background: var(--orange);
        }

        .toggle input:checked+.toggle-slider::before {
            transform: translateX(22px);
        }

        .offer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .offer-table th {
            text-align: left;
            padding: 14px 16px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 2px solid var(--cream2);
        }

        .offer-table td {
            padding: 16px;
            border-bottom: 1px solid var(--cream2);
            vertical-align: middle;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            text-decoration: none;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-active {
            background: rgba(52, 199, 89, 0.12);
            color: #1a7a34;
        }

        .badge-inactive {
            background: rgba(255, 59, 48, 0.1);
            color: #cc2d25;
        }

        .promo-pill {
            background: rgba(255,79,0,0.08);
            border: 1px dashed rgba(255,79,0,0.4);
            color: var(--orange);
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .offer-thumb {
            width: 80px;
            height: 50px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--orange), var(--yellow));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
            object-fit: cover;
            overflow: hidden;
        }
        .offer-thumb img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="logo">Swift<span>Bite</span></div>
        </div>
        <div class="topbar-links">
            <a href="dashboard.php"><i class="fa-solid fa-chart-bar"></i> Dashboard</a>
            <a href="manage_foods.php"><i class="fa-solid fa-burger"></i> Menu</a>
            <a href="manage_restaurants.php">🏪 Restaurants</a>
            <a href="manage_promos.php">💸 Promos</a>
            <a href="manage_offers.php" style="color:#fff; background:rgba(255,255,255,0.08);">🎁 Offers</a>
            <a href="../index.php"><i class="fa-solid fa-house"></i> View Site</a>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="page-header">
            <h1>🎁 Manage <em>Offers</em></h1>
            <?php if (!$editOffer): ?>
                <a href="#offerForm" class="btn btn-orange"
                    onclick="document.getElementById('offerForm').scrollIntoView({behavior:'smooth'})">➕ Add New Offer</a>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-card" id="offerForm">
            <h2><?php echo $editOffer ? '✏️ Edit Offer' : '➕ Add Marketing Offer'; ?></h2>
            <form action="manage_offers.php" method="POST" enctype="multipart/form-data">
                <?php if ($editOffer): ?>
                    <input type="hidden" name="offer_id" value="<?php echo (int)$editOffer['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Offer Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Get 40% Off Your First Order"
                            value="<?php echo $editOffer ? htmlspecialchars($editOffer['title']) : ''; ?>">
                    </div>

                    <div class="form-group full">
                        <label>Offer Description *</label>
                        <textarea name="description" required placeholder="e.g. Use code SWIFT40 at checkout. New users only."><?php echo $editOffer ? htmlspecialchars($editOffer['description']) : ''; ?></textarea>
                    </div>

                    <!-- Associated Promo Code (Inline Creation) -->
                    <div class="form-group">
                        <label>Associated Promo Code (Optional)</label>
                        <input type="text" name="promo_code" placeholder="e.g. SWIFT40"
                            value="<?php echo $editOffer ? htmlspecialchars($editOffer['promo_code'] ?? '') : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="type">
                            <option value="percent" <?php echo ($linkedPromo && $linkedPromo['type'] === 'percent') ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="flat" <?php echo ($linkedPromo && $linkedPromo['type'] === 'flat') ? 'selected' : ''; ?>>Flat Amount (Rs.)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Discount Value</label>
                        <input type="number" name="value" step="0.01" placeholder="e.g. 40 or 150"
                            value="<?php echo $linkedPromo ? htmlspecialchars($linkedPromo['value']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Promo Expiry Date</label>
                        <input type="datetime-local" name="expiry_date"
                            value="<?php echo $linkedPromo && $linkedPromo['expiry_date'] ? date('Y-m-d\TH:i', strtotime($linkedPromo['expiry_date'])) : ''; ?>">
                    </div>

                    <!-- Banner Image -->
                    <div class="form-group full">
                        <label>Banner Image (Optional)</label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-icon">🖼️</div>
                            <div class="upload-text">Click or drag & drop an offer banner</div>
                            <div class="upload-hint">JPG, PNG or WebP • Max 2MB</div>
                            <input type="file" name="offer_image" id="offerImage" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImg" src="" alt="Preview">
                            <div class="remove-img" onclick="removePreview()">✕</div>
                        </div>
                        <?php if ($editOffer && !empty($editOffer['image_path'])): ?>
                            <div class="current-image">
                                <span>Current banner:</span>
                                <?php if (file_exists(__DIR__ . '/../' . $editOffer['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($editOffer['image_path']); ?>" alt="Current">
                                <?php else: ?>
                                    <img src="https://placehold.co/120x60/1a0a00/ff4f00?text=Missing" alt="Missing" title="File missing on server: <?php echo htmlspecialchars($editOffer['image_path']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Options</label>
                        <div class="toggle-row" style="margin-top: 5px;">
                            <label class="toggle">
                                <input type="checkbox" name="is_active" value="1" <?php echo (!$editOffer || $editOffer['is_active']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span style="font-weight:600; font-size:0.9rem;">Active (Show on Offers Page)</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                    <?php if ($editOffer): ?>
                        <a href="manage_offers.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-orange">Save Offer Details</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <h2 style="margin-bottom: 20px;">🎁 Existing Marketing Offers</h2>
            <?php if (empty($offers)): ?>
                <p>No marketing offers found. Click "Add New Offer" to create one!</p>
            <?php else: ?>
                <table class="offer-table">
                    <thead>
                        <tr>
                            <th>Banner</th>
                            <th>Offer Title & Description</th>
                            <th>Promo Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offers as $off): 
                            // Fetch promo details if any
                            $pInfo = null;
                            if (!empty($off['promo_code'])) {
                                $ps = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ?");
                                $ps->execute([$off['promo_code']]);
                                $pInfo = $ps->fetch(PDO::FETCH_ASSOC);
                            }
                        ?>
                            <tr>
                                <td>
                                    <div class="offer-thumb">
                                        <?php if (!empty($off['image_path']) && file_exists(__DIR__ . '/../' . $off['image_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($off['image_path']); ?>" alt="Banner">
                                        <?php else: ?>
                                            🎁
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong style="font-size:1.1rem; color:var(--dark);"><?php echo htmlspecialchars($off['title']); ?></strong>
                                    <div style="color:var(--muted); font-size:0.88rem; margin-top:4px; max-width: 400px;"><?php echo htmlspecialchars($off['description']); ?></div>
                                </td>
                                <td>
                                    <?php if ($pInfo): ?>
                                        <span class="promo-pill"><?php echo htmlspecialchars($off['promo_code']); ?></span>
                                        <div style="font-size:0.75rem; color:var(--muted); margin-top:4px;">
                                            Discount: <?php echo $pInfo['type'] === 'flat' ? 'Rs. ' . $pInfo['value'] : $pInfo['value'] . '%'; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-style:italic;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($off['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="manage_offers.php?toggle=<?php echo $off['id']; ?>" class="action-btn"
                                        style="background:#eee; color:#333;" title="Toggle Status">⏯️</a>
                                    <a href="manage_offers.php?edit=<?php echo $off['id']; ?>" class="action-btn"
                                        style="background:rgba(255,184,48,0.2); color:#e6a200;" title="Edit">✏️</a>
                                    <a href="manage_offers.php?delete=<?php echo $off['id']; ?>" class="action-btn"
                                        style="background:rgba(255,59,48,0.1); color:var(--red);"
                                        onclick="return confirm('Delete this offer?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Image preview
        const fileInput = document.getElementById('offerImage');
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

        function removePreview() {
            fileInput.value = '';
            uploadPreview.style.display = 'none';
            uploadZone.style.display = '';
        }

        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(function(el) {
            setTimeout(function() { el.style.opacity = '0'; el.style.transform = 'translateY(-10px)'; el.style.transition = 'all 0.3s'; }, 4000);
        });
    </script>
</body>
</html>
