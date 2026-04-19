<?php
session_start();
include('../core/config.php');

$reason = $_SESSION['banned_reason'] ?? 'Violation of our Terms of Service.';
// If no session reason is available, we simply show a generic message.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --cream: #fff8f0;
            --dark: #1a1004;
            --red: #f43f5e;
            --muted: #8b6a44;
            --shadow: 0 20px 60px rgba(244, 63, 94, 0.15);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            max-width: 500px;
            width: 100%;
            background: #fff;
            padding: 48px;
            border-radius: 32px;
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 8px solid var(--red);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(244, 63, 94, 0.1);
            color: var(--red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 24px;
        }
        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        p {
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .reason-box {
            background: #f9f9f9;
            border-left: 4px solid var(--red);
            padding: 16px 24px;
            border-radius: 8px 16px 16px 8px;
            text-align: left;
            margin-bottom: 32px;
        }
        .reason-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #999;
            margin-bottom: 6px;
            display: block;
        }
        .reason-text {
            color: var(--dark);
            font-weight: 500;
            font-style: italic;
        }
        .contact-info {
            background: #fff0dc;
            padding: 20px;
            border-radius: 16px;
        }
        .contact-info strong {
            display: block;
            color: #ff4f00;
            font-size: 1.2rem;
            margin-bottom: 4px;
        }
        .back-link {
            display: inline-block;
            margin-top: 32px;
            color: var(--dark);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
            padding: 10px 20px;
            border-radius: 99px;
            background: #f1f1f1;
        }
        .back-link:hover {
            background: #e1e1e1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-circle">⛔</div>
        <h1>Account Suspended</h1>
        <p>Your SwiftBite access has been restricted by an administrator.</p>
        
        <div class="reason-box">
            <span class="reason-label">Reason for suspension:</span>
            <div class="reason-text">"<?php echo nl2br(htmlspecialchars($reason)); ?>"</div>
        </div>

        <div class="contact-info">
            <span>If you believe this is a mistake, please contact support at:</span>
            <br><br>
            <strong>+977 9800000000</strong>
            <span style="font-size:0.9rem; color:var(--muted)">Available 9 AM - 5 PM (NPT)</span>
        </div>

        <a href="../index.php" class="back-link">Return to Homepage</a>
    </div>
</body>
</html>
