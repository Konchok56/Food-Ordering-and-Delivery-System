<?php
session_start();
include('../core/db.php');

// Admin only
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') { die("<h2 style='color:red;text-align:center;margin-top:60px'>Access Denied</h2>"); }

// Handle approve / reject action
if (isset($_GET['action'], $_GET['user_id'])) {
    $uid    = (int)$_GET['user_id'];
    $action = $_GET['action'];
    if ($action === 'approve') {
        $pdo->prepare("UPDATE users SET is_approved = 1 WHERE id = ? AND role = 'restaurant'")->execute([$uid]);
        // Also make the restaurant visible
        $pdo->prepare("UPDATE restaurants SET is_open = 1 WHERE owner_id = ?")->execute([$uid]);
        $msg = ['type' => 'success', 'text' => '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Restaurant approved and is now live!'];
    } elseif ($action === 'reject') {
        // Delete the linked restaurant row and user
        $pdo->prepare("DELETE FROM restaurants WHERE owner_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'restaurant'")->execute([$uid]);
        $msg = ['type' => 'danger', 'text' => '<i class="fa-solid fa-trash"></i> Restaurant registration rejected and removed.'];
    }
}

// Fetch pending restaurant accounts
$pending = $pdo->query("
    SELECT u.id, u.name, u.email, u.created_at,
           r.id AS rest_id, r.name AS rest_name, r.cuisine_type, r.city, r.phone
    FROM users u
    LEFT JOIN restaurants r ON r.owner_id = u.id
    WHERE u.role = 'restaurant' AND u.is_approved = 0
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved restaurant accounts
$approved = $pdo->query("
    SELECT u.id, u.name, u.email,
           r.id AS rest_id, r.name AS rest_name, r.cuisine_type, r.city, r.is_open
    FROM users u
    LEFT JOIN restaurants r ON r.owner_id = u.id
    WHERE u.role = 'restaurant' AND u.is_approved = 1
    ORDER BY u.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Approvals — SwiftBite Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --orange: #ff4f00; --dark: #1a1004; --cream: #fff8f0; --cream2: #fff0dc;
            --text: #3d2600; --muted: #8b6a44; --green: #34c759; --red: #ff3b30;
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
        .admin-tag { background: rgba(255,79,0,0.15); color: var(--orange); padding: 5px 14px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; }
        .topbar-links a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem; font-weight: 500; padding: 8px 18px; border-radius: 10px; transition: all 0.2s; }
        .topbar-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
        .topbar-links { display: flex; gap: 6px; }

        .wrapper { max-width: 1100px; margin: 0 auto; padding: 36px 24px 60px; }
        h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; margin-bottom: 8px; }
        h1 em { font-style: normal; color: var(--orange); }
        .subtitle { color: var(--muted); margin-bottom: 32px; font-size: 0.93rem; }

        .alert { padding: 14px 20px; border-radius: 14px; margin-bottom: 24px; font-weight: 600; font-size: 0.92rem; }
        .alert-success { background: rgba(52,199,89,0.1); color: #1a7a34; border: 1px solid rgba(52,199,89,0.2); }
        .alert-danger  { background: rgba(255,59,48,0.08); color: #c0392b; border: 1px solid rgba(255,59,48,0.15); }

        .section-title {
            font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 800;
            color: var(--dark); margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }
        .badge { padding: 3px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
        .badge-pending { background: rgba(255,184,48,0.2); color: #a06200; }
        .badge-approved { background: rgba(52,199,89,0.15); color: #1a7a34; }

        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-bottom: 40px; }

        .rest-card {
            background: #fff; border-radius: 20px; padding: 24px;
            box-shadow: 0 4px 24px rgba(255,79,0,0.07);
            border: 1px solid var(--cream2);
        }
        .rest-card-header { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
        .rest-avatar {
            width: 52px; height: 52px; border-radius: 14px;
            background: var(--cream2); display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; flex-shrink: 0;
        }
        .rest-name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1rem; }
        .rest-meta { font-size: 0.8rem; color: var(--muted); margin-top: 3px; line-height: 1.6; }

        .owner-row { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; padding: 10px 14px; background: var(--cream); border-radius: 12px; }
        .owner-row .owner-label { font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .owner-row .owner-val { font-size: 0.88rem; font-weight: 600; }

        .card-actions { display: flex; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-approve { background: rgba(52,199,89,0.12); color: #1a7a34; }
        .btn-approve:hover { background: var(--green); color: #fff; }
        .btn-reject  { background: rgba(255,59,48,0.08); color: var(--red); }
        .btn-reject:hover  { background: var(--red); color: #fff; }

        .empty-state { padding: 40px; text-align: center; background: #fff; border-radius: 20px; border: 1px dashed var(--cream2); }
        .empty-state .emoji { font-size: 2.5rem; margin-bottom: 10px; }
        .empty-state p { color: var(--muted); font-size: 0.92rem; }

        .approved-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(255,79,0,0.07); }
        .approved-table th { padding: 14px 18px; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--cream2); background: var(--cream); }
        .approved-table td { padding: 14px 18px; font-size: 0.88rem; border-bottom: 1px solid var(--cream2); }
        .approved-table tr:last-child td { border-bottom: none; }
        .status-dot { display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; font-weight: 600; }
        .status-dot::before { content:''; width:7px; height:7px; border-radius:50%; background: currentColor; }
        .open   { color: var(--green); }
        .closed { color: var(--red); }
    </style>
</head>
<body>

<div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="logo">Swift<span>Bite</span></div>
        <span class="admin-tag">Admin Panel</span>
    </div>
    <div class="topbar-links">
        <a href="dashboard.php"><i class="fa-solid fa-chart-bar"></i> Dashboard</a>
        <a href="manage_restaurants.php">🏪 Restaurants</a>
        <a href="manage_foods.php"><i class="fa-solid fa-burger"></i> Menu</a>
        <a href="pending_restaurants.php" style="color:#fff;background:rgba(255,255,255,0.08);"><i class="fa-solid fa-bell"></i> Approvals</a>
        <a href="../index.php"><i class="fa-solid fa-house"></i> View Site</a>
    </div>
</div>

<div class="wrapper">
    <h1><i class="fa-solid fa-bell"></i> Restaurant <em>Approvals</em></h1>
    <p class="subtitle">Review new restaurant registrations before they go live.</p>

    <?php if (isset($msg)): ?>
        <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo $msg['text']; ?></div>
    <?php endif; ?>

    <!-- Pending -->
    <div class="section-title">
        <i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i> Pending Approval
        <span class="badge badge-pending"><?php echo count($pending); ?> pending</span>
    </div>

    <?php if (empty($pending)): ?>
        <div class="empty-state" style="margin-bottom:36px;">
            <div class="emoji"><i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i></div>
            <p>No pending restaurant registrations. You're all caught up!</p>
        </div>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($pending as $p): ?>
                <div class="rest-card">
                    <div class="rest-card-header">
                        <div class="rest-avatar">🏪</div>
                        <div>
                            <div class="rest-name"><?php echo htmlspecialchars($p['rest_name'] ?? 'Unnamed Restaurant'); ?></div>
                            <div class="rest-meta">
                                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($p['city'] ?? '—'); ?> &nbsp;•&nbsp;
                                <i class="fa-solid fa-utensils"></i> <?php echo htmlspecialchars($p['cuisine_type'] ?? '—'); ?><br>
                                <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($p['phone'] ?? '—'); ?><br>
                                🕐 Registered: <?php echo date('M d, Y', strtotime($p['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="owner-row">
                        <div>
                            <div class="owner-label">Owner</div>
                            <div class="owner-val"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div style="font-size:0.8rem;color:var(--muted)"><?php echo htmlspecialchars($p['email']); ?></div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="?action=approve&user_id=<?php echo (int)$p['id']; ?>" class="btn btn-approve"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Approve</a>
                        <a href="?action=reject&user_id=<?php echo (int)$p['id']; ?>"
                           class="btn btn-reject"
                           onclick="return confirm('Reject and delete this registration?')">✗ Reject</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Approved -->
    <div class="section-title">
        <i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Approved Restaurants
        <span class="badge badge-approved"><?php echo count($approved); ?> active</span>
    </div>

    <?php if (empty($approved)): ?>
        <div class="empty-state">
            <div class="emoji">🏪</div>
            <p>No approved restaurant owners yet.</p>
        </div>
    <?php else: ?>
        <table class="approved-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Owner</th>
                    <th>City</th>
                    <th>Cuisine</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approved as $a): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($a['rest_name'] ?? '—'); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($a['name']); ?><br>
                            <span style="font-size:0.78rem;color:var(--muted)"><?php echo htmlspecialchars($a['email']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($a['city'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($a['cuisine_type'] ?? '—'); ?></td>
                        <td>
                            <span class="status-dot <?php echo $a['is_open'] ? 'open' : 'closed'; ?>">
                                <?php echo $a['is_open'] ? 'Open' : 'Closed'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>

