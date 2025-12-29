<?php
if (session_status() === PHP_SESSION_NONE) {
  // session_start();
  require_once __DIR__ . "/config/init.php";
}

// require 'db.php';             // PDO DB connection
require 'mailer.php';         // ✅ REQUIRED: To send the OTP email
// Note: 'django_hasher.php' is not needed here anymore; we use it in verify_otp.php

// Ensure PDO throws exceptions so we notice errors during development
if ($conn instanceof PDO) {
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name  = trim($_POST['last_name'] ?? '');
  $username   = trim($_POST['username'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $password   = trim($_POST['password'] ?? '');

  // Server-side validation (include first/last name)
  if ($first_name === '' || $last_name === '' || $username === '' || $email === '' || $password === '') {
    $error = "All fields are required.";
  } else {
    try {
      // check if email already exists
      $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
      $stmt->execute(['email' => $email]);
      if ($stmt->fetch()) {
        $error = "An account already exists with that email.";
      } else {
        // Also check username uniqueness (recommended)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
          $error = "That username is already taken. Please choose another.";
        } else {
          // ✅ CHANGED LOGIC STARTS HERE

          // 1. Generate OTP
          $otp = rand(100000, 999999);

          // 2. Store data in Session temporarily (Do NOT insert into DB yet)
          $_SESSION['temp_user_data'] = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'username'   => $username,
            'email'      => $email,
            'password'   => $password // Stored raw, will be hashed in verify_otp.php
          ];
          $_SESSION['otp'] = $otp;
          $_SESSION['otp_action'] = 'signup';

          // 3. Send OTP Email using the function from mailer.php
          if (sendOtpMail($email, $otp)) {
            // Redirect to verification page
            header("Location: verify_otp.php");
            exit();
          } else {
            $error = "Failed to send OTP email. Please try again.";
          }
          // ✅ CHANGED LOGIC ENDS HERE
        }
      }
    } catch (PDOException $e) {
      // In production, don't reveal raw SQL errors. For development it's helpful.
      $error = "Database error: " . htmlspecialchars($e->getMessage());
    } catch (Exception $e) {
      $error = "Error: " . htmlspecialchars($e->getMessage());
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signup</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>css/login_signup.css">
</head>

<body>
  <div class="container">
    <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier</h1>


    <?php if ($error): ?>
      <p style="color: red">
        <?= htmlspecialchars($error) ?>
      </p>
    <?php endif; ?>

    <form method="POST" action="signup.php" novalidate>
      <input type="text" name="first_name" placeholder="First Name" required value="<?= isset($first_name) ? htmlspecialchars($first_name) : '' ?>"><br><br>
      <input type="text" name="last_name" placeholder="Last Name" required value="<?= isset($last_name) ? htmlspecialchars($last_name) : '' ?>"><br><br>
      <input type="text" name="username" placeholder="Username" required value="<?= isset($username) ? htmlspecialchars($username) : '' ?>"><br><br>
      <input type="email" name="email" placeholder="Email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"><br><br>

      <div style="position: relative; padding-bottom: 26px;">
        <input type="password" name="password" id="password" placeholder="Password" required
          style="width: 100%; padding-right: 50px;" />
        <span id="togglePassword" style="
          position: absolute; right: 12px; top: 36%;
          transform: translateY(-50%); cursor: pointer;
          font-size: 14px; color: black; font-weight: bold;
        ">Show</span>
      </div>

      <ul id="password-requirements" style="display: none; text-align: left; padding-left: 20px; font-size: 14px; margin-top: 10px;">
        <li id="length" style="color: red;">❌ At least 8 characters</li>
        <li id="letter" style="color: red;">❌ At least one alphabet (A-Z or a-z)</li>
        <li id="number" style="color: red;">❌ At least one number (0-9)</li>
        <li id="special" style="color: red;">❌ At least one special character (!@#$%^&*)</li>
      </ul>

      <button type="submit">Signup</button>
    </form>

    <p style="margin-top: 10px;">Already have an account? <a href="login.php" style="color: #ff5e62;">Login</a></p>
  </div>

  <script>
    const passwordInput = document.querySelector('input[name="password"]');
    const requirementsBox = document.getElementById("password-requirements");
    const lengthCheck = document.getElementById("length");
    const letterCheck = document.getElementById("letter");
    const numberCheck = document.getElementById("number");
    const specialCheck = document.getElementById("special");

    passwordInput.addEventListener("focus", () => requirementsBox.style.display = "block");
    passwordInput.addEventListener("input", () => {
      const value = passwordInput.value;

      lengthCheck.textContent = (value.length >= 8) ? "✅ At least 8 characters" : "❌ At least 8 characters";
      lengthCheck.style.color = (value.length >= 8) ? "green" : "red";

      letterCheck.textContent = (/[a-zA-Z]/.test(value)) ? "✅ At least one alphabet (A-Z or a-z)" : "❌ At least one alphabet (A-Z or a-z)";
      letterCheck.style.color = (/[a-zA-Z]/.test(value)) ? "green" : "red";

      numberCheck.textContent = (/\d/.test(value)) ? "✅ At least one number (0-9)" : "❌ At least one number (0-9)";
      numberCheck.style.color = (/\d/.test(value)) ? "green" : "red";

      specialCheck.textContent = (/[!@#$%^&*(),.?":{}|<>]/.test(value)) ? "✅ At least one special character (!@#$%^&*)" : "❌ At least one special character (!@#$%^&*)";
      specialCheck.style.color = (/[!@#$%^&*(),.?":{}|<>]/.test(value)) ? "green" : "red";
    });

    const passwordField = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    togglePassword.addEventListener("click", () => {
      const isHidden = passwordField.type === "password";
      passwordField.type = isHidden ? "text" : "password";
      togglePassword.textContent = isHidden ? "Hide" : "Show";
    });
  </script>
</body>

</html>