<?php session_start();
include('../includes/csrf.php');
$error = $_SESSION['login_error'] ?? '';
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login — SwiftBite</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root { --orange: #ff4f00; --dark: #1a1004; --cream: #fff8f0; --cream2: #fff0dc; --text: #3d2600; --muted: #8b6a44; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card {
            background: #fff; border-radius: 32px; padding: 48px 40px; width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(255,79,0,0.12);
        }
        .auth-logo { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--orange); text-align: center; margin-bottom: 8px; }
        .auth-logo span { color: var(--dark); }
        .auth-title { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--dark); text-align: center; margin-bottom: 6px; }
        .auth-subtitle { color: var(--muted); text-align: center; font-size: 0.92rem; margin-bottom: 28px; }
        .auth-alert { padding: 12px 18px; border-radius: 14px; font-size: 0.88rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .auth-alert.error { background: rgba(255,59,48,0.08); color: #cc2d25; border: 1px solid rgba(255,59,48,0.15); }
        .auth-alert.success { background: rgba(52,199,89,0.08); color: #1a7a34; border: 1px solid rgba(52,199,89,0.15); }
        .auth-field { margin-bottom: 18px; }
        .auth-field label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--dark); margin-bottom: 6px; }
        .auth-field input {
            width: 100%; padding: 14px 18px; border: 2px solid var(--cream2); border-radius: 16px;
            font-size: 0.95rem; color: var(--text); background: var(--cream); outline: none; transition: border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .auth-field input:focus { border-color: var(--orange); background: #fff; }
        .auth-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 0.88rem; }
        .auth-options label { display: flex; align-items: center; gap: 6px; cursor: pointer; color: var(--text); font-weight: 500; }
        .auth-options a { color: var(--orange); text-decoration: none; font-weight: 600; }
        .auth-options a:hover { text-decoration: underline; }
        .auth-btn {
            width: 100%; padding: 16px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff;
            border: none; border-radius: 18px; font-weight: 800; font-size: 1.05rem; cursor: pointer;
            transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3);
            font-family: 'DM Sans', sans-serif;
        }
        .auth-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }
        .auth-footer { text-align: center; margin-top: 24px; font-size: 0.92rem; color: var(--muted); }
        .auth-footer a { color: var(--orange); font-weight: 700; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
        .auth-back { display: block; text-align: center; margin-top: 16px; color: var(--muted); font-size: 0.85rem; text-decoration: none; }
        .auth-back:hover { color: var(--orange); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">Swift<span>Bite</span></div>
        <div class="auth-title">Welcome Back</div>
        <div class="auth-subtitle">Sign in to your account</div>

        <?php if ($error): ?>
            <div class="auth-alert error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="auth-alert success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form action="../actions/login_action.php" method="POST">
            <?php echo csrfInput(); ?>
            <div class="auth-field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" required>
            </div>
            <div class="auth-field">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required>
            </div>
            <div class="auth-options">
                <label><input type="checkbox" name="remember"> Remember me</label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            <button class="auth-btn" type="submit">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register</a>
        </div>
        <a href="../index.php" class="auth-back">← Back to SwiftBite</a>
    </div>
</body>
</html>