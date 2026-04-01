<?php 
session_start();
include('../includes/csrf.php');

// Make sure user came from step 1
if (!isset($_SESSION['otp_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$error = $_SESSION['otp_error'] ?? '';
$success = $_SESSION['otp_success'] ?? '';
unset($_SESSION['otp_error'], $_SESSION['otp_success']);

$masked_email = $_SESSION['otp_email'];
$at_pos = strpos($masked_email, '@');
if ($at_pos > 2) {
    $masked_email = substr($masked_email, 0, 2) . str_repeat('•', $at_pos - 2) . substr($masked_email, $at_pos);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP — SwiftBite</title>
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
            background: #fff; border-radius: 32px; padding: 48px 40px; width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px rgba(255,79,0,0.12);
        }
        .auth-logo { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--orange); text-align: center; margin-bottom: 8px; }
        .auth-logo span { color: var(--dark); }
        .auth-title { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--dark); text-align: center; margin-bottom: 6px; }
        .auth-subtitle { color: var(--muted); text-align: center; font-size: 0.92rem; margin-bottom: 28px; line-height: 1.6; }
        .auth-alert { padding: 12px 18px; border-radius: 14px; font-size: 0.88rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .auth-alert.error { background: rgba(255,59,48,0.08); color: #cc2d25; border: 1px solid rgba(255,59,48,0.15); }
        .auth-alert.success { background: rgba(52,199,89,0.08); color: #1a7a34; border: 1px solid rgba(52,199,89,0.15); }
        .auth-btn {
            width: 100%; padding: 16px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff;
            border: none; border-radius: 18px; font-weight: 800; font-size: 1.05rem; cursor: pointer;
            transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3);
            font-family: 'DM Sans', sans-serif;
        }
        .auth-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }
        .auth-back { display: block; text-align: center; margin-top: 20px; color: var(--muted); font-size: 0.88rem; text-decoration: none; font-weight: 500; }
        .auth-back:hover { color: var(--orange); }
        .auth-icon { text-align: center; font-size: 3rem; margin-bottom: 12px; }

        /* Step indicator */
        .step-indicator { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 28px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--cream2); transition: all 0.3s; }
        .step-dot.active { background: var(--orange); width: 28px; border-radius: 10px; }
        .step-dot.done { background: #34c759; }
        .step-line { width: 30px; height: 2px; background: var(--cream2); }
        .step-line.done { background: #34c759; }

        /* OTP input boxes */
        .otp-inputs {
            display: flex; gap: 10px; justify-content: center; margin-bottom: 28px;
        }
        .otp-inputs input {
            width: 54px; height: 64px; text-align: center; font-family: 'Syne', sans-serif;
            font-size: 1.6rem; font-weight: 800; color: var(--dark); background: var(--cream);
            border: 2px solid var(--cream2); border-radius: 16px; outline: none;
            transition: all 0.2s;
        }
        .otp-inputs input:focus {
            border-color: var(--orange); background: #fff;
            box-shadow: 0 0 0 4px rgba(255,79,0,0.1);
        }
        .otp-inputs input.filled {
            border-color: var(--orange); background: rgba(255,79,0,0.04);
        }

        /* Hidden real input */
        .otp-hidden { position: absolute; opacity: 0; pointer-events: none; }

        .email-badge {
            display: inline-flex; align-items: center; gap: 6px; background: var(--cream2);
            padding: 8px 14px; border-radius: 50px; font-weight: 600; font-size: 0.85rem;
            color: var(--dark); margin: 0 auto 20px; 
        }
        .email-badge-wrap { text-align: center; }

        .resend-section { text-align: center; margin-top: 16px; }
        .resend-link {
            color: var(--muted); font-size: 0.85rem; text-decoration: none; font-weight: 500;
            cursor: pointer; transition: color 0.2s;
        }
        .resend-link:hover { color: var(--orange); }
        .resend-link.disabled { pointer-events: none; opacity: 0.5; }

    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">Swift<span>Bite</span></div>

        <!-- Step indicator -->
        <div class="step-indicator">
            <div class="step-dot done"></div>
            <div class="step-line done"></div>
            <div class="step-dot active"></div>
            <div class="step-line"></div>
            <div class="step-dot"></div>
        </div>

        <div class="auth-icon">📧</div>
        <div class="auth-title">Verify OTP</div>
        <div class="auth-subtitle">We've sent a 6-digit verification code to your email</div>

        <div class="email-badge-wrap">
            <span class="email-badge">📧 <?php echo htmlspecialchars($masked_email); ?></span>
        </div>

        <?php if ($error): ?>
            <div class="auth-alert error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>


        <form action="../actions/verify_otp_action.php" method="POST" id="otpForm">
            <?php echo csrfInput(); ?>
            <!-- Hidden input to collect full OTP -->
            <input type="hidden" name="otp" id="otpHidden">

            <div class="otp-inputs" id="otpInputs">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0" autofocus>
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
            </div>

            <button class="auth-btn" type="submit">Verify Code →</button>
        </form>

        <div class="resend-section">
            <form action="../actions/forgot_password_action.php" method="POST" style="display:inline;">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['otp_email']); ?>">
                <input type="hidden" name="resend" value="1">
                <button type="submit" class="resend-link" id="resendBtn">
                    Resend OTP (<span id="countdown">60</span>s)
                </button>
            </form>
        </div>

        <a href="forgot_password.php" class="auth-back">← Try a different email</a>
    </div>

    <script>
    (function() {
        const inputs = document.querySelectorAll('.otp-inputs input');
        const hidden = document.getElementById('otpHidden');
        const form = document.getElementById('otpForm');

        function collectOtp() {
            let otp = '';
            inputs.forEach(inp => otp += inp.value);
            hidden.value = otp;
        }

        inputs.forEach((inp, i) => {
            inp.addEventListener('input', function(e) {
                const val = this.value.replace(/[^0-9]/g, '');
                this.value = val;
                if (val) {
                    this.classList.add('filled');
                    if (i < inputs.length - 1) inputs[i + 1].focus();
                }
                collectOtp();
                // Auto-submit when all filled
                if (hidden.value.length === 6) {
                    form.submit();
                }
            });

            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && i > 0) {
                    inputs[i - 1].focus();
                    inputs[i - 1].value = '';
                    inputs[i - 1].classList.remove('filled');
                }
            });

            inp.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                for (let j = 0; j < Math.min(paste.length, 6); j++) {
                    inputs[j].value = paste[j];
                    inputs[j].classList.add('filled');
                }
                if (paste.length >= 6) {
                    inputs[5].focus();
                    collectOtp();
                    form.submit();
                } else {
                    inputs[Math.min(paste.length, 5)].focus();
                    collectOtp();
                }
            });
        });

        // Countdown for resend
        const btn = document.getElementById('resendBtn');
        const span = document.getElementById('countdown');
        let seconds = 60;
        btn.classList.add('disabled');

        const timer = setInterval(() => {
            seconds--;
            span.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                btn.classList.remove('disabled');
                btn.textContent = '🔄 Resend OTP';
            }
        }, 1000);
    })();
    </script>
</body>
</html>
