<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <style>
        body { font-family: 'Syne', sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background: #fafafa; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 400px; }
        h2 { color: #ff2400; margin-bottom: 10px;}
        p { color: #888; font-family: 'DM Sans', sans-serif; margin-bottom: 30px;}
        a { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #ff4f00, #ff2400); color: white; border-radius: 12px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Payment Failed! ❌</h2>
        <p>Your eSewa payment was cancelled or failed. Your order has not been placed.</p>
        <a href="../orders/cart.php">Return to Cart</a>
    </div>
</body>
</html>
