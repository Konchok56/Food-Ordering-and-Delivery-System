<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$order_id = $_GET['order_id'] ?? 0;
// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("Invalid order or order already processed.");
}

$total_amount = $order['total'];
$transaction_uuid = 'ORD-' . $order['id'] . '-' . time();

// Update order with transaction UUID so we can verify later
$pdo->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?")->execute([$transaction_uuid, $order_id]);

// eSewa Sandbox Details
$product_code = 'EPAYTEST';
$secret = '8gBm/:&EnhH.1/q';

$message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
$s = hash_hmac('sha256', $message, $secret, true);
$signature = base64_encode($s);

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$folder_path = dirname($_SERVER['PHP_SELF']); // e.g. /food/swiftbite_php_starter/actions
$success_url = $base_url . $folder_path . "/esewa_success.php";
$failure_url = $base_url . $folder_path . "/esewa_failure.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to eSewa...</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #fafafa;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #41A124;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="loader"></div>
    <h3>Redirecting to eSewa Secure Payment Gateway...</h3>
    <p>Please do not refresh or close this page.</p>

    <!-- TO USE REAL ESEWA, change the action below to: action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" -->
    <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST" id="esewaForm">
        <input type="hidden" id="amount" name="amount" value="<?php echo $total_amount; ?>" required>
        <input type="hidden" id="tax_amount" name="tax_amount" value="0" required>
        <input type="hidden" id="total_amount" name="total_amount" value="<?php echo $total_amount; ?>" required>
        <input type="hidden" id="transaction_uuid" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>"
            required>
        <input type="hidden" id="product_code" name="product_code" value="<?php echo $product_code; ?>" required>
        <input type="hidden" id="product_service_charge" name="product_service_charge" value="0" required>
        <input type="hidden" id="product_delivery_charge" name="product_delivery_charge" value="0" required>
        <input type="hidden" id="success_url" name="success_url" value="<?php echo $success_url; ?>" required>
        <input type="hidden" id="failure_url" name="failure_url" value="<?php echo $failure_url; ?>" required>
        <input type="hidden" id="signed_field_names" name="signed_field_names"
            value="total_amount,transaction_uuid,product_code" required>
        <input type="hidden" id="signature" name="signature" value="<?php echo $signature; ?>" required>
    </form>
    <script>
        setTimeout(() => {
            document.getElementById('esewaForm').submit();
        }, 1500); // Small delay for UX
    </script>
</body>

</html>