<?php

// ========================================
// LOAD PHPMailer VIA COMPOSER AUTOLOADER
// ========================================

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendSwiftBiteEmail($to, $subject, $body)
{
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sheetpillo@gmail.com';
    $mail->Password = 'julx gwvm kfhv idlp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('sheetpillo@gmail.com', 'SwiftBite');
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    return false;
  }
}

function _swiftbiteEmailWrapper($content)
{
  return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin:0; padding:0; background-color:#f5f0eb; font-family: Arial, sans-serif;'>
      <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f5f0eb; padding: 30px 0;'>
        <tr>
          <td align='center'>
            <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px; width:100%;'>
              <tr>
                <td style='background: linear-gradient(135deg, #1a0a00, #3d1f00); padding: 28px 32px; border-radius: 16px 16px 0 0; text-align: center;'>
                  <h1 style='color:#ff6b1a; margin:0; font-size:26px; letter-spacing:2px;'>🍔 SwiftBite</h1>
                  <p style='color:#c9a07d; margin:6px 0 0; font-size:13px;'>Fast. Fresh. Delivered.</p>
                </td>
              </tr>
              <tr>
                <td style='background:#ffffff; padding: 32px; border-left: 1px solid #f0e6d9; border-right: 1px solid #f0e6d9;'>
                  $content
                </td>
              </tr>
              <tr>
                <td style='background:#1a0a00; padding: 20px 32px; border-radius: 0 0 16px 16px; text-align:center;'>
                  <p style='color:#8b6a44; margin:0; font-size:12px;'>© " . date('Y') . " SwiftBite. All rights reserved.</p>
                  <p style='color:#5c4023; margin:6px 0 0; font-size:11px;'>This is an automated notification — please do not reply to this email.</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>
    ";
}

function sendOrderPlacedEmail(
  $to,
  $customerName,
  $order_id,
  $items,
  $subtotal,
  $deliveryFee,
  $discount,
  $total,
  $paymentMethod,
  $address
) {
  $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
  $firstName = htmlspecialchars(explode(' ', trim($customerName))[0]);

  $itemRows = '';
  foreach ($items as $item) {
    $itemName = htmlspecialchars($item['food_name']);
    $itemEmoji = htmlspecialchars($item['emoji'] ?? '🍽️');
    $itemQty = (int) $item['quantity'];
    $itemPrice = number_format($item['price'] * $item['quantity'], 2);
    $itemRows .= "
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid #f5ede3; font-size:14px; color:#3d2600;'>$itemEmoji $itemName</td>
          <td style='padding:10px 0; border-bottom:1px solid #f5ede3; text-align:center; font-size:14px; color:#8b6a44;'>x$itemQty</td>
          <td style='padding:10px 0; border-bottom:1px solid #f5ede3; text-align:right; font-size:14px; color:#3d2600; font-weight:bold;'>Rs. $itemPrice</td>
        </tr>";
  }

  $discountRow = '';
  if ($discount > 0) {
    $discountRow = "
        <tr>
          <td colspan='2' style='padding:6px 0; color:#27ae60; font-size:13px;'>Discount Applied</td>
          <td style='padding:6px 0; text-align:right; color:#27ae60; font-weight:bold;'>- Rs. " . number_format($discount, 2) . "</td>
        </tr>";
  }

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
      <div style='background:linear-gradient(135deg,#fff3e8,#ffe0c2); border-radius:10px; padding:16px 20px; text-align:center;'>
        <p style='margin:0; color:#3d2600; font-size:14px;'>🛵 We're preparing your order. You'll get another email when it's on its way!</p>
      </div>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "✅ Order $orderLabel Confirmed — SwiftBite";
  return sendSwiftBiteEmail($to, $subject, $body);
}
?>