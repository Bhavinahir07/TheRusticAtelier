<?php
require_once __DIR__ . "/config/init.php";

// --- 1. Authentication and User ID ---
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user']['id'];

// --- 2. Get the Specific Address ID from the URL ---
$address_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$address_id) {
    $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Invalid address link.'];
    header("Location: show_address.php");
    exit;
}

// --- 3. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate all inputs from the form
    $receiver_name = trim($_POST['receiver_name'] ?? '');
    $building_name = trim($_POST['building_name'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $alternate_phone = trim($_POST['alternate_phone'] ?? '');

    // Basic validation
    $errors = [];
    if (empty($receiver_name)) $errors['receiver_name'] = 'Receiver name is required.';
    if (empty($building_name)) $errors['building_name'] = 'Building/House info is required.';
    if (empty($phone)) $errors['phone'] = 'A primary phone number is required.';

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: edit_address.php?id=" . $address_id);
        exit;
    }

    // --- THE FIX IS HERE ---
    // The query now correctly updates the 'receiver_name' column instead of 'username'.
    $stmt = $conn->prepare(
        "UPDATE addresses 
         SET receiver_name = ?, building_name = ?, landmark = ?, phone = ?, alternate_phone = ? 
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$receiver_name, $building_name, $landmark, $phone, $alternate_phone, $address_id, $user_id]);

    $_SESSION['feedback'] = ['type' => 'success', 'text' => 'Address updated successfully.'];
    header("Location: show_address.php");
    exit;
}


// --- 4. Fetch Address for Display (GET Request) ---
$stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$address_id, $user_id]);
$address_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$address_data) {
    $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Address not found.'];
    header("Location: show_address.php");
    exit;
}

// --- 5. Use a Consistent Variable for Display ---
$errors = $_SESSION['errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
$display_data = $old_input ?: $address_data;
unset($_SESSION['errors'], $_SESSION['old_input']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Address</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .form-input.error { border-color: #ef4444; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-lg p-8 space-y-6 bg-white rounded-xl shadow-lg">
    <div>
        <h1 class="text-3xl font-bold text-gray-800 text-center">Edit Your Address</h1>
        <p class="text-center text-gray-500 mt-2">Update the details for your delivery location.</p>
    </div>

    <form method="POST" action="edit_address.php?id=<?= $address_id ?>" class="space-y-4" novalidate>
        
        <div>
            <label for="receiver_name" class="block text-sm font-medium text-gray-700">Receiver's Name</label>
            <!-- --- THE FIX IS HERE --- -->
            <!-- The 'value' now correctly uses the 'receiver_name' column from your table. -->
            <input type="text" id="receiver_name" name="receiver_name"
                   class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['receiver_name']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($display_data['receiver_name'] ?? '') ?>" required>
            <?php if (isset($errors['receiver_name'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= $errors['receiver_name'] ?></p>
            <?php endif; ?>
        </div>
        
        <div>
            <label for="building_name" class="block text-sm font-medium text-gray-700">Building/House No., Street Name</label>
            <input type="text" id="building_name" name="building_name"
                   class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['building_name']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($display_data['building_name'] ?? '') ?>" required>
            <?php if (isset($errors['building_name'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= $errors['building_name'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="landmark" class="block text-sm font-medium text-gray-700">Landmark (Optional)</label>
            <input type="text" id="landmark" name="landmark"
                   class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   value="<?= htmlspecialchars($display_data['landmark'] ?? '') ?>">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="phone" name="phone" maxlength="10"
                       class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['phone']) ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($display_data['phone'] ?? '') ?>" required>
                 <?php if (isset($errors['phone'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['phone'] ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label for="alternate_phone" class="block text-sm font-medium text-gray-700">Alternate Phone (Optional)</label>
                <input type="tel" id="alternate_phone" name="alternate_phone" maxlength="10"
                       class="form-input mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       value="<?= htmlspecialchars($display_data['alternate_phone'] ?? '') ?>">
            </div>
        </div>

        <div class="pt-4 flex items-center space-x-4">
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i> Update Address
            </button>
            <a href="show_address.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
        </div>
    </form>
</div>

</body>
</html>

