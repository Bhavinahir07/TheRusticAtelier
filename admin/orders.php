<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/orders.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$adminName = $_SESSION['user']['username'] ?? 'Admin';

// FIX: Define the valid statuses array OUTSIDE the 'if' block
// This makes it available to the HTML below, even on a GET request.
// Also removed 'Inactive' to match your database.
$valid_statuses = [
  'Pending',
  'Processed',
  'Shipped',
  'Delivered',
  'deactivated_by_user',
  'deactivated_by_admin'
];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
  $new_status = trim($_POST['status'] ?? '');

  if ($order_id && in_array($new_status, $valid_statuses, true)) {
    try {
      $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
      $stmt->execute([$new_status, $order_id]);
      $_SESSION['feedback'] = ['type' => 'success', 'text' => "Order #$order_id updated to '$new_status'."];
    } catch (Throwable $e) {
      $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Failed to update order status.'];
      error_log('Order status update failed: ' . $e->getMessage());
    }
  }
  header('Location: ' . BASE_URL . 'admin/orders.php');
  exit;
}

// Fetch orders (join users)
try {
  $stmt = $conn->prepare('SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC');
  $stmt->execute();
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $orders = [];
}

$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Orders • Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    :root {
      --bg: #0b0f19;
      --panel: #0f172a;
      --muted: #9aa4b2;
      --text: #e6e9ef;
      --primary: #7c3aed;
      --primary2: #8b5cf6;
      --accent: #22d3ee;
      --border: rgba(255, 255, 255, .08);
      --danger: #ef4444;
      --success: #22c55e
    }

    * {
      box-sizing: border-box
    }

    body.admin-shell {
      margin: 0;
      background: radial-gradient(1200px 600px at 20% -10%, rgba(124, 58, 237, .18), transparent), radial-gradient(1000px 500px at 100% 0%, rgba(34, 211, 238, .14), transparent), linear-gradient(180deg, #0b0f19 0%, #0f172a 100%);
      color: var(--text);
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif
    }

    a {
      text-decoration: none;
      color: inherit
    }

    /* Topbar (unified with other admin pages) */
    .admin-topbar {
      position: sticky;
      top: 0;
      left: 0;
      right: 0;
      background: linear-gradient(180deg, #0b0f19, #0f172a);
      border-bottom: 1px solid var(--border);
      padding: 14px 18px;
      z-index: 2
    }

    .admin-topbar__inner {
      display: flex;
      align-items: center;
      gap: 12px
    }

    .page-title {
      font-weight: 800;
      font-size: 1.6rem
    }

    .admin-identity {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .admin-badge {
      background: rgba(124, 58, 237, .18);
      border: 1px solid rgba(124, 58, 237, .35);
      color: #c4b5fd;
      padding: 2px 8px;
      border-radius: 9999px;
      font-weight: 800
    }

    .admin-user span {
      color: var(--muted)
    }

    .admin-content {
      margin-left: 0;
      padding: 18px
    }

    .orders-page {
      max-width: 1100px;
      margin: 0 auto
    }

    .orders-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 10px 0 16px
    }

    .head-actions {
      display: flex;
      gap: 10px
    }

    .btn-soft {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 12px;
      padding: 9px 12px;
      font-weight: 700;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, .06);
      color: var(--text)
    }

    .btn-soft:hover {
      background: rgba(255, 255, 255, .1)
    }

    .table-wrap {
      background: linear-gradient(180deg, #0d1424, #0c1220);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 24px 60px rgba(2, 6, 23, .55);
      overflow: hidden
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0
    }

    thead {
      background: rgba(255, 255, 255, .04)
    }

    th,
    td {
      padding: 12px 14px;
      text-align: left
    }

    th {
      font-size: .9rem;
      color: #cdd5df
    }

    tbody tr {
      border-top: 1px solid var(--border)
    }

    tbody tr:hover {
      background: rgba(255, 255, 255, .03)
    }

    .muted {
      color: var(--muted)
    }

    .status-pill {
      padding: 4px 8px;
      border-radius: 9999px;
      font-size: .8rem;
      border: 1px solid var(--border);
      display: inline-block
    }

    .st-processing {
      background: rgba(245, 158, 11, .12);
      border-color: rgba(245, 158, 11, .35);
      color: #fde68a
    }

    .st-packed {
      background: rgba(59, 130, 246, .12);
      border-color: rgba(59, 130, 246, .35);
      color: #bfdbfe
    }

    .st-shipped {
      background: rgba(59, 130, 246, .12);
      border-color: rgba(59, 130, 246, .35);
      color: #bfdbfe
    }

    .st-out {
      background: rgba(99, 102, 241, .12);
      border-color: rgba(99, 102, 241, .35);
      color: #c7d2fe
    }

    .st-delivered {
      background: rgba(34, 197, 94, .12);
      border-color: rgba(34, 197, 94, .35);
      color: #bbf7d0
    }

    .st-cancelled {
      background: rgba(239, 68, 68, .12);
      border-color: rgba(239, 68, 68, .35);
      color: #fecaca
    }

    .st-refunded {
      background: rgba(234, 179, 8, .12);
      border-color: rgba(234, 179, 8, .35);
      color: #fde68a
    }

    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 12px;
      padding: 8px 12px;
      font-weight: 700;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, .05);
      color: var(--text)
    }

    .btn:hover {
      background: rgba(255, 255, 255, .08)
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--primary2));
      border-color: transparent;
      color: #fff;
      border-radius: 12px
    }

    .select {
      background: rgba(255, 255, 255, .06);
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: 10px;
      padding: 8px;
      color-scheme: dark;
      /* For background color of dropdown of order status */
    }

    /* ADD THIS ENTIRE BLOCK */
    .select option {
      background: var(--panel);
      color: var(--text);
      padding: 8px;
      /* Optional: for better spacing */
    }

    .feedback {
      margin: 12px 0;
      padding: 12px;
      border-radius: 12px
    }

    .ok {
      background: rgba(34, 197, 94, .12);
      border: 1px solid rgba(34, 197, 94, .35);
      color: #bbf7d0
    }

    .err {
      background: rgba(239, 68, 68, .12);
      border: 1px solid rgba(239, 68, 68, .35);
      color: #fecaca
    }
  </style>
</head>

<body class="admin-shell">

  <!-- Topbar -->
  <header class="admin-topbar">
    <div class="admin-topbar__inner">
      <div class="page-title" id="pageTitle">Manage Orders</div>
      <div class="admin-identity" title="You are in the Admin area">
        <span class="admin-badge">ADMIN</span>
        <div class="admin-user"><i class="fa-solid fa-user-shield"></i><span><?= htmlspecialchars($adminName) ?></span></div>
      </div>
    </div>
  </header>

  <main class="admin-content">
    <section class="orders-page">

      <div class="page-head" style="display:flex;align-items:center;justify-content:space-between;margin:10px 0 16px">
        <div>
          <div class="title" style="font-size:1.6rem;font-weight:800">Manage Orders</div>
          <div class="sub" style="color:var(--muted)">View and process customer orders</div>
        </div>
        <div class="head-actions" style="display:flex;gap:10px">
          <a href="<?= BASE_URL ?>admin/index.php" class="btn-soft"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back</a>
          <a href="<?= BASE_URL ?>logout.php" class="btn-soft"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
        </div>
      </div>

      <?php if ($feedback): ?>
        <div class="feedback <?= $feedback['type'] === 'success' ? 'ok' : 'err' ?> flash-card" data-flash>
          <?= htmlspecialchars($feedback['text']) ?>
        </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($orders)): ?>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td>#<?= (int)$o['id'] ?></td>
                  <td><?= htmlspecialchars($o['username']) ?></td>
                  <td class="muted"><?= htmlspecialchars(date('d M Y, H:i', strtotime($o['created_at']))) ?></td>
                  <td>₹<?= htmlspecialchars(number_format((float)($o['total_price'] ?? 0), 2)) ?></td>
                  <td class="muted"><?= htmlspecialchars($o['payment_method'] ?? '—') ?></td>
                  <td>
                    <?php
                    // $st = (string)($o['status'] ?? 'Processing');
                    // $cls = [
                    //   'Processing' => 'st-processing',
                    //   'Packed' => 'st-packed',
                    //   'Shipped' => 'st-shipped',
                    //   'Out for Delivery' => 'st-out',
                    //   'Delivered' => 'st-delivered',
                    //   'Cancelled' => 'st-cancelled',
                    //   'Refunded' => 'st-refunded'
                    // ][$st] ?? 'st-processing';
                    $st = (string)($o['status'] ?? 'Pending'); // Default to Pending
                    $cls = [
                      'Pending'    => 'st-processing', // Yellow
                      'Processed'  => 'st-packed',     // Blue
                      'Shipped'    => 'st-shipped',    // Blue
                      'Delivered'  => 'st-delivered',  // Green
                      'Inactive'   => 'st-cancelled',   // Red
                      'deactivated_by_user'  => 'st-cancelled',  // For user's user deactivation
                      'deactivated_by_admin' => 'st-cancelled'   // <-- For admin's user deactivation
                    ][$st] ?? 'st-processing'; // Default to yellow
                    ?>
                    <span class="status-pill <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                  </td>
                  <td>
                    <form method="post" class="actions">
                      <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>" />
                      <select name="status" class="select">
                        <?php foreach ($valid_statuses as $opt): ?>
                          <option value="<?= $opt ?>" <?= $st === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" name="update_status" class="btn-primary"><i class="fa-solid fa-rotate"></i>&nbsp;Update</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="muted" style="text-align:center;padding:22px">No orders found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </section>
  </main>

</body>

</html>