<?php

// ========================================
// LOAD PHPMailer VIA COMPOSER AUTOLOADER
// ========================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendSwiftBiteEmail($to, $subject, $body)
{
  /** @var PHPMailer $mail */
  $mail = new PHPMailer(true);
  try {
    // Check if configuration constants are defined, else use defaults or fail
    $host = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
    $port = defined('MAIL_PORT') ? MAIL_PORT : 587;
    $user = defined('MAIL_USER') ? MAIL_USER : '';
    $pass = defined('MAIL_PASS') ? MAIL_PASS : '';
    $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : $user;
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'SwiftBite';

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $port;

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    $mail->send();
    return true;
  } catch (\Throwable $e) {
    // Catching everything (Exception and Error) to prevent crashing the caller
    error_log("Mailer Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
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
                  <h1 style='color:#ff6b1a; margin:0; font-size:26px; letter-spacing:2px;'><i class="fa-solid fa-burger"></i> SwiftBite</h1>
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
  if (is_array($items)) {
    foreach ($items as $item) {
      $itemName = htmlspecialchars((string)($item['food_name'] ?? 'Item'));
      $itemEmoji = htmlspecialchars((string)($item['emoji'] ?? ($item['food_emoji'] ?? '<i class="fa-solid fa-utensils"></i>')));
      $qty = (int)($item['quantity'] ?? 1);
      $price = (float)($item['price'] ?? 0);
      $itemTotal = number_format($price * $qty, 2);
      
      $itemRows .= "
          <tr>
            <td style='padding:10px 0; border-bottom:1px solid #f5ede3; font-size:14px; color:#3d2600;'>$itemEmoji $itemName</td>
            <td style='padding:10px 0; border-bottom:1px solid #f5ede3; text-align:center; font-size:14px; color:#8b6a44;'>x$qty</td>
            <td style='padding:10px 0; border-bottom:1px solid #f5ede3; text-align:right; font-size:14px; color:#3d2600; font-weight:bold;'>Rs. $itemTotal</td>
          </tr>";
    }
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
      <h2 style='color:#ff6b1a; margin:0 0 6px;'>Order Confirmed! <i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i></h2>
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
        <p style='margin:0; color:#3d2600; font-size:14px;'><i class="fa-solid fa-motorcycle"></i> We're preparing your order. You'll get another email when it's on its way!</p>
      </div>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "<i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Order $orderLabel Confirmed — SwiftBite";
  return sendSwiftBiteEmail($to, $subject, $body);
}

function sendOrderCancelledByCustomerEmail($to, $customerName, $order_id)
{
  $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
  $firstName = htmlspecialchars(explode(' ', trim($customerName))[0]);

  $content = "
      <h2 style='color:#d93025; margin:0 0 6px;'>Order Cancelled <i class="fa-solid fa-trash"></i></h2>
      <p style='color:#3d2600; margin:0 0 20px;'>Hi $firstName, your order <strong>$orderLabel</strong> has been successfully cancelled as per your request.</p>
      <div style='background:#fff0f0; border:1px solid #ffcccb; border-radius:10px; padding:16px 20px; margin-bottom:24px;'>
        <p style='margin:0; color:#3d2600; font-size:14px;'>The order has been removed from our system. If you change your mind, we're always here to serve you again!</p>
      </div>
      <p style='text-align:center;'>
        <a href='" . SITE_BASE_URL . "/menu.php' style='display:inline-block; padding:12px 24px; background:#ff6b1a; color:#fff; text-decoration:none; border-radius:50px; font-weight:bold; font-size:14px;'>Order Something Else</a>
      </p>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> Order $orderLabel Cancelled — SwiftBite";
  return sendSwiftBiteEmail($to, $subject, $body);
}
?>