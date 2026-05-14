<?php
// Base URL constant from config (e.g. /food/swiftbite_php_starter)
$base = SITE_BASE_URL;

// 1. Greeting
if (preg_match('/\b(hi|hello|hey|greetings|good morning|good afternoon|good evening)\b/i', $message)) {
    $greetings = [
        "Hello! 👋 Welcome to SwiftBite. Ready for something delicious?",
        "Hi there! 👋 What are you craving today?",
        "Hey! SwiftBite at your service. How can I help you?",
        "Greetings! 👋 Hungry? I can help you find the best food in town."
    ];
    $reply = $greetings[array_rand($greetings)];
}
// 2. Order Status / Tracking
elseif (preg_match('/\b(order|where is my food|status|track|my food)\b/i', $message)) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, status, total FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $status = ucfirst(str_replace('_', ' ', $order['status']));
            $orderNum = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
            $reply = "🔍 Found your most recent order (<b>#$orderNum</b>).<br>";
            $reply .= "Current Status: <b>$status</b><br>";
            $reply .= "Total: <b>Rs. " . number_format($order['total'], 2) . "</b><br><br>";
            
            if ($order['status'] === 'out_for_delivery') {
                $reply .= "🛵 <b>Your food is on the way!</b> <a href='{$base}/orders/track.php?id={$order['id']}' style='color:#41A124;font-weight:bold;'>Track live location</a>";
            } elseif ($order['status'] === 'delivered') {
                $reply .= "🎉 <b>Order Delivered!</b> Hope you're enjoying your meal!";
            } elseif ($order['status'] === 'cancelled') {
                $reply .= "❌ This order was cancelled. Want to try something else? <a href='{$base}/menu.php' style='color:#41A124;font-weight:bold;'>Browse Menu</a>";
            } else {
                $reply .= "📅 We're working on it! Check full details on your <a href='{$base}/user/order_details.php?id={$order['id']}' style='color:#41A124;font-weight:bold;'>Order Details</a> page.";
            }
        } else {
            $reply = "I couldn't find any recent orders for you. Ready to place your first order? <a href='{$base}/menu.php' style='color:#41A124;font-weight:bold;'>Explore the menu!</a> 🍔";
        }
    } else {
        $reply = "To track your order, please <a href='{$base}/auth/login.php' style='color:#41A124;font-weight:bold;'>log in</a> first.";
    }
}
// 3. Cart Integration — "add 2 burger", "order 3 momos", "want pizza"
elseif (preg_match('/\b(add|buy|want|order|get)\b\s+(?:(\d+)\s+)?(.*)/i', $message, $matches)) {
    $qty = !empty($matches[2]) ? (int)$matches[2] : 1;
    $searchQuery = trim($matches[3]);
    $searchQuery = preg_replace('/\b(to cart|please|a|an|some|me)\b/i', '', $searchQuery);
    $searchQuery = trim($searchQuery);
    
    // Simple plural to singular handling (e.g. burgers -> burger)
    $cleanQuery = (strlen($searchQuery) > 3 && substr($searchQuery, -1) === 's') ? substr($searchQuery, 0, -1) : $searchQuery;

    if (empty($searchQuery)) {
        $reply = "What would you like to add? Try saying 'add burger' or 'order 2 pizzas'!";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, price, emoji FROM foods WHERE name LIKE :search OR category LIKE :search OR name LIKE :clean OR category LIKE :clean LIMIT 1");
        $stmt->execute([
            'search' => '%' . $searchQuery . '%',
            'clean' => '%' . $cleanQuery . '%'
        ]);
        $food = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($food && isset($_SESSION['user_id'])) {
            $cartStmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND food_id = ?");
            $cartStmt->execute([$_SESSION['user_id'], $food['id']]);
            $existing = $cartStmt->fetch();

            if ($existing) {
                $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $existing['id']]);
            } else {
                $pdo->prepare("INSERT INTO cart (user_id, food_id, food_name, price, quantity) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$_SESSION['user_id'], $food['id'], $food['name'], $food['price'], $qty]);
            }
            $emoji = $food['emoji'] ?: '✅';
            $reply = "$emoji <b>Added $qty × {$food['name']}</b> to your cart!<br>";
            $reply .= "Total for this item: <b>Rs. " . number_format($food['price'] * $qty, 2) . "</b><br>";
            $reply .= "🛒 <a href='{$base}/orders/cart.php' style='color:#41A124;font-weight:bold;'>Go to Checkout</a>";
        } elseif (!$food) {
            $reply = "Hmm, I couldn't find '<b>$searchQuery</b>' in our kitchen. 🧐 Maybe try checking the <a href='{$base}/menu.php' style='color:#41A124;font-weight:bold;'>full menu</a>?";
        } else {
            $reply = "Please <a href='{$base}/auth/login.php' style='color:#41A124;font-weight:bold;'>log in</a> to start adding items to your cart!";
        }
    }
}
// 4. Menu Search — "burger", "pizza", "momos" (if just name is mentioned)
elseif (preg_match('/\b(burger|pizza|momo|chicken|veg|drinks|dessert|chowmein|biryani|pasta|sandwich|fries)\b/i', $message, $matches)) {
    $term = $matches[0];
    $cleanTerm = (strlen($term) > 3 && substr($term, -1) === 's') ? substr($term, 0, -1) : $term;
    
    $stmt = $pdo->prepare("SELECT id, name, price, emoji, rating FROM foods WHERE name LIKE :term OR category LIKE :term OR name LIKE :clean OR category LIKE :clean LIMIT 4");
    $stmt->execute([
        'term' => '%' . $term . '%',
        'clean' => '%' . $cleanTerm . '%'
    ]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        $reply = "Here's what I found for '<b>$term</b>':<br><br>";
        foreach ($items as $item) {
            $reply .= "{$item['emoji']} <b>{$item['name']}</b> - Rs.{$item['price']} (⭐{$item['rating']})<br>";
            $reply .= "<a href='{$base}/food_detail.php?id={$item['id']}' style='color:#41A124;font-size:0.8rem;'>View Item</a> | ";
            $reply .= "<a href='#' onclick='window.parent.postMessage({action:\"chat_input\", text:\"add {$item['name']}\"}, \"*\");return false;' style='color:#41A124;font-size:0.8rem;'>Add to Cart</a><br><br>";
        }
        $reply .= "See more results on our <a href='{$base}/menu.php' style='color:#41A124;font-weight:bold;'>Full Menu</a>.";
    } else {
        $reply = "I searched for '<b>$term</b>' but couldn't find matches. Try another dish!";
    }
}
// 5. Recommendations
elseif (preg_match('/\b(recommend|suggest|what should i eat|hungry|i want food|picks|choice)\b/i', $message)) {
    if (isset($_SESSION['user_id'])) {
        $recs = getRecommendations($pdo, $_SESSION['user_id'], 2);
        if (!empty($recs)) {
            $reply = "Based on your taste, you'll love these! ✨<br><br>";
            foreach ($recs as $food) {
                $reply .= "{$food['emoji']} <b>{$food['name']}</b><br>⭐ {$food['rating']} rated<br>";
                $reply .= "<a href='{$base}/food_detail.php?id={$food['id']}' style='color:#41A124;font-weight:bold;'>Check it out!</a><br><br>";
            }
        } else {
            $reply = "Check out our top picks on the <a href='{$base}/menu.php' style='color:#41A124;font-weight:bold;'>menu page</a> — everything is delicious! 🍕";
        }
    } else {
        $reply = "We have amazing dishes! Explore our <a href='{$base}/menu.php' style='color:#41A124;font-weight:bold;'>full menu</a> to find your next favourite. 😋";
    }
}
// 6. Operating Hours
elseif (preg_match('/\b(hours|open|close|when|timing)\b/i', $message)) {
    $reply = "SwiftBite partners are active 24/7! 🕒<br>Most restaurants deliver from <b>8:00 AM to 11:30 PM</b>. You can check individual restaurant status on their page.";
}
// 7. Contact / Support
elseif (preg_match('/\b(human|agent|support|help|contact|problem|issue|complain)\b/i', $message)) {
    $reply = "Need help? 🎧<br>📧 Email: <b>support@swiftbite.com</b><br>📞 Call: <b>+977-1-SWIFT-BITE</b><br>Our team is available 24/7 to assist you!";
}
// 8. Thank you
elseif (preg_match('/\b(thanks|thank you|awesome|great|good|bye)\b/i', $message)) {
    $replies = ["You're welcome! 😊", "Anytime! Let me know if you need anything else.", "Happy to help! Enjoy SwiftBite! 🍔", "Bye! Hope to see you back soon! 👋"];
    $reply = $replies[array_rand($greetings)];
}
// 9. Fallback
else {
    $reply = "I'm still learning! 🤖<br>Try asking me:<br>";
    $reply .= "• <b>\"add 2 chicken burgers\"</b><br>";
    $reply .= "• <b>\"where is my food\"</b><br>";
    $reply .= "• <b>\"show me some pizza\"</b><br>";
    $reply .= "• <b>\"recommend me something\"</b>";
}
