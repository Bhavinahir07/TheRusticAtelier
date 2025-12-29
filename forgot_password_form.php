<?php
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
    require_once __DIR__ . "/config/init.php";
}

// Define message variables safely
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';

// Clear messages after showing
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f9f9f9, #e3f2fd);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .forgot-container {
      background: #fff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 90%;
      max-width: 400px;
      transition: transform 0.3s ease;
    }

    .forgot-container:hover {
      transform: scale(1.02);
    }

    .forgot-container h2 {
      margin-bottom: 25px;
      font-size: 24px;
      color: #1976d2;
      font-weight: bold;
    }

    input[type="email"] {
      width: 100%;
      padding: 14px 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 16px;
      transition: border-color 0.3s ease;
    }

    input[type="email"]:focus {
      border-color: #1976d2;
      outline: none;
      box-shadow: 0 0 5px rgba(25, 118, 210, 0.3);
    }

    button {
      width: 100%;
      background: #1976d2;
      color: white;
      border: none;
      padding: 14px;
      font-size: 16px;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #0d47a1;
    }

    .message {
      margin-top: 10px;
      font-size: 14px;
    }

    .error {
      color: red;
    }

    .success {
      color: green;
    }
  </style>
</head>
<body>

  <div class="forgot-container">
    <h2>Forgot Password</h2>

    <?php if (!empty($error)): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="forgot_password.php">
      <input type="email" name="email" placeholder="Enter your registered email" required>
      <button type="submit">Send OTP</button>
    </form>
  </div>

</body>
</html>
