<?php
session_start();
include('../core/db.php');
include('../core/csrf.php');
include('../core/cart_helper.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cartCount = getCartCount($pdo, $user_id);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Profile — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        .page { padding: 100px 24px 60px; min-height: 100vh; background: var(--cream); }
        .inner { max-width: 600px; margin: 0 auto; }
        .header { margin-bottom: 32px; text-align: center; }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--dark); }
        
        .card { background: #fff; border-radius: 32px; padding: 40px; box-shadow: var(--shadow); }
        .avatar { width: 100px; height: 100px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-family: 'Syne', sans-serif; font-weight: 800; margin: 0 auto 24px; box-shadow: 0 10px 30px rgba(255, 79, 0, 0.2); }
        
        .alert { padding: 14px 20px; border-radius: 16px; font-weight: 600; font-size: 0.95rem; margin-bottom: 24px; text-align: center; }
        .alert-success { background: rgba(52, 199, 89, 0.1); color: #1a7a34; border: 1px solid rgba(52, 199, 89, 0.2); }
        .alert-error { background: rgba(255, 59, 48, 0.1); color: #cc2d25; border: 1px solid rgba(255, 59, 48, 0.2); }

        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; color: var(--dark); margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; padding: 14px 18px; border: 2px solid var(--cream2); border-radius: 16px; font-size: 1rem; color: var(--text); background: var(--cream); outline: none; transition: border-color 0.2s; font-family: 'DM Sans', sans-serif; }
        .form-group input:focus, .form-group select:focus { border-color: var(--orange); background: #fff; }
        
        .update-btn { width: 100%; padding: 16px; background: var(--orange); color: #fff; border: none; border-radius: 18px; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3); margin-top: 12px; }
        .update-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }

        .logout-link { display: block; text-align: center; margin-top: 24px; color: var(--red); font-weight: 700; text-decoration: none; padding: 12px; border-radius: 12px; transition: background 0.2s; }
        .logout-link:hover { background: rgba(255, 59, 48, 0.1); }
    </style>
</head>
<body>
    <?php include '../templates/navbar.php'; ?>

    <div class="page">
        <div class="inner">
            <div class="header">
                <h1>My Profile</h1>
            </div>

            <div class="card">
                <div class="avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="../actions/update_user/profile.php" method="POST">
                    <?php echo csrfInput(); ?>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span style="font-weight:400;color:var(--muted);">(Cannot be changed)</span></label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity: 0.7; cursor: not-allowed; border-color: transparent;">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="9812345678">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Street, area...">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <select id="city" name="city">
                            <option value="Kathmandu" <?php echo ($user['city'] ?? 'Kathmandu') === 'Kathmandu' ? 'selected' : ''; ?>>Kathmandu</option>
                            <option value="Lalitpur" <?php echo ($user['city'] ?? '') === 'Lalitpur' ? 'selected' : ''; ?>>Lalitpur</option>
                            <option value="Bhaktapur" <?php echo ($user['city'] ?? '') === 'Bhaktapur' ? 'selected' : ''; ?>>Bhaktapur</option>
                        </select>
                    </div>

                    <button type="submit" class="update-btn">💾 Save Changes</button>
                    <a href="../../auth/logout.php" class="logout-link">🚪 Sign Out</a>
                </form>
            </div>
            
        </div>
    </div>

    <?php include '../templates/floating_menu.php'; ?>

    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/cart.js"></script>
</body>
</html>
