<?php
// Mock eSewa Gateway designed to bypass eSewa's sandbox downtime 
$amount = $_POST['amount'] ?? '';
$total_amount = $_POST['total_amount'] ?? '';
$transaction_uuid = $_POST['transaction_uuid'] ?? '';
$success_url = $_POST['success_url'] ?? '';
$failure_url = $_POST['failure_url'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'pay') {
        // Generate a mock success payload matching eSewa's V2 structure
        $payload = json_encode([
            'status' => 'COMPLETE',
            'transaction_uuid' => $transaction_uuid,
            'total_amount' => $total_amount,
            'transaction_code' => 'MOCK-' . rand(100000, 999999)
        ]);
        
        $data = base64_encode($payload);
        header("Location: " . $success_url . "?data=" . urlencode($data));
        exit;
    }
    
    if ($_POST['action'] === 'cancel') {
        header("Location: " . $failure_url);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mock eSewa Server</title>
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #fafafa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); width: 400px; text-align: center; border: 2px solid #e5e5e5;}
        h2 { color: #41A124; font-family: 'Syne', sans-serif; margin-bottom: 10px;}
        .btn { padding: 14px 24px; border: none; border-radius: 12px; color: white; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; margin-bottom: 14px; transition: transform 0.2s;}
        .btn:hover { transform: scale(1.02); }
        .btn-success { background: linear-gradient(135deg, #41A124, #2d7a18); box-shadow: 0 6px 20px rgba(65, 161, 36, 0.3);}
        .btn-danger { background: linear-gradient(135deg, #ff4f00, #ff2400); box-shadow: 0 6px 20px rgba(255, 79, 0, 0.3);}
    </style>
</head>
<body>
    <div class="card">
        <h2>🟢 Offline eSewa Mock</h2>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 24px;">Because the real eSewa sandbox servers are down, this local page simulates their payment screen so you can finish testing your application flow instantly!</p>
        
        <div style="background: #f9f9f9; padding: 16px; margin-bottom: 24px; border-radius: 12px; text-align: left; border: 1px solid #eee;">
            <div style="margin-bottom: 8px;"><strong>Order ID:</strong> <span style="float: right; color: #888;"><?php echo htmlspecialchars($transaction_uuid); ?></span></div>
            <div><strong>Amount Due:</strong> <span style="float: right; color: #ff2400; font-weight: bold;">Rs. <?php echo htmlspecialchars($total_amount); ?></span></div>
        </div>

        <form method="POST">
            <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transaction_uuid); ?>">
            <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($total_amount); ?>">
            <input type="hidden" name="success_url" value="<?php echo htmlspecialchars($success_url); ?>">
            <input type="hidden" name="failure_url" value="<?php echo htmlspecialchars($failure_url); ?>">
            
            <button type="submit" name="action" value="pay" class="btn btn-success">✅ Simulate Successful Payment</button>
            <button type="submit" name="action" value="cancel" class="btn btn-danger">❌ Cancel Payment</button>
        </form>
    </div>
</body>
</html>
