<?php
// session_start();
// require 'db.php';       // This should return a PDO connection as $conn
require_once __DIR__ . "/config/init.php";
require 'mailer.php';   // Your PHPMailer setup

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email address.";
        header("Location: forgot_password_form.php");
        exit();
    }

    // PDO query
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "Email not registered.";
        header("Location: forgot_password_form.php");
        exit();
    }

    // Generate OTP
    $otp = strval(rand(100000, 999999));
    $_SESSION['email'] = $email;
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_action'] = 'reset';
    $_SESSION['otp_time'] = time();

    if (sendOtpMail($email, $otp)) {
        $_SESSION['success'] = "OTP sent to your email.";
        header("Location: verify_otp.php");
    } else {
        $_SESSION['error'] = "Failed to send OTP. Try again later.";
        header("Location: forgot_password_form.php");
    }
    exit();
}

include 'forgot_password_form.php';
