<?php
session_start();
header('Content-Type: application/json');

include('../core/db.php');
include('../core/cart_helper.php');

// 🔒 Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int) $_POST['cart_id'] : 0;
$action  = $_POST['action'] ?? '';

if (!$cart_id || !in_array($action, ['increase', 'decrease', 'remove', 'set'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Verify the cart item belongs to this user
$stmt = $pdo->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$cart_id, $user_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

switch ($action) {
    case 'increase':
        $newQty = $item['quantity'] + 1;
        if ($newQty > 50) $newQty = 50;
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $cart_id]);
        break;

    case 'decrease':
        $newQty = $item['quantity'] - 1;
        if ($newQty <= 0) {
            $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$cart_id]);
            $newQty = 0;
        } else {
            $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $cart_id]);
        }
        break;

    case 'remove':
        $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$cart_id]);
        $newQty = 0;
        break;

    case 'set':
        $newQty = isset($_POST['quantity']) ? max(1, min(50, (int) $_POST['quantity'])) : 1;
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $cart_id]);
        break;
}

// Calculate real-time item price using safe database checks
$dbPrice = (float) $item['price'];
if (!empty($item['food_id'])) {
    $priceStmt = $pdo->prepare("SELECT price FROM foods WHERE id = ? LIMIT 1");
    $priceStmt->execute([$item['food_id']]);
    $priceVal = $priceStmt->fetchColumn();
    if ($priceVal !== false) {
        $dbPrice = (float) $priceVal;
        // Sync the cart price with the database price
        $pdo->prepare("UPDATE cart SET price = ? WHERE id = ?")->execute([$dbPrice, $cart_id]);
    }
}

$itemSubtotal = ($newQty ?? 0) * $dbPrice;

// Get new totals using safe database checks (joining with foods table to prevent any price manipulation)
$totalStmt = $pdo->prepare("
    SELECT COALESCE(SUM(COALESCE(f.price, c.price) * c.quantity), 0) as total 
    FROM cart c 
    LEFT JOIN foods f ON c.food_id = f.id 
    WHERE c.user_id = ?
");
$totalStmt->execute([$user_id]);
$cartTotal = (float) $totalStmt->fetchColumn();

$cartCount = getCartCount($pdo, $user_id);

echo json_encode([
    'success'       => true,
    'action'        => $action,
    'cart_id'       => $cart_id,
    'new_quantity'  => $newQty ?? 0,
    'item_subtotal' => number_format($itemSubtotal, 2),
    'cart_total'    => number_format($cartTotal, 2),
    'cart_count'    => $cartCount,
    'is_empty'      => $cartCount === 0,
]);
exit;


