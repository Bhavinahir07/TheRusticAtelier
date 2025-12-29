<?php
// show_address.php
require_once __DIR__ . "/config/init.php";

// --- User Authentication & Feedback Handling ---
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user']['id'];

// Check for feedback messages from previous actions
$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);

// --- Handle Actions (Set Default, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle 'Set as Default' action
    if (isset($_GET['set_default'])) {
        $address_id = intval($_GET['set_default']);
        try {
            $conn->beginTransaction();
            // Reset any existing default address for this user
            $stmt_reset = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt_reset->execute([$user_id]);
            // Set the new default address
            $stmt_set = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt_set->execute([$address_id, $user_id]);
            $conn->commit();
            $_SESSION['feedback'] = ['type' => 'success', 'text' => 'Address successfully set as default.'];
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Failed to set default address: " . $e->getMessage());
            $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Could not set default address. Please try again.'];
        }
        header("Location: show_address.php");
        exit;
    }

    // Handle 'Delete' action
    if (isset($_GET['delete'])) {
        $address_id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$address_id, $user_id])) {
            $_SESSION['feedback'] = ['type' => 'success', 'text' => 'Address successfully deleted.'];
        } else {
            $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Failed to delete address.'];
        }
        header("Location: show_address.php");
        exit;
    }
}

// --- Fetch Address Data ---
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no addresses exist, redirect to the 'add address' page
if (empty($addresses)) {
    // We pass the redirect_to parameter so add_address.php knows to send them back here
    header("Location: add_address.php?redirect_to=show_address.php");
    exit;
}

// Auto-set first address as default if none is set
$defaultExists = false;
foreach ($addresses as $addr) {
    if ($addr['is_default'] == 1) {
        $defaultExists = true;
        break;
    }
}
if (!$defaultExists) {
    $firstAddressId = $addresses[0]['id'];
    $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$firstAddressId, $user_id]);
    // Refresh the page to show the change
    header("Location: show_address.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>css/style.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>css/site-name.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>css/footer.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; }
        /* Match my_recipe_view.php */
        header .site-name { margin: 0; padding: 0; text-align: left; }
        header .site-name i { display: inline-block; transform: translateY(-10%); }
        header .header-row { padding-top: 0.46rem; padding-bottom: 0.46rem; }
        header { box-shadow: none !important; }
    </style>
</head>
<body class="bg-gray-100">

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
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Your Addresses</h1>

    <?php if ($feedback): ?>
        <div class="p-4 mb-6 rounded-lg text-sm <?= $feedback['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>" role="alert">
            <span class="font-medium"><?= htmlspecialchars($feedback['text']) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($addresses as $addr): ?>
            <div class="relative bg-white p-6 rounded-lg shadow-md border-2 <?= $addr['is_default'] ? 'border-blue-500' : 'border-gray-200' ?>">
                
                <?php if ($addr['is_default']): ?>
                    <span class="absolute top-4 right-4 bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full">DEFAULT</span>
                <?php endif; ?>

                <div class="flex items-start space-x-4">
                    <i class="fas fa-map-marker-alt text-xl text-gray-400 mt-1"></i>
                    <div>
                        <h4 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($addr['receiver_name']) ?></h4>
                        <p class="text-gray-600"><?= htmlspecialchars($addr['building_name']) ?></p>
                        <p class="text-gray-600"><?= htmlspecialchars($addr['landmark']) ?></p>
                        <p class="text-gray-600 mt-2"><i class="fas fa-phone-alt text-xs mr-2"></i><?= htmlspecialchars($addr['phone']) ?></p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 flex items-center space-x-3">
                    <a href="edit_address.php?id=<?= $addr['id'] ?>" class="text-sm text-blue-600 hover:underline">Edit</a>
                    <span class="text-gray-300">|</span>
                    <a href="?delete=<?= $addr['id'] ?>" class="text-sm text-red-600 hover:underline" onclick="return confirm('Are you sure you want to delete this address?')">Delete</a>
                    
                    <?php if (!$addr['is_default']): ?>
                        <span class="text-gray-300">|</span>
                        <a href="?set_default=<?= $addr['id'] ?>" class="text-sm text-green-600 hover:underline">Set as Default</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <a href="add_address.php?redirect_to=show_address.php" class="flex items-center justify-center bg-white p-6 rounded-lg shadow-md border-2 border-dashed border-gray-300 hover:border-blue-500 hover:text-blue-500 transition-all duration-200 text-gray-500">
            <div class="text-center">
                <i class="fas fa-plus-circle text-4xl"></i>
                <p class="mt-2 font-semibold">Add New Address</p>
            </div>
        </a>
    </div>

    <div class="mt-8 flex justify-end">
        <a href="payment.php" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 shadow-lg transition-colors duration-200">
            <i class="fas fa-shopping-cart mr-2"></i> Complete Checkout
        </a>
    </div>
    </div>

</body>
</html>