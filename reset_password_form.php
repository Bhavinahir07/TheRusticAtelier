<?php
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
    require_once __DIR__ . "/config/init.php";
}

// Handle optional messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password</title>
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

    .reset-container {
      background: #fff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 90%;
      max-width: 400px;
    }

    .reset-container h2 {
      margin-bottom: 25px;
      font-size: 24px;
      /* color: #1976d2; */
      color: black;
      font-weight: bold;
    }

    input[type="password"] {
      width: 100%;
      padding: 14px 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 16px;
    }

    input[type="password"]:focus {
      border-color: #1976d2;
      outline: none;
      box-shadow: 0 0 5px rgba(25, 118, 210, 0.3);
    }

    button {
      width: 100%;
      /* background: #1976d2; */
      background: black;
      color: white;
      border: none;
      padding: 14px;
      font-size: 16px;
      border-radius: 10px;
      cursor: pointer;
    }

    button:hover {
      /* background: #0d47a1; */
      background: linear-gradient(to right, #ff5e62, #ff9966);
    }

    .message {
      margin-bottom: 15px;
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

  <div class="reset-container">
    <h2>Reset Your Password</h2>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="reset_password.php">
      <input type="password" name="new_password" placeholder="New Password" required />
      <input type="password" name="confirm_password" placeholder="Confirm Password" required />
      <button type="submit">Reset Password</button>
    </form>
  </div>

</body>
</html>
