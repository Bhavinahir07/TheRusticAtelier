<?php
require_once __DIR__ . "/config/init.php";

// --- User Authentication ---
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user']['id'];

// --- Corrected Query to Fetch REAL Status ---
// This query now fetches the live 'status' column from your database.
$stmt = $conn->prepare("
    SELECT 
        o.id AS order_id,
        o.total_price,
        o.payment_method,
        o.created_at,
        o.status, -- This is the REAL status
        oi.quantity,
        p.name AS product_name,
        p.price AS product_price,
        p.image AS product_image,
        p.id AS product_id -- Fetch product ID for 'Buy Again' link
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND o.status != 'Inactive'
    ORDER BY o.created_at DESC, o.id ASC
");
$stmt->execute([$user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Process Results into a Structured Array ---
$orders = [];
foreach ($results as $row) {
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        // The old simulation logic is now completely removed.
        $orders[$order_id] = [
            'total_price'    => $row['total_price'],
            'payment_method' => $row['payment_method'],
            'created_at'     => $row['created_at'],
            'status'         => $row['status'], // Using the real status
            'items'          => [],
        ];
    }
    $orders[$order_id]['items'][] = [
        'product_id' => $row['product_id'],
        'name'       => $row['product_name'],
        'price'      => $row['product_price'],
        'image'      => $row['product_image'],
        'quantity'   => $row['quantity'],
    ];
}

// Function to determine CSS classes for the status tracker
function getStatusClass($stepStatus, $currentStatus)
{
    // This list is our "master" timeline, including the hidden 'Pending'
    $statuses = ['Pending', 'Processed', 'Shipped', 'Delivered'];

    // Find the numerical index for the step we are rendering 
    // (e.g., 'Processed' is 1)
    $currentStepIndex = array_search($stepStatus, $statuses);

    // Find the numerical index for the order's actual status 
    // (e.g., 'Shipped' is 2)
    $activeStepIndex = array_search($currentStatus, $statuses);

    // If the order's status is 'Pending' (index 0) or not found, 
    // no steps are complete.
    if ($activeStepIndex === false || $activeStepIndex === 0) {
        return ''; // Return no class, so it stays grey
    }

    // If the step we are rendering (e.g., 'Processed' at 1) is
    // less than or equal to the order's status (e.g., 'Shipped' at 2),
    // then mark it as 'is-complete' (green).
    if ($currentStepIndex <= $activeStepIndex) {
        return 'is-complete';
    }

    // Otherwise, this step has not been reached yet.
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">
    <script src="public/js/script.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        /* Match my_recipe_view.php header */
        header .site-name {
            margin: 0;
            padding: 0;
            text-align: left;
        }

        header .site-name i {
            display: inline-block;
            transform: translateY(-10%);
        }

        header .header-row {
            padding-top: 0.46rem;
            padding-bottom: 0.46rem;
        }

        header {
            box-shadow: none !important;
        }

        .c-stepper {
            display: flex;
        }

        .c-stepper__item {
            display: flex;
            flex-direction: column;
            flex: 1;
            text-align: center;
        }

        .c-stepper__item:before {
            content: '';
            display: block;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            border: 2px solid #e0e0e0;
            background-color: #fff;
            margin: 0 auto 0.75rem;
            position: relative;
            z-index: 1;
        }

        .c-stepper__title {
            font-weight: 500;
            font-size: 0.875rem;
            color: #757575;
        }

        .c-stepper__item:not(:last-child):after {
            content: '';
            position: relative;
            top: 1.25rem;
            width: calc(100% - 2.5rem);
            left: 50%;
            height: 2px;
            background-color: #e0e0e0;
            order: -1;
        }

        .c-stepper__item.is-complete:before {
            border-color: #16a34a;
            background-color: #16a34a;
        }

        /* green-600 */
        .c-stepper__item.is-complete .c-stepper__title {
            color: #16a34a;
        }

        .c-stepper__item.is-complete:after {
            background-color: #16a34a;
        }

        /* .c-stepper__item.is-active:before {
            border-color: #2563eb;
            background-color: #fff;
            border-width: 4px;
        } */

        /* blue-600 */
        /* .c-stepper__item.is-active .c-stepper__title {
            color: #1e3a8a;
            font-weight: 600;
        } */
    </style>
</head>

<body class="bg-gray-100">

    <div class="menu-overlay"></div>
    <header class="bg-white border-b border-gray-200">
        <div class="w-full px-0">
            <div class="flex justify-between items-center header-row">
                <h1 class="site-name flex items-center gap-2">
                    <i class="fas fa-utensils"></i>
                    TheRusticAtelier
                </h1>
                <a href="user_profile.php" class="text-sm font-medium text-gray-600 hover:text-black flex items-center">
                    <i class="fas fa-user mr-2"></i> Back to Profile
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Your Order History</h1>

        <?php if (empty($orders)): ?>
            <div class="text-center bg-white p-12 rounded-lg shadow-md">
                <i class="fas fa-box-open text-6xl text-gray-300"></i>
                <h2 class="mt-4 text-2xl font-semibold text-gray-700">No Orders Yet</h2>
                <p class="text-gray-500 mt-2">Looks like you haven't placed any orders. Let's change that!</p>
                <a href="product.php" class="mt-6 inline-block bg-black text-white font-bold py-2 px-6 rounded-lg hover:bg-gray-700 transition-colors">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($orders as $order_id => $order): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Order Header -->
                        <div class="p-5 bg-gray-50 border-b border-gray-200 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                            <div class="text-gray-600">
                                <span class="font-semibold text-gray-800 block">ORDER #</span>
                                ORD-<?= $order_id ?>
                            </div>
                            <div class="text-gray-600">
                                <span class="font-semibold text-gray-800 block">DATE PLACED</span>
                                <?= date("M d, Y", strtotime($order['created_at'])) ?>
                            </div>
                            <div class="text-gray-600 col-span-2 sm:col-span-1 sm:text-right">
                                <span class="font-semibold text-gray-800 block">TOTAL</span>
                                <span class="font-bold text-lg">₹<?= number_format($order['total_price'], 2) ?></span>
                            </div>
                        </div>

                        <!-- Conditional Status Display -->
                        <div class="p-6">
                            <div class="c-stepper">
                                <div class="c-stepper__item <?= getStatusClass('Processed', $order['status']) ?>">
                                    <h3 class="c-stepper__title">Processed</h3>
                                </div>
                                <div class="c-stepper__item <?= getStatusClass('Shipped', $order['status']) ?>">
                                    <h3 class="c-stepper__title">Shipped</h3>
                                </div>
                                <div class="c-stepper__item <?= getStatusClass('Delivered', $order['status']) ?>">
                                    <h3 class="c-stepper__title">Delivered</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="p-6 border-t border-gray-200">
                            <div class="space-y-6">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="flex items-center space-x-4">
                                        <img src="<?= htmlspecialchars($item['image'] ?: 'https://placehold.co/100x100/e2e8f0/718096?text=No+Image') ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                            class="w-20 h-20 rounded-md object-cover flex-shrink-0 <?= $order['status'] === 'Cancelled' ? 'opacity-50' : '' ?>">
                                        <div class="flex-grow">
                                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                                            <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?> | ₹<?= number_format($item['price'], 2) ?> ea.</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-lg text-gray-800">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                                            <a href="product.php?id=<?= $item['product_id'] ?>" class="mt-1 inline-block text-sm font-medium text-blue-600 hover:text-blue-700">
                                                Buy Again <i class="fas fa-redo-alt ml-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>