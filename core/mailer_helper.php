<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Sends an email using PHPMailer.
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $body HTML body of the email
 * @return bool True if sent, false otherwise
 */
function sendSwiftBiteEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // --- ⚙️ SMTP CONFIGURATION ⚙️ ---
        // Replace these with your real credentials!
        // Get your free SMTP account at mailtrap.io or use Gmail.
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';           // Gmail SMTP Server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sheetpillo@gmail.com';     // 📧 YOUR GMAIL ADDRESS
        $mail->Password   = 'julx gwvm kfhv idlp';      // 🔑 YOUR 16-DIGIT APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- 📧 SENDER & RECIPIENT ---
        $mail->setFrom('no-reply@swiftbite.com', 'SwiftBite');
        $mail->addAddress($to);

        // --- 📄 CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // For non-HTML email clients

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("SwiftBite Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Returns a styled HTML email wrapper.
 */
function _swiftbiteEmailWrapper($content) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'></head>
    <body style='margin:0; padding:0; background-color:#f5f0eb; font-family: Arial, sans-serif;'>
      <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f5f0eb; padding: 30px 0;'>
        <tr><td align='center'>
          <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px; width:100%;'>
            <!-- Header -->
            <tr>
              <td style='background: linear-gradient(135deg, #1a0a00, #3d1f00); padding: 28px 32px; border-radius: 16px 16px 0 0; text-align: center;'>
                <h1 style='color:#ff6b1a; margin:0; font-size:26px; letter-spacing:2px;'>🍔 SwiftBite</h1>
                <p style='color:#c9a07d; margin:6px 0 0; font-size:13px;'>Fast. Fresh. Delivered.</p>
              </td>
            </tr>
            <!-- Body -->
            <tr>
              <td style='background:#ffffff; padding: 32px; border-left: 1px solid #f0e6d9; border-right: 1px solid #f0e6d9;'>
                $content
              </td>
            </tr>
            <!-- Footer -->
            <tr>
              <td style='background:#1a0a00; padding: 20px 32px; border-radius: 0 0 16px 16px; text-align:center;'>
                <p style='color:#8b6a44; margin:0; font-size:12px;'>© " . date('Y') . " SwiftBite. All rights reserved.</p>
                <p style='color:#5c4023; margin:6px 0 0; font-size:11px;'>This is an automated notification — please do not reply to this email.</p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>";
}

/**
 * Sends an "Order Placed" confirmation email.
 *
 * @param string $to           Customer email
 * @param string $customerName Customer first name
 * @param int    $order_id     Order ID
 * @param array  $items        Array of order item rows (food_name, quantity, price)
 * @param float  $subtotal
 * @param float  $deliveryFee
 * @param float  $discount
 * @param float  $total
 * @param string $paymentMethod
 * @param string $address
 * @return bool
 */
function sendOrderPlacedEmail($to, $customerName, $order_id, $items, $subtotal, $deliveryFee, $discount, $total, $paymentMethod, $address) {
    $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    $firstName  = htmlspecialchars(explode(' ', trim($customerName))[0]);

    // Build items table rows
    $itemRows = '';
    foreach ($items as $item) {
        $itemName  = htmlspecialchars($item['food_name']);
        $itemEmoji = htmlspecialchars($item['emoji'] ?? '🍽️');
        $itemQty   = (int)$item['quantity'];
        $itemPrice = number_format($item['price'] * $item['quantity'], 2);
        $itemRows .= "
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid #f5ede3; font-size:14px; color:#3d2600;'>
            $itemEmoji $itemName
          </td>
          <td style='padding:10px 0; border-bottom:1px solid #f5ede3; text-align:center; font-size:14px; color:#8b6a44;'>x$itemQty</td>
          <td style='padding:10px 0; border-bottom:1px solid #f5ede3; text-align:right; font-size:14px; color:#3d2600; font-weight:bold;'>Rs. $itemPrice</td>
        </tr>";
    }

    $discountRow = $discount > 0 ? "<tr>
      <td colspan='2' style='padding:6px 0; color:#27ae60; font-size:13px;'>Discount Applied</td>
      <td style='padding:6px 0; text-align:right; color:#27ae60; font-weight:bold;'>- Rs. " . number_format($discount, 2) . "</td>
    </tr>" : '';

    $payLabel = strtoupper($paymentMethod);

    $content = "
      <h2 style='color:#ff6b1a; margin:0 0 6px;'>Order Confirmed! 🎉</h2>
      <p style='color:#3d2600; margin:0 0 20px;'>Hi $firstName, your order <strong>$orderLabel</strong> has been placed successfully.</p>

      <div style='background:#fff8f0; border-radius:10px; padding:16px 20px; margin-bottom:24px;'>
        <table width='100%' cellpadding='0' cellspacing='0'>
          $itemRows
          <tr>
            <td colspan='2' style='padding:8px 0; font-size:13px; color:#8b6a44;'>Subtotal</td>
            <td style='padding:8px 0; text-align:right; font-size:13px; color:#3d2600;'>Rs. " . number_format($subtotal, 2) . "</td>
          </tr>
          <tr>
            <td colspan='2' style='padding:6px 0; font-size:13px; color:#8b6a44;'>Delivery Fee</td>
            <td style='padding:6px 0; text-align:right; font-size:13px; color:#3d2600;'>Rs. " . number_format($deliveryFee, 2) . "</td>
          </tr>
          $discountRow
          <tr>
            <td colspan='2' style='padding:10px 0 0; font-size:16px; font-weight:bold; color:#1a0a00;'>Total</td>
            <td style='padding:10px 0 0; text-align:right; font-size:18px; font-weight:bold; color:#ff6b1a;'>Rs. " . number_format($total, 2) . "</td>
          </tr>
        </table>
      </div>

      <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
        <tr>
          <td width='50%' style='padding-right:8px;'>
            <div style='background:#f9f3ec; border-radius:10px; padding:14px;'>
              <p style='margin:0; font-size:12px; color:#8b6a44; text-transform:uppercase; letter-spacing:1px;'>Payment</p>
              <p style='margin:4px 0 0; font-size:15px; font-weight:bold; color:#3d2600;'>$payLabel</p>
            </div>
          </td>
          <td width='50%' style='padding-left:8px;'>
            <div style='background:#f9f3ec; border-radius:10px; padding:14px;'>
              <p style='margin:0; font-size:12px; color:#8b6a44; text-transform:uppercase; letter-spacing:1px;'>Delivery To</p>
              <p style='margin:4px 0 0; font-size:14px; color:#3d2600;'>" . htmlspecialchars($address) . "</p>
            </div>
          </td>
        </tr>
      </table>

      <div style='background:linear-gradient(135deg,#fff3e8,#ffe0c2); border-radius:10px; padding:16px 20px; text-align:center;'>
        <p style='margin:0; color:#3d2600; font-size:14px;'>🛵 We're preparing your order. You'll get another email when it's on its way!</p>
      </div>";

    $body    = _swiftbiteEmailWrapper($content);
    $subject = "✅ Order $orderLabel Confirmed — SwiftBite";
    return sendSwiftBiteEmail($to, $subject, $body);
}

/**
 * Sends an order status update email to the customer.
 *
 * @param string $to           Customer email
 * @param string $customerName Customer name
 * @param int    $order_id     Order ID
 * @param string $status       New status (confirmed|preparing|out_for_delivery|delivered|cancelled)
 * @param string $deliveryPartnerName  (optional) Delivery partner name
 * @return bool
 */
function sendOrderStatusEmail($to, $customerName, $order_id, $status, $deliveryPartnerName = '') {
    $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    $firstName  = htmlspecialchars(explode(' ', trim($customerName))[0]);

    $statusInfo = [
        'confirmed'        => ['emoji' => '👍', 'title' => 'Order Confirmed!',        'color' => '#2ecc71', 'msg' => 'Great news! Your order has been confirmed and will be prepared shortly.'],
        'preparing'        => ['emoji' => '🧑‍🍳', 'title' => 'Preparing Your Food',  'color' => '#f39c12', 'msg' => 'The kitchen is now working on your delicious order. Sit tight!'],
        'out_for_delivery' => ['emoji' => '🛵', 'title' => 'Out for Delivery!',       'color' => '#3498db', 'msg' => 'Your order is on its way! Our delivery partner' . (!empty($deliveryPartnerName) ? " <strong>$deliveryPartnerName</strong>" : '') . ' is heading to you.'],
        'delivered'        => ['emoji' => '🎉', 'title' => 'Order Delivered!',        'color' => '#ff6b1a', 'msg' => 'Your order has been delivered. We hope you enjoy your meal! 😋'],
        'cancelled'        => ['emoji' => '❌', 'title' => 'Order Cancelled',         'color' => '#e74c3c', 'msg' => 'Your order has been cancelled by our team. If you have questions, please contact support.'],
    ];

    if (!isset($statusInfo[$status])) return false;

    $info = $statusInfo[$status];

    $content = "
      <h2 style='color:" . $info['color'] . "; margin:0 0 6px;'>" . $info['emoji'] . " " . $info['title'] . "</h2>
      <p style='color:#3d2600; margin:0 0 24px;'>Hi $firstName, here's an update on your order <strong>$orderLabel</strong>.</p>

      <div style='background:#fff8f0; border-left:4px solid " . $info['color'] . "; border-radius:8px; padding:20px; margin-bottom:24px;'>
        <p style='margin:0; color:#3d2600; font-size:15px;'>" . $info['msg'] . "</p>
      </div>

      <div style='text-align:center; padding: 10px 0;'>
        <span style='display:inline-block; background:#1a0a00; color:#ff6b1a; padding:8px 24px; border-radius:20px; font-size:13px; font-weight:bold; letter-spacing:1px;'>
          Status: " . strtoupper(str_replace('_', ' ', $status)) . "
        </span>
      </div>

      <p style='color:#8b6a44; font-size:13px; margin-top:24px; text-align:center;'>Thank you for choosing SwiftBite! 🍔</p>";

    $body    = _swiftbiteEmailWrapper($content);
    $subject = $info['emoji'] . " Order $orderLabel — " . $info['title'] . " | SwiftBite";
    return sendSwiftBiteEmail($to, $subject, $body);
}

/**
 * Sends an order cancellation email (initiated by customer).
 *
 * @param string $to           Customer email
 * @param string $customerName Customer name
 * @param int    $order_id     Order ID
 * @return bool
 */
function sendOrderCancelledByCustomerEmail($to, $customerName, $order_id) {
    $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    $firstName  = htmlspecialchars(explode(' ', trim($customerName))[0]);

    $content = "
      <h2 style='color:#e74c3c; margin:0 0 6px;'>❌ Order Cancelled</h2>
      <p style='color:#3d2600; margin:0 0 24px;'>Hi $firstName, your order <strong>$orderLabel</strong> has been successfully cancelled as requested.</p>

      <div style='background:#fff5f5; border-left:4px solid #e74c3c; border-radius:8px; padding:20px; margin-bottom:24px;'>
        <p style='margin:0; color:#3d2600; font-size:15px;'>If your payment has already been processed, a refund will be initiated within 3–5 business days.</p>
      </div>

      <p style='color:#8b6a44; font-size:14px;'>Changed your mind? You can place a new order anytime on SwiftBite. 🍔</p>";

    $body    = _swiftbiteEmailWrapper($content);
    $subject = "❌ Order $orderLabel Cancelled — SwiftBite";
    return sendSwiftBiteEmail($to, $subject, $body);
}
?>
