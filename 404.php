<?php
require_once 'core/bootstrap.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('page_not_found_title', '404 — Page Not Found | SwiftBite'); ?></title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --orange: #ff4f00;
            --dark:   #1a1004;
            --cream:  #fff8f0;
            --cream2: #fff0dc;
            --muted:  #8b6a44;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            overflow: hidden;
        }

        /* Floating blobs */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.18;
            animation: drift 12s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .blob-1 { width: 500px; height: 500px; background: var(--orange); top: -120px; left: -120px; animation-delay: 0s; }
        .blob-2 { width: 350px; height: 350px; background: #ffb830; bottom: -80px; right: -60px; animation-delay: -5s; }
        @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(40px,30px) scale(1.08); } }

        .card {
            position: relative;
            background: #fff;
            border-radius: 40px;
            padding: 64px 56px;
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(255,79,0,0.14);
            animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.88) translateY(24px); }
            to   { opacity: 1; transform: scale(1)    translateY(0); }
        }

        .plate {
            font-size: 6rem;
            margin-bottom: 8px;
            animation: wobble 3s ease-in-out infinite;
            display: inline-block;
        }
        @keyframes wobble {
            0%,100% { transform: rotate(-4deg); }
            50%      { transform: rotate(4deg); }
        }

        .error-code {
            font-family: 'Syne', sans-serif;
            font-size: 7rem;
            font-weight: 800;
            line-height: 1;
            color: var(--orange);
            margin-bottom: 4px;
            letter-spacing: -4px;
        }
        .error-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
        }
        .error-desc {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 36px;
            max-width: 380px;
            margin-inline: auto;
        }

        .btn-group { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.25s;
        }
        .btn-primary {
            background: var(--orange);
            color: #fff;
            box-shadow: 0 8px 28px rgba(255,79,0,0.3);
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(255,79,0,0.4); }

        .btn-ghost {
            background: var(--cream2);
            color: var(--dark);
        }
        .btn-ghost:hover { background: #ffe4c0; transform: translateY(-3px); }

        .suggestions {
            margin-top: 36px;
            padding-top: 28px;
            border-top: 2px dashed var(--cream2);
        }
        .suggestions p {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }
        .suggestion-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .suggestion-links a {
            padding: 7px 18px;
            background: var(--cream);
            color: var(--dark);
            border-radius: 999px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1.5px solid var(--cream2);
            transition: all 0.2s;
        }
        .suggestion-links a:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        @media (max-width: 500px) {
            .card { padding: 40px 24px; border-radius: 28px; }
            .error-code { font-size: 5rem; }
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="card">
        <div class="plate"><i class="fa-solid fa-utensils"></i></div>
        <div class="error-code"><?php echo t_num('404'); ?></div>
        <div class="error-title"><?php echo __('oops_page_not_found', 'Oops! Page Not Found'); ?></div>
        <p class="error-desc">
            <?php echo __('page_not_found_desc', "Looks like this dish fell off the menu. The page you're looking for doesn't exist or may have been moved."); ?>
        </p>

        <div class="btn-group">
            <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-house"></i> <?php echo __('back_to_home', 'Back to Home'); ?></a>
            <a href="javascript:history.back()" class="btn btn-ghost"><?php echo __('go_back_arrow', '← Go Back'); ?></a>
        </div>

        <div class="suggestions">
            <p><?php echo __('or_explore', 'Or explore'); ?></p>
            <div class="suggestion-links">
                <a href="menu.php"><i class="fa-solid fa-pizza-slice"></i> <?php echo __('menu', 'Menu'); ?></a>
                <a href="restaurants.php">🏪 <?php echo __('restaurants', 'Restaurants'); ?></a>
                <a href="orders/cart.php"><i class="fa-solid fa-cart-shopping"></i> <?php echo __('cart', 'Cart'); ?></a>
                <a href="auth/login.php"><i class="fa-solid fa-key"></i> <?php echo __('login', 'Login'); ?></a>
            </div>
        </div>
    </div>
</body>
</html>

