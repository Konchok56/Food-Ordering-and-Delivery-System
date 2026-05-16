<?php
session_start();
include('../core/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = strtoupper(trim($_POST['promo_code'] ?? ''));
$subtotal = (float) ($_POST['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a promo code']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code']);
        exit;
    }

    if (!empty($promo['expiry_date']) && strtotime($promo['expiry_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This promo code has expired']);
        exit;
    }

    $discountAmount = 0;
    if ($promo['type'] === 'percent') {
        $discountAmount = ($promo['value'] / 100) * $subtotal;
    } else {
        $discountAmount = $promo['value'];
    }

    // Cap the discount at the subtotal bounds so we don't go negative
    if ($discountAmount > $subtotal) {
        $discountAmount = $subtotal;
    }

    echo json_encode([
        'success' => true,
        'message' => "Promo applied! Saving Rs. " . number_format($discountAmount, 2),
        'code' => $promo['code'],
        'discount_amount' => $discountAmount
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

