<?php 
require_once '../core/bootstrap.php';

if (isLoggedIn()) { redirect('index.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sign In — SwiftBite</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
    <script>(function(){var t=localStorage.getItem('sb-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script src="../assets/js/theme.js"></script>
    <style>
        :root { --orange:#ff4f00; --dark:#1a1004; --cream:#fff8f0; --cream2:#ffe0c2; --text:#3d2600; --muted:#8b6a44; }
        *{ box-sizing:border-box; margin:0; padding:0; }

        body{
            font-family:'DM Sans',sans-serif;
            background: linear-gradient(135deg, #1a0a00 0%, #3d1500 50%, #1a0a00 100%);
            min-height:100vh;
            display:flex; align-items:center; justify-content:center;
            padding:16px;
        }

        /* ── Card ── */
        .auth-wrap{
            display:grid;
            grid-template-columns: 260px 1fr;
            width:100%; max-width:720px;
            background:var(--white);
            border-radius:28px;
            overflow:hidden;
            box-shadow:0 30px 80px rgba(0,0,0,0.35);
        }

        /* ── Left panel ── */
        .auth-left{
            background: linear-gradient(160deg, #1a0a00, #3d1500);
            padding:40px 28px;
            display:flex; flex-direction:column;
            align-items:flex-start; justify-content:space-between;
        }
        .brand{ margin-bottom:32px; }
        .brand-logo{
            font-family:'Syne',sans-serif; font-size:1.9rem;
            font-weight:800; color:#ff6b1a;
        }
        .brand-logo span{ color:#fff; }
        .brand-tagline{ color:#c9a07d; font-size:0.82rem; margin-top:4px; }

        .left-perks{ display:flex; flex-direction:column; gap:16px; flex:1; justify-content:center; }
        .perk{ display:flex; align-items:flex-start; gap:12px; }
        .perk-icon{
            width:36px; height:36px; border-radius:10px;
            background:rgba(255,79,0,0.15); border:1px solid rgba(255,79,0,0.25);
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; flex-shrink:0;
        }
        .perk-text strong{ display:block; color:#fff; font-size:0.88rem; font-weight:700; }
        .perk-text span{ color:#c9a07d; font-size:0.78rem; }

        .left-footer{
            color:#5c3a1e; font-size:0.75rem; margin-top:32px;
            border-top:1px solid rgba(255,255,255,0.07); padding-top:16px; width:100%;
        }

        /* ── Right panel ── */
        .auth-right{
            padding:40px 36px;
            display:flex; flex-direction:column; justify-content:center;
        }
        .auth-title{
            font-family:'Syne',sans-serif; font-size:1.6rem;
            font-weight:800; color:var(--dark); margin-bottom:4px;
        }
        .auth-subtitle{ color:var(--muted); font-size:0.85rem; margin-bottom:24px; }

        /* Flash */
        .flash-msg{
            padding:11px 14px; border-radius:10px; font-size:0.82rem;
            font-weight:600; margin-bottom:18px;
            display:flex; align-items:center; gap:8px; line-height:1.4;
        }
        .flash-error   { background:rgba(255,59,48,0.08);  color:#cc2d25; border:1px solid rgba(255,59,48,0.2); }
        .flash-success { background:rgba(52,199,89,0.08);  color:#1a7a34; border:1px solid rgba(52,199,89,0.2); }
        .flash-warning { background:rgba(255,184,48,0.1);  color:#a06200; border:1px solid rgba(255,184,48,0.25); }

        /* Fields */
        .auth-field{ margin-bottom:16px; }
        .auth-field label{
            display:block; font-weight:600; font-size:0.8rem;
            color:var(--dark); margin-bottom:6px;
        }
        .auth-field input{
            width:100%; padding:12px 15px;
            border:1.5px solid var(--cream2); border-radius:12px;
            font-size:0.92rem; color:var(--text);
            background:var(--cream); outline:none; transition:border-color 0.2s;
            font-family:'DM Sans',sans-serif;
        }
        .auth-field input:focus{ border-color:var(--orange); background:#fff; }

        /* Options row */
        .auth-options{
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:20px; font-size:0.83rem;
        }
        .auth-options label{
            display:flex; align-items:center; gap:6px;
            cursor:pointer; color:var(--text); font-weight:500;
        }
        .auth-options label input[type=checkbox]{ accent-color:var(--orange); }
        .auth-options a{ color:var(--orange); text-decoration:none; font-weight:600; }
        .auth-options a:hover{ text-decoration:underline; }

        /* Button */
        .auth-btn{
            width:100%; padding:13px;
            background:linear-gradient(135deg,var(--orange),#ff2400);
            color:#fff; border:none; border-radius:12px;
            font-weight:800; font-size:0.98rem; cursor:pointer;
            transition:all 0.25s; box-shadow:0 6px 24px rgba(255,79,0,0.3);
            font-family:'DM Sans',sans-serif;
        }
        .auth-btn:hover{ transform:translateY(-2px); box-shadow:0 10px 32px rgba(255,79,0,0.42); }

        .auth-footer{ text-align:center; margin-top:18px; font-size:0.85rem; color:var(--muted); }
        .auth-footer a{ color:var(--orange); font-weight:700; text-decoration:none; }
        .auth-footer a:hover{ text-decoration:underline; }
        .auth-back{ display:block; text-align:center; margin-top:10px; color:#bbb; font-size:0.78rem; text-decoration:none; transition:color .2s; }
        .auth-back:hover{ color:var(--orange); }

        /* Divider */
        .divider{ display:flex; align-items:center; gap:10px; margin:18px 0; }
        .divider::before, .divider::after{ content:''; flex:1; height:1px; background:var(--cream2); }
        .divider span{ color:#ccc; font-size:0.75rem; font-weight:600; white-space:nowrap; }

        /* Responsive */
        @media (max-width:600px){
            .auth-wrap{ grid-template-columns:1fr; max-width:400px; }
            .auth-left{ padding:24px; flex-direction:row; flex-wrap:wrap; align-items:center; gap:12px; }
            .left-perks, .left-footer{ display:none; }
            .brand{ margin-bottom:0; }
            .auth-right{ padding:24px 20px; }
        }
    </style>
</head>
<body>
<div class="auth-wrap">

    <!-- Left -->
    <div class="auth-left">
        <div class="brand" style="display:flex; align-items:center; width:100%; justify-content:space-between;">
            <div>
                <div class="brand-logo">Swift<span>Bite</span></div>
                <div class="brand-tagline">Fast. Fresh. Delivered.</div>
            </div>
            <!-- Theme Toggle -->
            <button id="theme-toggle" class="theme-toggle-btn" style="width:36px; height:36px; font-size:0.9rem; background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.2); color:#fff;" title="Toggle theme">
                <span class="theme-icon theme-icon-sun">&#9728;</span>
                <span class="theme-icon theme-icon-moon"><i class="fa-solid fa-moon"></i></span>
            </button>
        </div>

        <div class="left-perks">
            <div class="perk">
                <div class="perk-icon"><i class="fa-solid fa-burger"></i></div>
                <div class="perk-text">
                    <strong>100+ Menu Items</strong>
                    <span>From local favourites to global bites</span>
                </div>
            </div>
            <div class="perk">
                <div class="perk-icon"><i class="fa-solid fa-motorcycle"></i></div>
                <div class="perk-text">
                    <strong>Fast Delivery</strong>
                    <span>Hot food at your door in 30 mins</span>
                </div>
            </div>
            <div class="perk">
                <div class="perk-icon">💸</div>
                <div class="perk-text">
                    <strong>Exclusive Deals</strong>
                    <span>Members get special promo codes</span>
                </div>
            </div>
            <div class="perk">
                <div class="perk-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="perk-text">
                    <strong>Live Tracking</strong>
                    <span>Watch your order arrive in real time</span>
                </div>
            </div>
        </div>

        <div class="left-footer">© <?php echo date('Y'); ?> SwiftBite. All rights reserved.</div>
    </div>

    <!-- Right -->
    <div class="auth-right">
        <div class="auth-title">Welcome Back 👋</div>
        <div class="auth-subtitle">Sign in to your SwiftBite account</div>

        <?php echo renderFlash(); ?>

        <form action="../actions/login_action.php" method="POST">
            <?php echo csrfInput(); ?>

            <div class="auth-field">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" required autocomplete="email">
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="auth-options">
                <label><input type="checkbox" name="remember"> Remember me</label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button class="auth-btn" type="submit">Sign In →</button>
        </form>

        <div class="divider"><span>New to SwiftBite?</span></div>

        <div class="auth-footer">
            <a href="register.php">Create a free account</a>
        </div>
        <a href="../index.php" class="auth-back">← Back to SwiftBite</a>
    </div>
</div>
</body>
</html>
