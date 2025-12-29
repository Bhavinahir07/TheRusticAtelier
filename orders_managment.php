<?php
session_start();
// Make sure you connect to your database
require_once __DIR__ . "/config/init.php";

// --- Optional but Recommended: Admin-only Access ---
// This check ensures only users with the role 'admin' can see this page.
// if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
//     // Redirect non-admin users to the homepage
//     header("Location: index.php");
//     exit;
// }

// --- Handle Status Update Logic (when form is submitted) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';
    // A list of allowed statuses to prevent errors
    $valid_statuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if ($order_id && in_array($new_status, $valid_statuses)) {
        try {
            // Update the 'status' column in your 'orders' table
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            // Create a success message to show the admin
            $_SESSION['feedback'] = ['type' => 'success', 'text' => "Order #$order_id status has been updated to '$new_status'."];
        } catch (Exception $e) {
            $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Failed to update order status.'];
            // Log the detailed error for yourself to see, not for the user
            error_log("Order update failed: " . $e->getMessage());
        }
    }
    // Redirect back to this same page to show the updated list and the feedback message
    header("Location: admin_orders.php");
    exit;
}


// --- Fetch All Orders to Display on the Page ---
// This query joins the 'orders' and 'users' tables to show the customer's name with each order.
$stmt = $conn->prepare("
    SELECT o.*, u.username 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the feedback message from the session if it exists
$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']); // Clear the message so it doesn't show again
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
         @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Order Management Dashboard</h1>
    
    <!-- Feedback Alert -->
    <?php if ($feedback): ?>
        <div class="mb-6 p-4 rounded-lg text-sm <?= $feedback['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <?= htmlspecialchars($feedback['text']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Order ID</th>
                        <th scope="col" class="px-6 py-3">Customer</th>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3">Total</th>
                        <th scope="col" class="px-6 py-3">Current Status</th>
                        <th scope="col" class="px-6 py-3">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            #<?= $order['id'] ?>
                        </th>
                        <td class="px-6 py-4"><?= htmlspecialchars($order['username']) ?></td>
                        <td class="px-6 py-4"><?= date("d M Y, H:i", strtotime($order['created_at'])) ?></td>
                        <td class="px-6 py-4">₹<?= number_format($order['total_price'], 2) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $order['status'] === 'Delivered' ? 'bg-green-100 text-green-800' : '' ?>
                                <?= $order['status'] === 'Shipped' ? 'bg-blue-100 text-blue-800' : '' ?>
                                <?= $order['status'] === 'Processing' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $order['status'] === 'Cancelled' ? 'bg-red-100 text-red-800' : '' ?>
                            ">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" class="flex items-center gap-2">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                    <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-4 py-2">
                                    Update
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
