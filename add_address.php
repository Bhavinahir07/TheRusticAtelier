<?php
// session_start();
// require 'db.php';
require_once __DIR__ . "/config/init.php";

$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
  header("Location: login.php");
  exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $receiver = $_POST['receiver_name'] ?? '';
  $building = $_POST['building_name'] ?? '';
  $landmark = $_POST['landmark'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $alternate = $_POST['alternate_phone'] ?? '';

  // 👉 Check if this is the first address for this user
  $stmt = $conn->prepare("SELECT COUNT(*) FROM addresses WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $count = $stmt->fetchColumn();

  // If no address exists → mark this one as default
  $is_default = ($count == 0) ? 1 : 0;
  // Insert into new table addresses
  $stmt = $conn->prepare("INSERT INTO addresses (user_id, receiver_name,building_name, landmark, phone, alternate_phone,is_default) 
                          VALUES (?, ?, ?, ?, ?,?,?)");
  $stmt->execute([$user_id, $receiver, $building, $landmark, $phone, $alternate, $is_default]);


  header("Location: show_address.php");
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Address</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .form-input.error {
            border-color: #ef4444; /* red-500 */
        }
    </style>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/add_address.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-lg p-8 space-y-6 bg-white rounded-xl shadow-lg">
    <div>
        <h1 class="text-3xl font-bold text-gray-800 text-center">Add a New Address</h1>
        <p class="text-center text-gray-500 mt-2">Enter the details for your new delivery location.</p>
    </div>

    <form method="POST" action="add_address.php" class="space-y-4" novalidate>
        <div>
            <label for="receiver_name" class="block text-sm font-medium text-gray-700">Receiver's Name</label>
            <input type="text" id="receiver_name" name="receiver_name"
                   class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['receiver_name']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($old_input['receiver_name'] ?? '') ?>" required>
            <?php if (isset($errors['receiver_name'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= $errors['receiver_name'] ?></p>
            <?php endif; ?>
        </div>
        
        <div>
            <label for="building_name" class="block text-sm font-medium text-gray-700">Building/House No., Street Name</label>
            <input type="text" id="building_name" name="building_name"
                   class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['building_name']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($old_input['building_name'] ?? '') ?>" required>
            <?php if (isset($errors['building_name'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= $errors['building_name'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="landmark" class="block text-sm font-medium text-gray-700">Landmark (Optional)</label>
            <input type="text" id="landmark" name="landmark"
                   class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   value="<?= htmlspecialchars($old_input['landmark'] ?? '') ?>">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="phone" name="phone" maxlength="10"
                       class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['phone']) ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($old_input['phone'] ?? '') ?>" required>
                 <?php if (isset($errors['phone'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['phone'] ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label for="alternate_phone" class="block text-sm font-medium text-gray-700">Alternate Phone (Optional)</label>
                <input type="tel" id="alternate_phone" name="alternate_phone" maxlength="10"
                       class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['alternate_phone']) ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($old_input['alternate_phone'] ?? '') ?>">
                <?php if (isset($errors['alternate_phone'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['alternate_phone'] ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="pt-4 flex items-center space-x-4">
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i> Save Address
            </button>
            <a href="show_address.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
        </div>
    </form>
</div>

</body>
</html>
