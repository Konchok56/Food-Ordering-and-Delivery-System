<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }

$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([$_SESSION['user_id']]);
$currentRole = (string)$roleStmt->fetchColumn();

if ($currentRole !== 'admin') {
    echo "<h2 style='color:red;text-align:center;margin-top:80px;'>⛔ Admins only.</h2>";
    exit;
}

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($target_id > 0 && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE users SET is_approved = 1, role = 'delivery_partner' WHERE id = ? AND role = 'delivery_partner'")->execute([$target_id]);
            $_SESSION['rider_flash'] = ['type' => 'success', 'msg' => 'Rider approved successfully!'];
        } else {
            // Reject = delete the account (or you could set is_approved = -1)
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'delivery_partner' AND is_approved = 0")->execute([$target_id]);
            $_SESSION['rider_flash'] = ['type' => 'error', 'msg' => 'Rider application rejected and removed.'];
        }
    }
    header('Location: manage_riders.php');
    exit;
}

$flash = $_SESSION['rider_flash'] ?? null;
unset($_SESSION['rider_flash']);

// Pending riders
$pending = $pdo->query("SELECT * FROM users WHERE role = 'delivery_partner' AND is_approved = 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Approved riders
$approved = $pdo->query("SELECT * FROM users WHERE role = 'delivery_partner' AND is_approved = 1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>" <?= isRtlLang() ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Riders — SwiftBite Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=9">
    <style>
        .page { padding: 100px 24px 60px; min-height: 100vh; background: var(--cream); }
        .inner { max-width: 1100px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
        .topbar h1 { font-family: 'Syne', sans-serif; font-size: 2rem; color: var(--dark); margin: 0 0 6px; }
        .topbar p { color: var(--muted); margin: 0; }
        .back-link { text-decoration: none; padding: 12px 18px; border-radius: 14px; background: #fff; color: var(--dark); font-weight: 700; box-shadow: var(--shadow); }
        .back-link:hover { background: var(--orange); color: #fff; }

        .flash { padding: 14px 18px; border-radius: 14px; font-weight: 700; margin-bottom: 20px; }
        .flash-success { background: rgba(52,199,89,0.14); color: #1a7a34; border: 1px solid rgba(52,199,89,0.3); }
        .flash-error   { background: rgba(255,59,48,0.12); color: #cc2d25; border: 1px solid rgba(255,59,48,0.3); }

        .section-title { font-family: 'Syne', sans-serif; font-size: 1.3rem; color: var(--dark); margin: 0 0 16px; display: flex; align-items: center; gap: 10px; }
        .badge-count { background: var(--orange); color: #fff; border-radius: 999px; font-size: 0.78rem; font-weight: 800; padding: 2px 10px; }

        .riders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }

        .rider-card { background: #fff; border-radius: 22px; padding: 24px; box-shadow: var(--shadow); border: 2px solid transparent; transition: border-color 0.2s; }
        .rider-card.pending { border-color: rgba(255,184,48,0.4); }
        .rider-card.approved { border-color: rgba(52,199,89,0.3); }

        .rider-head { display: flex; gap: 14px; align-items: center; margin-bottom: 16px; }
        .rider-photo { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 3px solid var(--cream2); flex-shrink: 0; }
        .rider-photo-placeholder { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, var(--orange), #ff2400); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: #fff; flex-shrink: 0; }
        .rider-name { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--dark); font-size: 1rem; margin-bottom: 2px; }
        .rider-email { color: var(--muted); font-size: 0.82rem; word-break: break-all; }

        .rider-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
        .info-chip { background: var(--cream); border-radius: 12px; padding: 8px 12px; }
        .info-chip .label { font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
        .info-chip .value { font-size: 0.88rem; font-weight: 700; color: var(--dark); }

        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }
        .status-pending  { background: rgba(255,184,48,0.15); color: #d68c00; }
        .status-approved { background: rgba(52,199,89,0.15);  color: #1a7a34; }

        .btn-row { display: flex; gap: 10px; }
        .btn-approve { flex: 1; background: linear-gradient(135deg, #2ecc71, #1a9e55); color: #fff; border: none; padding: 11px; border-radius: 14px; font-weight: 800; font-size: 0.88rem; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: opacity 0.2s; }
        .btn-approve:hover { opacity: 0.88; }
        .btn-reject  { flex: 1; background: #fff; color: #cc2d25; border: 2px solid rgba(255,59,48,0.3); padding: 11px; border-radius: 14px; font-weight: 800; font-size: 0.88rem; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all 0.2s; }
        .btn-reject:hover  { background: rgba(255,59,48,0.08); }

        .empty-box { background: #fff; border-radius: 20px; padding: 40px 20px; text-align: center; box-shadow: var(--shadow); color: var(--muted); }
        .empty-box .big { font-size: 2.5rem; margin-bottom: 10px; }

        .view-photo-link { font-size: 0.8rem; color: var(--orange); text-decoration: none; font-weight: 700; }
        .view-photo-link:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .riders-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../sections/navbar.php'; ?>

    <div class="page">
        <div class="inner">
            <div class="topbar">
                <div>
                    <h1>🛵 Manage Riders</h1>
                    <p>Review delivery partner applications and manage approved riders</p>
                </div>
                <a class="back-link" href="dashboard.php">← Admin Dashboard</a>
            </div>

            <?php if ($flash): ?>
                <div class="flash flash-<?php echo $flash['type']; ?>">
                    <?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?>
                    <?php echo htmlspecialchars($flash['msg']); ?>
                </div>
            <?php endif; ?>

            <!-- Pending Applications -->
            <div class="section-title">
                ⏳ Pending Applications
                <span class="badge-count"><?php echo count($pending); ?></span>
            </div>

            <?php if (empty($pending)): ?>
                <div class="empty-box" style="margin-bottom:36px;">
                    <div class="big">✅</div>
                    <p>No pending rider applications right now.</p>
                </div>
            <?php else: ?>
                <div class="riders-grid">
                    <?php foreach ($pending as $rider): ?>
                    <div class="rider-card pending">
                        <div class="rider-head">
                            <?php if (!empty($rider['profile_photo'])): ?>
                                <img class="rider-photo" src="../<?php echo htmlspecialchars($rider['profile_photo']); ?>" alt="Photo">
                            <?php else: ?>
                                <div class="rider-photo-placeholder"><?php echo strtoupper(substr($rider['name'],0,1)); ?></div>
                            <?php endif; ?>
                            <div>
                                <div class="rider-name"><?php echo htmlspecialchars($rider['name']); ?></div>
                                <div class="rider-email"><?php echo htmlspecialchars($rider['email']); ?></div>
                                <span class="status-badge status-pending" style="margin-top:4px;">⏳ Pending</span>
                            </div>
                        </div>

                        <div class="rider-info">
                            <div class="info-chip">
                                <div class="label">Phone</div>
                                <div class="value"><?php echo htmlspecialchars($rider['phone'] ?: '—'); ?></div>
                            </div>
                            <div class="info-chip">
                                <div class="label">Vehicle</div>
                                <div class="value"><?php echo htmlspecialchars($rider['city'] ?: '—'); ?></div>
                            </div>
                            <div class="info-chip">
                                <div class="label">Area</div>
                                <div class="value"><?php echo htmlspecialchars($rider['address'] ?: '—'); ?></div>
                            </div>
                            <div class="info-chip">
                                <div class="label">Applied</div>
                                <div class="value"><?php echo date('M d, Y', strtotime($rider['created_at'])); ?></div>
                            </div>
                        </div>

                        <?php if (!empty($rider['profile_photo'])): ?>
                            <a class="view-photo-link" href="../<?php echo htmlspecialchars($rider['profile_photo']); ?>" target="_blank">🖼️ View Full Photo</a>
                            <br><br>
                        <?php endif; ?>

                        <div class="btn-row">
                            <form method="POST">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="user_id" value="<?php echo (int)$rider['id']; ?>">
                                <input type="hidden" name="action"  value="approve">
                                <button class="btn-approve" type="submit">✅ Approve</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Reject and delete this application?')">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="user_id" value="<?php echo (int)$rider['id']; ?>">
                                <input type="hidden" name="action"  value="reject">
                                <button class="btn-reject" type="submit">❌ Reject</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Approved Riders -->
            <div class="section-title">
                ✅ Approved Riders
                <span class="badge-count" style="background:#2ecc71;"><?php echo count($approved); ?></span>
            </div>

            <?php if (empty($approved)): ?>
                <div class="empty-box">
                    <div class="big">🛵</div>
                    <p>No approved riders yet.</p>
                </div>
            <?php else: ?>
                <div class="riders-grid">
                    <?php foreach ($approved as $rider): ?>
                    <div class="rider-card approved">
                        <div class="rider-head">
                            <?php if (!empty($rider['profile_photo'])): ?>
                                <img class="rider-photo" src="../<?php echo htmlspecialchars($rider['profile_photo']); ?>" alt="Photo">
                            <?php else: ?>
                                <div class="rider-photo-placeholder"><?php echo strtoupper(substr($rider['name'],0,1)); ?></div>
                            <?php endif; ?>
                            <div>
                                <div class="rider-name"><?php echo htmlspecialchars($rider['name']); ?></div>
                                <div class="rider-email"><?php echo htmlspecialchars($rider['email']); ?></div>
                                <span class="status-badge status-approved" style="margin-top:4px;">✅ Active Rider</span>
                            </div>
                        </div>
                        <div class="rider-info">
                            <div class="info-chip">
                                <div class="label">Phone</div>
                                <div class="value"><?php echo htmlspecialchars($rider['phone'] ?: '—'); ?></div>
                            </div>
                            <div class="info-chip">
                                <div class="label">Vehicle</div>
                                <div class="value"><?php echo htmlspecialchars($rider['city'] ?: '—'); ?></div>
                            </div>
                            <div class="info-chip">
                                <div class="label">Area</div>
                                <div class="value"><?php echo htmlspecialchars($rider['address'] ?: '—'); ?></div>
                            </div>
                            <div class="info-chip">
                                <div class="label">Joined</div>
                                <div class="value"><?php echo date('M d, Y', strtotime($rider['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../sections/footer.php'; ?>
</body>
</html>
