<?php
session_start();
include('../includes/db.php');

$data = $_GET['data'] ?? '';
if (empty($data)) {
    die("Invalid request");
}

$decodedData = base64_decode($data);
$responseData = json_decode($decodedData, true);

if ($responseData && isset($responseData['status']) && $responseData['status'] === 'COMPLETE') {
    $transaction_uuid = $responseData['transaction_uuid'] ?? '';
    
    // Find order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE transaction_id = ?");
    $stmt->execute([$transaction_uuid]);
    $order = $stmt->fetch();
    
    if ($order && $order['status'] !== 'confirmed') {
        // Officially confirm the order since payment is successful
        $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?")->execute([$order['id']]);
        header("Location: ../order_confirmation.php?id=" . $order['id'] . "&payment=success");
        exit;
    } else if ($order && $order['status'] === 'confirmed') {
        // Already confirmed
        header("Location: ../order_confirmation.php?id=" . $order['id']);
        exit;
    }
}

echo "<div style='text-align:center; margin-top: 50px; font-family: sans-serif;'>";
echo "<h2>Payment Verification Failed</h2>";
echo "<p>Something went wrong with the payment validation. Your order is still pending.</p>";
echo "<a href='../cart.php' style='color:#ff4f00; font-weight:bold;'>Return to Cart</a>";
echo "</div>";
?>
