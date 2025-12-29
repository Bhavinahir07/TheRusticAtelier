<?php
session_start();

// Optional: clear cart after successful order (session-based fallbacks)
unset($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed - MyShop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --orange-500: #f59e0b;
      --orange-600: #ea580c;
      --bg: #f3f4f6;
      --text: #111827;
      --muted: #6b7280;
      --black: #111111;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .card {
      width: min(560px, 92vw);
      background: #ffffff;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      padding: 36px 28px;
      text-align: center;
      animation: fadeInUp 500ms ease;
    }

    .badge {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      margin: 0 auto 16px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, var(--orange-500), var(--orange-600));
      color: #fff;
      font-size: 44px;
      font-weight: 700;
      box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35);
      animation: pop 500ms ease both;
    }

    h1 {
      font-size: 26px;
      margin: 6px 0 8px;
      font-weight: 700;
    }

    .lead {
      font-size: 16px;
      color: var(--muted);
      margin: 0 0 18px;
      line-height: 1.6;
    }

    .highlight {
      color: var(--orange-600);
      font-weight: 600;
    }

    .note {
      font-size: 13px;
      color: #6b7280;
      background: #f9fafb;
      border: 1px solid #f3f4f6;
      padding: 10px 12px;
      border-radius: 10px;
      margin: 0 auto 18px;
    }

    .actions {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 6px;
    }

    .btn {
      display: inline-block;
      padding: 12px 16px;
      border-radius: 10px;
      font-weight: 700;
      text-decoration: none;
      font-size: 14px;
      transition: transform 0.15s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .btn:active { transform: translateY(1px); }

    .btn-primary {
      color: #fff;
      background: linear-gradient(90deg, var(--orange-500), var(--orange-600));
      box-shadow: 0 6px 14px rgba(245, 158, 11, 0.35);
    }
    .btn-primary:hover { opacity: 0.95; }

    .btn-dark {
      color: #fff;
      background: var(--black);
    }
    .btn-dark:hover { opacity: 0.92; }

    @keyframes fadeInUp {
      from { transform: translateY(16px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    @keyframes pop {
      from { transform: scale(0.6); }
      to { transform: scale(1); }
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="badge">✓</div>
    <h1>Order Confirmed</h1>
    <p class="lead">
      Thank you for your purchase! Your <span class="highlight">ingredient order</span> has been received.
      We’re preparing your items for dispatch.
    </p>
    <div class="note">Tip: Store perishables properly upon delivery to keep ingredients fresh.</div>
    <div class="actions">
      <a href="product.php" class="btn btn-primary">Browse More Ingredients</a>
      <a href="index.php" class="btn btn-dark">Back to Home</a>
    </div>
  </div>

</body>
</html>
