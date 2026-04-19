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

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Ban/Unban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($user_id > 0) {
        if ($action === 'ban') {
            $reason = trim($_POST['ban_reason'] ?? 'Violation of terms of service.');
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ? AND role != 'admin'");
            if ($stmt->execute([$reason, $user_id])) {
                // If banned successfully, we might want to kill their session but PHP sessions are server-side and cross-user session destruction is hard without DB session storage. The bootstrap checks it.
                header("Location: manage_users.php?success=User suspended successfully");
                exit;
            }
        } elseif ($action === 'unban') {
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                header("Location: manage_users.php?success=User unbanned successfully");
                exit;
            }
        } elseif ($action === 'toggle_status') {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                header("Location: manage_users.php?success=User status toggled successfully");
                exit;
            }
        }
    }
}

// Fetch users
$users = $pdo->query("SELECT id, name, email, phone, role, created_at, status, is_banned, ban_reason FROM users WHERE role != 'admin' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — SwiftBite Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --orange: #ff4f00; --dark: #1a1004; --cream: #fff8f0; --cream2: #fff0dc;
            --text: #3d2600; --muted: #8b6a44; --green: #34c759; --red: #ff3b30;
            --shadow: 0 8px 40px rgba(255, 79, 0, 0.10);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--text); min-height: 100vh; }
        
        .admin-topbar { background: var(--dark); padding: 18px 40px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .admin-topbar .logo { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--orange); }
        .admin-topbar .logo span { color: #fff; }
        .topbar-links { display: flex; gap: 12px; align-items: center; }
        .topbar-links a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem; font-weight: 500; padding: 8px 18px; border-radius: 10px; transition: all 0.2s; }
        .topbar-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
        
        .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 32px 24px 60px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--dark); }
        .page-header h1 em { font-style: normal; color: #f43f5e; }
        
        .alert { padding: 16px 22px; border-radius: 16px; margin-bottom: 24px; font-weight: 600; font-size: 0.92rem; }
        .alert-success { background: rgba(52, 199, 89, 0.12); color: #1a7a34; }
        .alert-error { background: rgba(255, 59, 48, 0.1); color: #cc2d25; }

        .table-card { background: #fff; border-radius: 28px; padding: 36px; box-shadow: var(--shadow); }
        .table-card h2 { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-bottom: 28px; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 14px 16px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--muted); border-bottom: 2px solid var(--cream2); }
        .data-table td { padding: 16px; border-bottom: 1px solid var(--cream2); vertical-align: middle; }
        
        .badge { padding: 4px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge-role { background: #e0e7ff; color: #3730A3; }
        .badge-active { background: rgba(52,199,89,0.15); color: #1a7a34; }
        .badge-inactive { background: #f3f4f6; color: #4B5563; }
        .badge-banned { background: rgba(255,59,48,0.15); color: #cc2d25; }
        
        .btn { border: none; border-radius: 10px; padding: 8px 16px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-red { background: rgba(255,59,48,0.1); color: var(--red); }
        .btn-red:hover { background: var(--red); color: #fff; }
        .btn-green { background: rgba(52,199,89,0.1); color: #1a7a34; }
        .btn-green:hover { background: #34c759; color: #fff; }

        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal { background: #fff; padding: 32px; border-radius: 24px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal h3 { font-family: 'Syne', sans-serif; font-size: 1.3rem; margin-bottom: 12px; }
        .modal textarea { width: 100%; padding: 12px; border: 2px solid var(--cream2); border-radius: 12px; margin-bottom: 16px; font-family: inherit; resize: vertical; min-height: 80px; }
        .modal textarea:focus { outline: none; border-color: var(--orange); }
        .modal-btns { display: flex; gap: 12px; justify-content: flex-end; }
        .modal-btns button { padding: 10px 20px; font-size: 0.9rem; }
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
            <a href="manage_users.php" style="color:#fff; background:rgba(255,255,255,0.08);">👥 Users</a>
            <a href="../index.php">🏠 View Site</a>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="page-header">
            <h1>👥 Manage <em>Users</em></h1>
        </div>

        <?php if ($success): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="table-card">
            <h2>System Users</h2>
            <?php if (empty($users)): ?>
                <p>No non-admin users found.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status (User Set)</th>
                            <th>Banned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--orange); font-size:1.05rem;"><?php echo htmlspecialchars($u['name']); ?></strong><br>
                                    <span style="font-size:0.85rem; color:var(--muted);"><?php echo htmlspecialchars($u['email']); ?></span>
                                </td>
                                <td><span class="badge badge-role"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                <td>
                                    <?php if(($u['status'] ?? 'active') === 'active'): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_banned']): ?>
                                        <span class="badge badge-banned">Yes</span>
                                        <div style="font-size:0.75rem; color:var(--red); margin-top:4px; max-width:150px; line-height:1.2;">
                                            Reason: <?php echo htmlspecialchars($u['ban_reason']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-size:0.85rem;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button class="btn" style="background:#eee; color:#333; margin-right:8px;" title="Toggle Status">⏯️ Status</button>
                                    </form>

                                    <?php if ($u['is_banned']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Unban this user?');">
                                            <input type="hidden" name="action" value="unban">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button class="btn btn-green">Unban</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-red" onclick="showBanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>')">Ban</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ban Modal -->
    <div class="modal-overlay" id="banModal">
        <div class="modal">
            <h3 id="banModalTitle">Ban User</h3>
            <p style="font-size:0.85rem; color:var(--muted); margin-bottom:12px;">Provide a comment explaining why this user is being suspended.</p>
            <form method="POST">
                <input type="hidden" name="action" value="ban">
                <input type="hidden" name="user_id" id="banUserId" value="">
                <textarea name="ban_reason" placeholder="E.g., Violating community guidelines..." required></textarea>
                <div class="modal-btns">
                    <button type="button" class="btn" style="background:#eee; color:#333;" onclick="hideBanModal()">Cancel</button>
                    <button type="submit" class="btn btn-red">Suspend User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showBanModal(id, name) {
            document.getElementById('banUserId').value = id;
            document.getElementById('banModalTitle').innerText = 'Ban ' + name;
            document.getElementById('banModal').style.display = 'flex';
        }
        function hideBanModal() {
            document.getElementById('banModal').style.display = 'none';
        }
    </script>
</body>
</html>
