<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required if not using Composer autoload
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

function sendOtpMail($toEmail, $otp) {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hiidemodemo@gmail.com';      // Your Gmail
        $mail->Password   = 'dlqy fivp kbix mfrm';         // Your App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('hiidemodemo@gmail.com');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP is: <strong>$otp</strong>";
        $mail->AltBody = "Your OTP is: $otp";

        $mail->send();
        return true;  // ✅ success
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false; // ❌ failed
    }
}

// Just added
function sendCustomMail($toEmail, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hiidemodemo@gmail.com';   // Your Gmail
        $mail->Password   = 'dlqy fivp kbix mfrm';     // Your App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('hiidemodemo@gmail.com');
        $mail->addAddress($toEmail);

        $mail->isHTML(false);  // plain text for order summary
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}