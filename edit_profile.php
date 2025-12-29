<?php
// edit_profile.php
// session_start();
// require_once "db.php";
require_once __DIR__ . "/config/init.php";

// Check if user is logged in
$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch current user details
$stmt = $conn->prepare("SELECT username, email, first_name, last_name, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Define default profile image path
$default_image = "images/user_profile/download.png";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $username   = trim($_POST['username']);

    // Profile picture handling
    $profile_image = $user['profile_image'] ?: $default_image; // keep old image or default if none
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "images/user_profile/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Secure filename
        $extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $file_name = "user_" . $user_id . "_" . time() . "." . $extension;
        $target_file = $target_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($extension, $allowed_types)) {
            if ($_FILES["profile_image"]["size"] <= 2 * 1024 * 1024) { // max 2MB
                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $profile_image = $target_file;
                }
            }
        }
    }

    // Update user info
    $update_stmt = $conn->prepare("UPDATE users 
        SET first_name=?, last_name=?, email=?, username=?, profile_image=? 
        WHERE id=?");
    $update_stmt->execute([$first_name, $last_name, $email, $username, $profile_image, $user_id]);

    // Update session data
    $_SESSION['user']['username']   = $username;
    $_SESSION['user']['email']      = $email;
    $_SESSION['user']['first_name'] = $first_name;
    $_SESSION['user']['last_name']  = $last_name;
    $_SESSION['user']['profile_image'] = $profile_image;

    header("Location: user_profile.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>

    <!-- FontAwesome & Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/user_profile.css">

    <style>
        .profile-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <!-- MENU OVERLAY -->
    <div class="menu-overlay"></div>

    <!-- Header -->
    <header>
        <div class="hamburger" onclick="toggleMenu()" aria-label="Toggle navigation">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <h1><i class="fas fa-utensils"></i> MyRecipes</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="product.php">Products</a></li>
                <li><a href="about_us.php">About Us</a></li>
                <li><a href="share_recipe.php">Share Recipe</a></li>
            </ul>
        </nav>
        <div class="menu-close" aria-hidden="true">&times;</div>
        <div class="auth-buttons">
            <div class="dropdown">
                <a href="#" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($_SESSION['user']['profile_image'] ?? $default_image) ?>" 
                         alt="Profile" class="profile-avatar">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li class="px-3 py-2">
                        <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a href="user_profile.php" class="dropdown-item">Your Profile</a></li>
                    <li><a href="logout.php" class="dropdown-item">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Edit Profile Form -->
    <main class="profile-container">
        <section class="profile-section">
            <h2>Edit Profile</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile Picture</label><br>
                    <img src="<?= htmlspecialchars($user['profile_image'] ?: $default_image) ?>" 
                         alt="Profile Picture" width="100" class="mb-2 rounded-circle"><br>
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                    <small class="text-muted">Allowed types: JPG, JPEG, PNG, GIF (Max 2MB)</small>
                </div>
                <button type="submit" class="btn profile-btn">Save Changes</button>
                <a href="user_profile.php" class="btn cancel-btn">Cancel</a>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2025 MyRecipe. All rights reserved.</p>
        <a href="about.php">About Us</a>
        <a href="share_recipe.php">Share Recipe</a>
        <a href="product.php">Our Products</a>
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <div class="footer-social">
            <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
