<?php
session_start();
header('Content-Type: application/json');

require_once '../core/config.php';
require_once '../core/db.php';
require_once '../core/recommendation_helper.php';

// Base URL constant from config (e.g. /food/swiftbite_php_starter)
$base = SITE_BASE_URL;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? strtolower(trim($input['message'])) : '';

if (empty($message)) {
    echo json_encode(['reply' => "I didn't quite catch that. Could you please rephrase?"]);
    exit;
}

// Simulated AI intent matching
$reply = "";

// 1. Greeting
if (preg_match('/\b(hi|hello|hey|greetings)\b/i', $message)) {
    $reply = "Hello! 👋 Welcome to SwiftBite. How can I help you with your cravings today?";
}
// 2. Order Status / Tracking
elseif (preg_match('/\b(order|where is my food|status|track|my food)\b/i', $message)) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $status = ucfirst(str_replace('_', ' ', $order['status']));
            $orderNum = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
            $reply = "Your most recent order (<b>#$orderNum</b>) is currently: <b>$status</b>.<br>";
            if ($order['status'] === 'out_for_delivery') {
                $reply .= "🛵 Your food is on the way! <a href='{$base}/orders/track.php?id={$order['id']}' style='color:#4f46e5;text-decoration:underline;'>Track live location</a>.";
            } elseif ($order['status'] === 'delivered') {
                $reply .= "🎉 Your order has been delivered. Enjoy your meal!";
            } elseif ($order['status'] === 'cancelled') {
                $reply .= "❌ This order was cancelled. <a href='{$base}/menu.php' style='color:#4f46e5;text-decoration:underline;'>Browse the menu</a> to place a new one.";
            } else {
                $reply .= "You can check full details on your <a href='{$base}/user/order_details.php?id={$order['id']}' style='color:#4f46e5;text-decoration:underline;'>Order Details</a> page.";
            }
        } else {
            $reply = "I couldn't find any recent orders for your account. Ready to place your first order? <a href='{$base}/menu.php' style='color:#4f46e5;text-decoration:underline;'>Browse the menu</a> 🍔";
        }
    } else {
        $reply = "To check your order status, please <a href='{$base}/auth/login.php' style='color:#4f46e5;text-decoration:underline;'>log in</a> first.";
    }
}
// 3. Cart Integration — "add burger", "order momos", "want pizza"
elseif (preg_match('/\b(add|buy|want|give me)\b\s+(.*)/i', $message, $matches)) {
    $searchQuery = trim($matches[2]);
    $searchQuery = preg_replace('/\b(to cart|please|a|an|some|me)\b/i', '', $searchQuery);
    $searchQuery = trim($searchQuery);

    $stmt = $pdo->prepare("SELECT id, name, price FROM foods WHERE name LIKE :search LIMIT 1");
    $stmt->execute(['search' => '%' . $searchQuery . '%']);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($food && isset($_SESSION['user_id'])) {
        $cartStmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND food_id = ?");
        $cartStmt->execute([$_SESSION['user_id'], $food['id']]);
        $existing = $cartStmt->fetch();

        if ($existing) {
            $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?")->execute([$existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO cart (user_id, food_id, food_name, price, quantity) VALUES (?, ?, ?, ?, 1)")
               ->execute([$_SESSION['user_id'], $food['id'], $food['name'], $food['price']]);
        }
        $reply = "✅ Added <b>1× {$food['name']}</b> (Rs. {$food['price']}) to your cart! 🛒 <a href='{$base}/orders/cart.php' style='color:#4f46e5;text-decoration:underline;'>View Cart</a>";
    } elseif (!$food) {
        $reply = "Hmm, I couldn't find an item matching '<b>$searchQuery</b>'. Try a different name or <a href='{$base}/menu.php' style='color:#4f46e5;text-decoration:underline;'>browse the full menu</a> 🍽️";
    } else {
        $reply = "Please <a href='{$base}/auth/login.php' style='color:#4f46e5;text-decoration:underline;'>log in</a> to add items to your cart.";
    }
}
// 4. Recommendations
elseif (preg_match('/\b(recommend|suggest|what should i eat|hungry|i want food)\b/i', $message)) {
    if (isset($_SESSION['user_id'])) {
        $recs = getRecommendations($pdo, $_SESSION['user_id'], 1);
        if (!empty($recs)) {
            $food = $recs[0];
            $reply = "Based on your taste, I highly recommend our <b>{$food['name']}</b>! ⭐{$food['rating']} rated and loved by customers. <a href='{$base}/food_detail.php?id={$food['id']}' style='color:#4f46e5;text-decoration:underline;'>Check it out!</a>";
        } else {
            $reply = "Check out our top picks on the <a href='{$base}/menu.php' style='color:#4f46e5;text-decoration:underline;'>menu page</a> — something for everyone! 🍕";
        }
    } else {
        $reply = "We have amazing dishes! Explore our <a href='{$base}/menu.php' style='color:#4f46e5;text-decoration:underline;'>full menu</a> to find your next favourite. 😋";
    }
}
// 5. Operating Hours
elseif (preg_match('/\b(hours|open|close|when|timing)\b/i', $message)) {
    $reply = "SwiftBite is available 24/7! 🕒 Delivery times vary by restaurant, but most partners deliver from <b>8 AM – 11 PM</b>.";
}
// 6. Contact / Support
elseif (preg_match('/\b(human|agent|support|help|contact|problem|issue)\b/i', $message)) {
    $reply = "For direct support, you can reach us at <b>support@swiftbite.com</b> or call <b>+977-1-SWIFT</b>. 🎧<br><i>Our agents are available 8 AM – 10 PM daily.</i>";
}
// 7. Thank you
elseif (preg_match('/\b(thanks|thank you|awesome|great|good)\b/i', $message)) {
    $reply = "You're very welcome! 😊 Let me know if there's anything else I can help with. Enjoy your meal! 🍔";
}
// 8. Fallback
else {
    $reply = "I'm not sure I understand that. 🤔<br>Try asking me to <b>add food to cart</b>, check your <b>order status</b>, or get a <b>food recommendation</b>!";
}

// Slight delay to simulate thinking
usleep(400000); // 0.4s

echo json_encode(['reply' => $reply]);
