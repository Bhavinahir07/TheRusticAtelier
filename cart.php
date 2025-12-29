<?php
// session_start();
// require 'db.php';
require_once __DIR__ . "/config/init.php";

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch cart
$stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

$items = [];
$total = 0;

if ($cart) {
  $cart_id = $cart['id'];
  $stmt = $conn->prepare("
        SELECT ci.id as cartitem_id, p.id as product_id, p.name, p.price, p.image, ci.quantity
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
  $stmt->execute([$cart_id]);
  $items = $stmt->fetchAll();

  foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Shopping Cart</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f3f4f6;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
    }

    .cart-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #eee;
      padding: 20px 0;
    }

    .cart-item img {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 10px;
    }

    .item-info {
      flex: 1;
      margin-left: 20px;
    }

    .item-info h4 {
      margin: 0;
      font-size: 18px;
      color: #333;
    }

    .item-info p {
      margin-top: 5px;
      font-size: 14px;
      color: #666;
    }

    .qty-controls {
      display: flex;
      align-items: center;
      gap: 6px;
      background: #f1f1f1;
      padding: 5px 10px;
      border-radius: 20px;
    }

    .qty-controls form {
      display: inline;
    }

    .qty-btn {
      background: #ddd;
      border: none;
      border-radius: 50%;
      width: 28px;
      height: 28px;
      font-size: 18px;
      color: #333;
      cursor: pointer;
      font-weight: bold;
    }

    .qty-number {
      font-size: 16px;
      padding: 0 10px;
      font-weight: bold;
      margin: 0 8px; /* space between buttons and number */
      min-width: 28px; /* consistent width */
      text-align: center; /* center digits */
      display: inline-block;
    }

    .remove-btn {
      background: transparent;
      border: none;
      color: #e74c3c;
      font-size: 18px;
      cursor: pointer;
      margin-left: 10px;
    }

    .remove-btn:hover {
      transform: scale(1.2);
    }

    .cart-summary {
      margin-top: 25px;
      text-align: right;
    }

    .cart-summary h3 {
      font-size: 20px;
      color: #444;
    }

    .pay-btn {
      width: 100%;
      margin-top: 15px;
      background: linear-gradient(to right, #f39c12, #e67e22);
      color: white;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .pay-btn:hover {
      background: linear-gradient(to right, #e67e22, #d35400);
    }

    /* Minimal additions for navigation buttons */
    .actions-top { text-align: right; margin-bottom: 10px; }
    .btn-home { display:inline-block; background:#222; color:#fff; padding:8px 12px; font-size:14px; border-radius:8px; text-decoration:none; font-weight:600; }
    .btn-home:hover { background:#000; }
    .empty-actions { display:flex; justify-content:center; gap:12px; margin-top:16px; flex-wrap:wrap; }
    .btn-shop { display:inline-block; background:linear-gradient(to right, #f39c12, #e67e22); color:#fff; padding:10px 14px; font-size:14px; border-radius:8px; text-decoration:none; font-weight:600; }
    .btn-shop:hover { background:linear-gradient(to right, #e67e22, #d35400); }

    .empty-message {
      text-align: center;
      color: #888;
      font-size: 18px;
      margin-top: 40px;
    }

    @media (max-width: 600px) {
      .cart-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .item-info {
        margin: 10px 0 0 0;
      }

      .qty-controls {
        margin-top: 10px;
      }
    }
  </style>
</head>

<body>

  <div class="container">
    <h2>Your Shopping Cart</h2>

    <?php if (!empty($items)): ?>
      <div class="actions-top">
        <a href="index.php" class="btn-home"><i class="fas fa-home"></i> Back to Home</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
      <?php foreach ($items as $item): ?>
        <div class="cart-item">
          <img src="<?= $item['image'] ?: 'images/placeholder.png' ?>" alt="<?= htmlspecialchars($item['name']) ?>">

          <div class="item-info">
            <h4><?= htmlspecialchars($item['name']) ?></h4>
            <p>
              ₹<?= number_format($item['price'], 2) ?>
              × <?= $item['quantity'] ?> =
              <strong>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
            </p>

            <div class="qty-controls">
              <form method="post" action="decrease_cart_item.php">
                <input type="hidden" name="item_id" value="<?= $item['cartitem_id'] ?>">
                <button class="qty-btn">−</button>
              </form>

              <span class="qty-number"><?= $item['quantity'] ?></span>

              <form method="post" action="increase_cart_item.php">
                <input type="hidden" name="item_id" value="<?= $item['cartitem_id'] ?>">
                <button class="qty-btn">+</button>
              </form>
            </div>
          </div>

          <form method="post" action="remove_cart_item.php">
            <input type="hidden" name="item_id" value="<?= $item['cartitem_id'] ?>">
            <button class="remove-btn" title="Remove item">
              <i class="fas fa-trash-alt"></i>
            </button>
          </form>
        </div>
      <?php endforeach; ?>

      <div class="cart-summary">
        <h3>Total: ₹<?= number_format($total, 2) ?></h3>
        <a href="payment.php" class="pay-btn">Proceed to Payment</a>
      </div>

    <?php else: ?>
      <p class="empty-message">Your cart is empty.</p>
      <div class="empty-actions">
        <a href="product.php" class="btn-shop"><i class="fas fa-shopping-bag"></i> Browse Products</a>
        <a href="index.php" class="btn-home"><i class="fas fa-home"></i> Back to Home</a>
      </div>
    <?php endif; ?>
  </div>

</body>

</html>