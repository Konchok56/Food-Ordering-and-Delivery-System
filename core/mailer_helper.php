<?php

// ========================================
// LOAD PHPMailer VIA COMPOSER AUTOLOADER
// ========================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Core function to send an email via SwiftBite SMTP settings.
 * Subjects should be plain text (emojis are okay).
 */
function sendSwiftBiteEmail($to, $subject, $body)
{
  $mail = new PHPMailer(true);
  try {
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
    $mail->CharSet = 'UTF-8'; // Crucial for emojis

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], ["\n", "\n"], $body));
    
    $mail->send();
    return true;
  } catch (\Throwable $e) {
    error_log("Mailer Error: " . $e->getMessage());
    return false;
  }
}

/**
 * Wraps content in a professional, stunning SwiftBite-branded HTML template.
 */
function _swiftbiteEmailWrapper($content)
{
  $year = date('Y');
  // Strip any Font Awesome tags from content to prevent "coding lines" in emails
  $cleanContent = preg_replace('/<i class="fa-solid.*?"><\/i>/', '', $content);
  
  return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { margin:0; padding:0; background-color:#f4f1ee; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
            .content-table { background:#ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
            .header { background: linear-gradient(135deg, #1a1004, #2c1a07); padding: 40px 32px; text-align: center; }
            .body { padding: 45px 40px; color: #3d2600; line-height: 1.7; font-size: 16px; }
            .footer { background: #faf8f5; padding: 30px; text-align: center; border-top: 1px solid #eee; }
            .btn { display: inline-block; padding: 16px 32px; background: #ff4f00; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; box-shadow: 0 6px 15px rgba(255,79,0,0.2); }
            .logo-text { color:#ff4f00; font-size:32px; font-weight:800; letter-spacing:-1px; }
            .logo-text span { color:#ffffff; }
            .divider { height: 1px; background: #eee; margin: 30px 0; }
        </style>
    </head>
    <body>
      <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f1ee; padding: 50px 0;'>
        <tr>
          <td align='center'>
            <table width='600' cellpadding='0' cellspacing='0' class='content-table' style='max-width:600px; width:100%;'>
              <tr>
                <td class='header'>
                  <div class='logo-text'>Swift<span>Bite</span></div>
                  <div style='color:rgba(255,255,255,0.6); margin-top:8px; font-size:12px; text-transform:uppercase; letter-spacing:3px;'>Gourmet Delivered</div>
                </td>
              </tr>
              <tr>
                <td class='body'>
                  $cleanContent
                </td>
              </tr>
              <tr>
                <td class='footer'>
                  <p style='color:#8b6a44; margin:0; font-size:14px; font-weight:600;'>© $year SwiftBite Premium</p>
                  <p style='color:#b8a088; margin:8px 0 0; font-size:12px;'>Fast • Fresh • Reliable</p>
                  <div style='margin-top:20px; font-size:11px; color:#c9b7a3;'>You received this because you have a SwiftBite account.</div>
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

/**
 * Sends a detailed order confirmation email.
 */
function sendOrderPlacedEmail($to, $customerName, $order_id, $items, $subtotal, $deliveryFee, $discount, $total, $paymentMethod, $address) 
{
  $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
  $firstName = htmlspecialchars(explode(' ', trim($customerName))[0]);

  $itemRows = '';
  if (is_array($items)) {
    foreach ($items as $item) {
      $itemName = htmlspecialchars((string)($item['food_name'] ?? 'Item'));
      $itemEmoji = (string)($item['food_emoji'] ?? ($item['emoji'] ?? '🍽️'));
      // Strip any FA HTML icons if present, replace with a safe emoji
      if (strpos($itemEmoji, '<i') !== false) $itemEmoji = '🍽️';
      
      $qty = (int)($item['quantity'] ?? 1);
      $price = (float)($item['price'] ?? 0);
      $itemTotal = number_format($price * $qty, 2);
      
      $itemRows .= "
          <tr>
            <td style='padding:12px 0; border-bottom:1px solid #f1f1f1; font-size:14px;'>$itemEmoji <strong>$itemName</strong></td>
            <td style='padding:12px 0; border-bottom:1px solid #f1f1f1; text-align:center; font-size:14px; color:#8b6a44;'>×$qty</td>
            <td style='padding:12px 0; border-bottom:1px solid #f1f1f1; text-align:right; font-size:14px; font-weight:bold;'>Rs. $itemTotal</td>
          </tr>";
    }
  }

  $discountRow = '';
  if ($discount > 0) {
    $discountRow = "
        <tr>
          <td colspan='2' style='padding:8px 0; color:#27ae60; font-size:14px;'>Discount Applied</td>
          <td style='padding:8px 0; text-align:right; color:#27ae60; font-weight:bold;'>- Rs. " . number_format($discount, 2) . "</td>
        </tr>";
  }

  $content = "
      <h2 style='color:#1a0a00; margin:0 0 10px; font-size:24px;'>Order Confirmed! 🎉</h2>
      <p style='margin:0 0 24px; font-size:16px;'>Hi $firstName, we've received your order <strong>$orderLabel</strong>. Our kitchen is getting ready to serve you!</p>
      
      <div style='border: 1px solid #eee; border-radius:12px; padding:20px; margin-bottom:24px;'>
        <table width='100%' cellpadding='0' cellspacing='0'>
          $itemRows
          <tr>
            <td colspan='2' style='padding:12px 0 6px; font-size:13px; color:#8b6a44;'>Subtotal</td>
            <td style='padding:12px 0 6px; text-align:right; font-size:13px;'>Rs. " . number_format($subtotal, 2) . "</td>
          </tr>
          <tr>
            <td colspan='2' style='padding:6px 0; font-size:13px; color:#8b6a44;'>Delivery Fee</td>
            <td style='padding:6px 0; text-align:right; font-size:13px;'>Rs. " . number_format($deliveryFee, 2) . "</td>
          </tr>
          $discountRow
          <tr>
            <td colspan='2' style='padding:15px 0 0; font-size:17px; font-weight:bold;'>Total Amount</td>
            <td style='padding:15px 0 0; text-align:right; font-size:20px; font-weight:bold; color:#ff4f00;'>Rs. " . number_format($total, 2) . "</td>
          </tr>
        </table>
      </div>

      <div style='background:#fff9f2; border-radius:12px; padding:20px; text-align:center;'>
        <p style='margin:0; font-size:14px; color:#5c4023;'>🛵 Your food will arrive at <strong>" . htmlspecialchars($address) . "</strong>.</p>
      </div>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "✅ Order $orderLabel Confirmed — SwiftBite";
  return sendSwiftBiteEmail($to, $subject, $body);
}

/**
 * Sends an email when an order is cancelled.
 */
function sendOrderCancelledByCustomerEmail($to, $customerName, $order_id)
{
  $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
  $firstName = htmlspecialchars(explode(' ', trim($customerName))[0]);

  $content = "
      <h2 style='color:#d93025; margin:0 0 10px; font-size:24px;'>Order Cancelled ❌</h2>
      <p style='margin:0 0 24px; font-size:16px;'>Hi $firstName, your order <strong>$orderLabel</strong> has been successfully cancelled as per your request.</p>
      <div style='background:#fff0f0; border-radius:12px; padding:20px; margin-bottom:24px; text-align:center;'>
        <p style='margin:0; color:#c5221f; font-size:14px;'>If this was a mistake or you'd like to order something else, we're ready whenever you are!</p>
      </div>
      <p style='text-align:center; margin-top:30px;'>
        <a href='" . SITE_BASE_URL . "/menu.php' class='btn'>Explore Menu</a>
      </p>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "❌ Order $orderLabel Cancelled — SwiftBite";
  return sendSwiftBiteEmail($to, $subject, $body);
}

/**
 * Sends a password reset OTP email.
 */
function sendForgotPasswordOTPEmail($to, $customerName, $otp)
{
  $firstName = htmlspecialchars(explode(' ', trim($customerName))[0]);
  $content = "
      <h2 style='color:#1a0a00; margin:0 0 10px; font-size:24px;'>Reset Your Password 🔒</h2>
      <p style='margin:0 0 24px; font-size:16px;'>Hi $firstName, use the verification code below to reset your SwiftBite password. This code will expire in 10 minutes.</p>
      
      <div style='background:#1a1004; color:#ff4f00; padding:24px; text-align:center; border-radius:12px; margin-bottom:24px;'>
        <div style='font-size:36px; font-weight:bold; letter-spacing:8px;'>$otp</div>
      </div>
      
      <p style='margin:0; font-size:13px; color:#8b6a44; text-align:center;'>If you didn't request this, you can safely ignore this email.</p>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "🔑 $otp is your SwiftBite verification code";
  return sendSwiftBiteEmail($to, $subject, $body);
}

/**
 * Sends a status update email for an order.
 */
function sendOrderStatusEmail($to, $customerName, $order_id, $status, $riderName = '')
{
  $orderLabel = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
  $firstName = htmlspecialchars(explode(' ', trim($customerName))[0]);
  
  $statusConfig = [
    'preparing' => ['🧑‍🍳', 'Kitchen is Preparing', 'Our chefs are working their magic on your order right now.'],
    'ready'     => ['🥡', 'Ready for Pickup', 'Your order is packed and ready to go!'],
    'out_for_delivery' => ['🛵', 'Out for Delivery', 'Your order is on its way! Our delivery partner ' . ($riderName ?: 'is') . ' arriving soon.'],
    'delivered' => ['🎉', 'Enjoy Your Meal!', 'Your order has been delivered. We hope you love it!'],
    'cancelled' => ['❌', 'Order Cancelled', 'Your order has been cancelled. Please contact support if you have questions.']
  ];

  $cfg = $statusConfig[$status] ?? ['📦', 'Order Update', 'Your order status has been updated to ' . $status];
  
  $content = "
      <h2 style='color:#1a0a00; margin:0 0 10px; font-size:24px;'>$cfg[0] $cfg[1]</h2>
      <p style='margin:0 0 24px; font-size:16px;'>Hi $firstName, order <strong>$orderLabel</strong> status update:</p>
      <div style='background:#fdf8f3; border-radius:12px; padding:24px; margin-bottom:24px; border-left: 4px solid #ff4f00;'>
        <p style='margin:0; font-size:15px; color:#3d2600;'>$cfg[2]</p>
      </div>
      <p style='text-align:center; margin-top:30px;'>
        <a href='" . SITE_BASE_URL . "/user/order_details.php?id=$order_id' class='btn'>Track Order</a>
      </p>
    ";

  $body = _swiftbiteEmailWrapper($content);
  $subject = "$cfg[0] Order $orderLabel: $cfg[1]";
  return sendSwiftBiteEmail($to, $subject, $body);
}