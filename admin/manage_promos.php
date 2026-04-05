<?php
session_start();
include('../includes/db.php');

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
    $pdo->prepare("DELETE FROM promo_codes WHERE id = ?")->execute([(int) $_GET['delete']]);
    header("Location: manage_promos.php?success=Promo code deleted");
    exit;
}
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE promo_codes SET is_active = NOT is_active WHERE id = ?")->execute([(int) $_GET['toggle']]);
    header("Location: manage_promos.php?success=Promo code status updated");
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['promo_id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['type'] ?? 'flat';
    $value = (float) ($_POST['value'] ?? 0);

    // Formatting datetime-local
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($code) || $value <= 0) {
        header("Location: manage_promos.php?error=Invalid input data");
        exit;
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE promo_codes SET code=?, type=?, value=?, expiry_date=?, is_active=? WHERE id=?");
            $stmt->execute([$code, $type, $value, $expiry_date, $is_active, $id]);
            header("Location: manage_promos.php?success=Promo code updated");
        } else {
            $stmt = $pdo->prepare("INSERT INTO promo_codes (code, type, value, expiry_date, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $expiry_date, $is_active]);
            header("Location: manage_promos.php?success=Promo code added");
        }
        exit;
    } catch (Exception $e) {
        header("Location: manage_promos.php?error=Failed to save. Ensure code is unique.");
        exit;
    }
}

// Get promo for editing
$editPromo = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editPromo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all promos
$promos = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promos — SwiftBite Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
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
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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

        .admin-topbar .logo span {
            color: #fff;
        }

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

        .form-group label {
            font-weight: 600;
            font-size: 0.88rem;
        }

        .form-group input,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid var(--cream2);
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--orange);
        }

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

        .promo-table {
            width: 100%;
            border-collapse: collapse;
        }

        .promo-table th {
            text-align: left;
            padding: 14px 16px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 2px solid var(--cream2);
        }

        .promo-table td {
            padding: 16px;
            border-bottom: 1px solid var(--cream2);
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
    </style>
</head>

<body>
    <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="logo">Swift<span>Bite</span></div>
        </div>
        <div class="topbar-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="manage_foods.php">🍔 Menu</a>
            <a href="manage_restaurants.php">🏪 Restaurants</a>
            <a href="manage_promos.php" style="color:#fff; background:rgba(255,255,255,0.08);">💸 Promos</a>
            <a href="../index.php">🏠 View Site</a>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="page-header">
            <h1>💸 Manage <em>Promos</em></h1>
            <?php if (!$editPromo): ?>
                <a href="#promoForm" class="btn btn-orange"
                    onclick="document.getElementById('promoForm').scrollIntoView({behavior:'smooth'})">➕ Add New Promo</a>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <!-- Form -->
        <div class="form-card" id="promoForm">
            <h2><?php echo $editPromo ? '✏️ Edit Promo' : '➕ Add Promo Code'; ?></h2>
            <form action="manage_promos.php" method="POST">
                <?php if ($editPromo): ?>
                    <input type="hidden" name="promo_id" value="<?php echo (int) $editPromo['id']; ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Promo Code *</label>
                        <input type="text" name="code" required placeholder="e.g. WELCOME50"
                            value="<?php echo $editPromo ? htmlspecialchars($editPromo['code']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Discount Type *</label>
                        <select name="type" required>
                            <option value="flat" <?php echo ($editPromo && $editPromo['type'] === 'flat') ? 'selected' : ''; ?>>Flat Amount (Rs.)</option>
                            <option value="percent" <?php echo ($editPromo && $editPromo['type'] === 'percent') ? 'selected' : ''; ?>>Percentage (%)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Value *</label>
                        <input type="number" name="value" step="0.01" required placeholder="e.g. 50 or 15"
                            value="<?php echo $editPromo ? htmlspecialchars($editPromo['value']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date (Optional)</label>
                        <input type="datetime-local" name="expiry_date"
                            value="<?php echo $editPromo && $editPromo['expiry_date'] ? date('Y-m-d\TH:i', strtotime($editPromo['expiry_date'])) : ''; ?>">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Options</label>
                        <div class="toggle-row" style="margin-top: 5px;">
                            <label class="toggle">
                                <input type="checkbox" name="is_active" value="1" <?php echo (!$editPromo || $editPromo['is_active']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span style="font-weight:600; font-size:0.9rem;">Active (Customers can use this)</span>
                        </div>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                    <?php if ($editPromo): ?>
                        <a href="manage_promos.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-orange">Save Promo Code</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <h2 style="margin-bottom: 20px;">🎟️ Existing Promos</h2>
            <?php if (empty($promos)): ?>
                <p>No promo codes found.</p>
            <?php else: ?>
                <table class="promo-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promos as $p): ?>
                            <tr>
                                <td><strong
                                        style="color:var(--orange); font-size:1.1rem;"><?php echo htmlspecialchars($p['code']); ?></strong>
                                </td>
                                <td><?php echo $p['type'] === 'flat' ? 'Flat Amount' : 'Percentage'; ?></td>
                                <td><?php echo $p['type'] === 'flat' ? 'Rs. ' . $p['value'] : $p['value'] . '%'; ?></td>
                                <td><?php echo $p['expiry_date'] ? date('M d, Y h:ia', strtotime($p['expiry_date'])) : '<span style="color:#aaa;">Never</span>'; ?>
                                </td>
                                <td>
                                        <?php if ($p['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                </td>
                                <td style="display:flex; gap:8px;">
                                    <a href="manage_promos.php?toggle=<?php echo $p['id']; ?>" class="action-btn"
                                        style="background:#eee; color:#333;" title="Toggle Status">⏯️</a>
                                    <a href="manage_promos.php?edit=<?php echo $p['id']; ?>" class="action-btn"
                                        style="background:rgba(255,184,48,0.2); color:#e6a200;">✏️</a>
                                    <a href="manage_promos.php?delete=<?php echo $p['id']; ?>" class="action-btn"
                                        style="background:rgba(255,59,48,0.1); color:var(--red);"
                                        onclick="return confirm('Delete this code?');">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>