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
        $mail->setFrom('no-reply@swiftbite.com', 'SwiftBite Support');
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
?>
