<?php
require_once __DIR__ . "/config/init.php";

// --- 1. Authentication Check ---
if (empty($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user']['id'];

// --- 2. Handle Profile Update (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // First, get the current user data for comparison
    $stmt_current = $conn->prepare("SELECT username, first_name, last_name, profile_image FROM users WHERE id = ?");
    $stmt_current->execute([$user_id]);
    $current_user_data = $stmt_current->fetch(PDO::FETCH_ASSOC);

    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $profile_image_path = $current_user_data['profile_image'] ?? null; // Start with the current image path

    // Basic validation
    $errors = [];
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    }

    // Image Upload Logic
    $new_image_uploaded = false;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/user_profile/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $file_info->file($_FILES['profile_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            $filename = 'user_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $new_image_uploaded = true;
                $old_image_path = $profile_image_path;
                $profile_image_path = $target_file; // Set the new path
                // Delete old image if it's not the default
                if (!empty($old_image_path) && !str_contains($old_image_path, 'download.png') && file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: user_profile.php#profile");
        exit;
    }

    // --- Check if any data has actually changed ---
    $has_changes = (
        $username !== $current_user_data['username'] ||
        $first_name !== $current_user_data['first_name'] ||
        $last_name !== $current_user_data['last_name'] ||
        $new_image_uploaded
    );

    if ($has_changes) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([$username, $first_name, $last_name, $profile_image_path, $user_id]);

        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['profile_image'] = $profile_image_path;
        $_SESSION['feedback'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
    } else {
        // If no changes, set an informational message
        $_SESSION['feedback'] = ['type' => 'info', 'text' => 'No changes were detected.'];
    }

    header("Location: user_profile.php");
    exit;
}

// --- 3. Handle Account Deletion ---
// --- 3. Handle Account Deletion (Soft Delete) ---
if (isset($_GET['delete_account']) && $_GET['delete_account'] === '1') {

    try {
        // We are already inside the user's session, so we use $user_id
        $conn->beginTransaction();

        // 1. Deactivate the user
        $stmt_user = $conn->prepare("UPDATE users SET account_status = 'deactivated_by_user' WHERE id = ?");
        $stmt_user->execute([$user_id]);

        // 2. Deactivate their shared recipes
        $stmt_recipes = $conn->prepare(
            "UPDATE shared_recipes SET status = 'deactivated_by_user' 
             WHERE user_id = ? AND status IN ('pending', 'approved', 'unpublished')"
        );
        $stmt_recipes->execute([$user_id]);

        // 3. Deactivate their active orders
        $stmt_orders = $conn->prepare(
            "UPDATE orders SET status = 'deactivated_by_user' 
             WHERE user_id = ? AND status IN ('Pending', 'Processed', 'Shipped')"
        );
        $stmt_orders->execute([$user_id]);

        // All queries succeeded, so commit the changes
        $conn->commit();
    } catch (Throwable $e) {
        // If any query failed, roll back all changes
        if ($conn->inTransaction()) {
            try {
                $conn->rollBack();
            } catch (Throwable $__) {
            }
        }
        // Log the error and stop
        error_log("Account deactivation failed for user $user_id: " . $e->getMessage());
        header("Location: user_profile.php?message=delete_failed"); // Redirect with an error
        exit;
    }

    // 4. Log the user out (this is the same as the original file)
    session_unset();
    session_destroy();
    header("Location: index.php?message=account_deleted"); // Redirect to home page
    exit;
}

// --- 4. Fetch Data for Display ---
$stmt = $conn->prepare("SELECT username, email, first_name, last_name, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_details = $stmt->fetch(PDO::FETCH_ASSOC);

// ✨ FIX: This is the new, smarter logic for the profile picture and initials
$profile_img_url = null;
$user_initial = '';
$avatar_color_class = '';
if (!empty($user_details['profile_image'])) {
    // If there IS a profile image, create the URL for it
    $profile_img_url = htmlspecialchars($user_details['profile_image']) . '?v=' . time();
} else {
    // If there is NO profile image, generate the initial and a color
    $display_name_for_initial = $user_details['first_name'] ?: $user_details['username'];
    $user_initial = strtoupper(substr($display_name_for_initial, 0, 1));
    $colors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-yellow-500', 'bg-indigo-500'];
    $hash = ord(substr($user_details['username'] ?? 'U', 0, 1));
    $avatar_color_class = $colors[$hash % count($colors)];
}


// Recipes moved to my_recipe_view.php

$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);
$errors = $_SESSION['errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

// Define feedback colors
$feedback_classes = [
    'success' => 'bg-green-100 text-green-800',
    'error'   => 'bg-red-100 text-red-800',
    'info'    => 'bg-orange-100 text-orange-800'
];
$feedback_class = isset($feedback['type']) ? $feedback_classes[$feedback['type']] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .tab-link.active {
            border-color: #f97316;
            color: #f97316;
            background-color: #fff7ed;
        }

        .tab-content {
            transition: opacity 0.3s ease-in-out;
        }

        /* Helper classes for Tailwind colors */
        .bg-red-500 {
            background-color: #ef4444;
        }

        .bg-blue-500 {
            background-color: #3b82f6;
        }

        .bg-green-500 {
            background-color: #22c55e;
        }

        .bg-purple-500 {
            background-color: #8b5cf6;
        }

        .bg-yellow-500 {
            background-color: #eab308;
        }

        .bg-indigo-500 {
            background-color: #6366f1;
        }

        /* Header tweaks */
        header .site-name {
            margin: 0;
            padding: 0;
            text-align: left;
        }

        header .site-name i {
            display: inline-block;
            transform: translateY(-10%);
        }

        /* ~8% smaller than 0.5rem (py-2) */
        header .header-row {
            padding-top: 0.46rem;
            padding-bottom: 0.46rem;
        }

        /* Remove any shadow */
        header {
            box-shadow: none !important;
        }
    </style>
</head>

<body class="bg-gray-50">

    <div class="menu-overlay"></div>
    <header class="bg-white border-b border-gray-200">
        <div class="w-full px-0">
            <div class="flex justify-between items-center header-row">
                <h1 class="site-name flex items-center gap-2">
                    <i class="fas fa-utensils"></i>
                    TheRusticAtelier
                </h1>
                <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-black flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Home
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
        <?php if ($feedback): ?>
            <div id="feedback-alert" class="mb-6 p-4 rounded-lg text-sm flex justify-between items-center <?= $feedback_class ?>">
                <span><?= htmlspecialchars($feedback['text']) ?></span>
                <button onclick="document.getElementById('feedback-alert').style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Quick Links (mirror header dropdown) -->
        <section class="mb-6">
            <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm">
                <div class="flex flex-wrap gap-2 sm:gap-3">
                    <a href="show_address.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium">
                        <i class="fas fa-map-marker-alt text-gray-500"></i> Address
                    </a>
                    <a href="my_orders.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium">
                        <i class="fas fa-box-open text-gray-500"></i> My Orders
                    </a>
                    <a href="my_recipe_view.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium">
                        <i class="fas fa-book-open text-gray-500"></i> My Recipes
                    </a>
                    <a href="logout.php" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <aside class="lg:col-span-1">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <ul class="space-y-1">
                        <li><a href="#profile" class="tab-link active flex items-center px-4 py-2 text-gray-700 font-medium rounded-md hover:bg-gray-100 border-l-4 border-transparent transition-colors">
                                <i class="fas fa-user-edit w-6 text-gray-400"></i> Edit Profile</a></li>

                        <li><a href="#settings" class="tab-link flex items-center px-4 py-2 text-gray-700 font-medium rounded-md hover:bg-gray-100 border-l-4 border-transparent transition-colors">
                                <i class="fas fa-cog w-6 text-gray-400"></i> Settings</a></li>
                    </ul>
                </div>
            </aside>

            <div class="lg:col-span-3">
                <div id="profile" class="tab-content bg-white p-8 rounded-lg shadow-sm">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Profile Information</h2>
                    <form method="POST" enctype="multipart/form-data" id="profile-form">
                        <div class="flex items-center space-x-6 mb-8">
                            <div id="avatar-container" class="w-24 h-24 rounded-full ring-4 ring-orange-100 flex items-center justify-center <?= $avatar_color_class ?>">
                                <?php if ($profile_img_url): ?>
                                    <img id="image-preview" src="<?= $profile_img_url ?>" alt="Profile Image" class="w-full h-full rounded-full object-cover">
                                <?php else: ?>
                                    <span id="initials-preview" class="text-4xl font-bold text-white"><?= $user_initial ?></span>
                                    <img id="image-preview" src="" alt="Profile Image" class="w-full h-full rounded-full object-cover hidden">
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="profile_image" class="cursor-pointer bg-black text-white font-bold py-2 px-4 rounded-lg hover:bg-gray-800 transition-colors">
                                    Change Photo
                                </label>
                                <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/*">
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG or GIF. 2MB max.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="sm:col-span-2">
                                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($old_input['username'] ?? $user_details['username'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                                <?php if (isset($errors['username'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= $errors['username'] ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($old_input['first_name'] ?? $user_details['first_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($old_input['last_name'] ?? $user_details['last_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                <p class="mt-1 text-gray-500 p-2 bg-gray-100 rounded-md"><?= htmlspecialchars($user_details['email']) ?></p>
                            </div>
                        </div>
                        <div class="text-right mt-8">
                            <button type="submit" name="update_profile" id="save-changes-btn" class="bg-black text-white font-bold py-2 px-6 rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>



                <!-- ✨ FIX: Settings / Danger Zone Content is now filled in -->
                <div id="settings" class="tab-content hidden bg-white p-8 rounded-lg shadow-sm">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Settings</h2>
                    <div class="p-4 border border-red-200 bg-red-50 rounded-lg">
                        <h3 class="font-bold text-red-800">Danger Zone</h3>
                        <p class="text-sm text-red-700 mt-1">Deleting your account is a permanent action and all your data, including shared recipes, will be lost.</p>
                        <div class="mt-4">
                            <button id="delete-account-btn" class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition-colors text-sm">Delete My Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Account</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">Are you sure you want to delete your account? This action is permanent and cannot be undone.</p>
                </div>
                <div class="items-center px-4 py-3 space-x-4">
                    <button id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <a href="user_profile.php?delete_account=1" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete Account</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Smart Save Button Logic ---
            const profileForm = document.getElementById('profile-form');
            const saveButton = document.getElementById('save-changes-btn');
            const inputs = profileForm.querySelectorAll('input[type="text"], input[type="file"]');

            const initialValues = {
                username: document.getElementById('username').value,
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
            };

            function checkForChanges() {
                let hasChanged = false;
                if (document.getElementById('username').value !== initialValues.username) hasChanged = true;
                if (document.getElementById('first_name').value !== initialValues.first_name) hasChanged = true;
                if (document.getElementById('last_name').value !== initialValues.last_name) hasChanged = true;
                if (document.getElementById('profile_image').files.length > 0) hasChanged = true;

                saveButton.disabled = !hasChanged;
            }

            checkForChanges();

            inputs.forEach(input => {
                input.addEventListener('input', checkForChanges);
                input.addEventListener('change', checkForChanges);
            });

            // --- Tab and Modal Logic ---
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            const deleteModal = document.getElementById('delete-modal');
            const deleteAccountBtn = document.getElementById('delete-account-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');


            function switchTab(targetId) {
                tabContents.forEach(content => {
                    content.style.display = content.id === targetId ? 'block' : 'none';
                });
                tabLinks.forEach(link => {
                    link.classList.toggle('active', link.hash === '#' + targetId);
                });
            }

            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.hash.substring(1);
                    switchTab(targetId);
                    history.pushState(null, '', '#' + targetId);
                });
            });

            const imageInput = document.getElementById('profile_image');
            const imagePreview = document.getElementById('image-preview');
            const initialsPreview = document.getElementById('initials-preview');

            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (initialsPreview) initialsPreview.classList.add('hidden');
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            const initialHash = window.location.hash.substring(1) || 'profile';
            switchTab(initialHash);

            if (deleteAccountBtn) {
                deleteAccountBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    deleteModal.classList.remove('hidden');
                });
            }
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', () => {
                    deleteModal.classList.add('hidden');
                });
            }
            window.addEventListener('click', (e) => {
                if (e.target == deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>