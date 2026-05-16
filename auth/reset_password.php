<?php 
require_once '../core/bootstrap.php';

// Must have verified OTP
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header('Location: forgot_password.php');
    exit;
}

$error = $_SESSION['rp_error'] ?? '';
unset($_SESSION['rp_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password — SwiftBite</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root { --orange: #ff4f00; --dark: #1a1004; --cream: #fff8f0; --cream2: #fff0dc; --text: #3d2600; --muted: #8b6a44; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card {
            background: #fff; border-radius: 32px; padding: 48px 40px; width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px rgba(255,79,0,0.12);
        }
        .auth-logo { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--orange); text-align: center; margin-bottom: 8px; }
        .auth-logo span { color: var(--dark); }
        .auth-title { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--dark); text-align: center; margin-bottom: 6px; }
        .auth-subtitle { color: var(--muted); text-align: center; font-size: 0.92rem; margin-bottom: 28px; line-height: 1.6; }
        .auth-alert { padding: 12px 18px; border-radius: 14px; font-size: 0.88rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .auth-alert.error { background: rgba(255,59,48,0.08); color: #cc2d25; border: 1px solid rgba(255,59,48,0.15); }
        .auth-field { margin-bottom: 18px; }
        .auth-field label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--dark); margin-bottom: 6px; }
        .auth-field input {
            width: 100%; padding: 14px 18px; border: 2px solid var(--cream2); border-radius: 16px;
            font-size: 0.95rem; color: var(--text); background: var(--cream); outline: none; transition: border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .auth-field input:focus { border-color: var(--orange); background: #fff; }
        .auth-btn {
            width: 100%; padding: 16px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff;
            border: none; border-radius: 18px; font-weight: 800; font-size: 1.05rem; cursor: pointer;
            transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3);
            font-family: 'DM Sans', sans-serif;
        }
        .auth-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }
        .auth-icon { text-align: center; font-size: 3rem; margin-bottom: 12px; }

        /* Step indicator */
        .step-indicator { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 28px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--cream2); transition: all 0.3s; }
        .step-dot.active { background: var(--orange); width: 28px; border-radius: 10px; }
        .step-dot.done { background: #34c759; }
        .step-line { width: 30px; height: 2px; background: var(--cream2); }
        .step-line.done { background: #34c759; }

        /* Password strength */
        .pwd-strength { margin-top: 8px; }
        .pwd-bar { height: 4px; border-radius: 4px; background: var(--cream2); overflow: hidden; margin-bottom: 6px; }
        .pwd-bar-fill { height: 100%; border-radius: 4px; width: 0; transition: all 0.3s; }
        .pwd-label { font-size: 0.78rem; font-weight: 600; color: var(--muted); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">Swift<span>Bite</span></div>

        <!-- Step indicator -->
        <div class="step-indicator">
            <div class="step-dot done"></div>
            <div class="step-line done"></div>
            <div class="step-dot done"></div>
            <div class="step-line done"></div>
            <div class="step-dot active"></div>
        </div>

        <div class="auth-icon">🔒</div>
        <div class="auth-title">Set New Password</div>
        <div class="auth-subtitle">Your identity has been verified! Create a strong new password for your account.</div>

        <?php if ($error): ?>
            <div class="auth-alert error"><i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="../actions/reset_password_action.php" method="POST">
            <?php echo csrfInput(); ?>
            <div class="auth-field">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required minlength="6">
                <div class="pwd-strength">
                    <div class="pwd-bar"><div class="pwd-bar-fill" id="pwdFill"></div></div>
                    <span class="pwd-label" id="pwdLabel"></span>
                </div>
            </div>
            <div class="auth-field">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required minlength="6">
            </div>
            <button class="auth-btn" type="submit">Reset Password ✓</button>
        </form>
    </div>

    <script>
    (function() {
        const pwd = document.getElementById('password');
        const fill = document.getElementById('pwdFill');
        const label = document.getElementById('pwdLabel');

        pwd.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { width: '0%', color: '#ddd', text: '' },
                { width: '20%', color: '#ff3b30', text: 'Weak' },
                { width: '40%', color: '#ff9500', text: 'Fair' },
                { width: '60%', color: '#ffcc00', text: 'Good' },
                { width: '80%', color: '#34c759', text: 'Strong' },
                { width: '100%', color: '#00c7be', text: 'Excellent' }
            ];

            const l = levels[score];
            fill.style.width = l.width;
            fill.style.background = l.color;
            label.textContent = l.text;
            label.style.color = l.color;
        });
    })();
    </script>
</body>
</html>
