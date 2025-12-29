<?php

/**
 * header.php
 * Shared header for Recipe Sharing & Product Website
 */

// --- Smarter Logic for User Authentication & Avatar Generation ---
$is_authenticated = !empty($_SESSION['user']['id']);
$user = $_SESSION['user'] ?? [];
$activePage = $activePage ?? '';

// Set default guest values
$profile_image = null;
$first_name = 'Guest';
$user_initial = '';
$avatar_color_class = '';
$full_name = 'Guest';

if ($is_authenticated) {
    // Try to get user details from the session first
    $first_name = $user['first_name'] ?? '';
    if (!empty($user['profile_image'])) {
        $profile_image = htmlspecialchars($user['profile_image']) . '?v=' . time();
    }

    // If the session is missing details, fetch from the database
    if (empty($first_name) || empty($user['profile_image'])) {
        $stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        if ($user_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($user_data['first_name'])) {
                $first_name = $user_data['first_name'];
                $_SESSION['user']['first_name'] = $first_name;
            }
            if (!empty($user_data['last_name'])) {
                $_SESSION['user']['last_name'] = $user_data['last_name']; // Save last name to session
            }
            if (!empty($user_data['profile_image'])) {
                $profile_image = htmlspecialchars($user_data['profile_image']) . '?v=' . time();
                $_SESSION['user']['profile_image'] = $user_data['profile_image'];
            }
        }
    }

    // Fallback to username if first_name is still empty
    if (empty($first_name) && !empty($user['username'])) {
        $first_name = $user['username'];
    }

    // Combine first and last name for display
    $full_name = trim($first_name . ' ' . ($_SESSION['user']['last_name'] ?? ''));
    if (empty($full_name)) {
        $full_name = $user['username']; // Fallback to username if full name is empty
    }


    // Logic for Initial Avatar
    if (empty($profile_image)) {
        $user_initial = strtoupper(substr($first_name, 0, 1));
        $colors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-yellow-500', 'bg-indigo-500'];
        $hash = ord(substr($user['username'] ?? 'User', 0, 1));
        $avatar_color_class = $colors[$hash % count($colors)];
    }
}
?>

<!-- Custom CSS for the Profile Dropdown and Guest Buttons -->
<style>
    .profile-dropdown {
        position: relative;
        display: inline-block;
    }

    .profile-trigger {
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid transparent;
        transition: all 0.2s ease-in-out;
        padding: 0;
        box-sizing: border-box;
    }

    .profile-trigger:hover,
    .profile-trigger.active {
        border-color: #0d6efd;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
    }

    .profile-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
    }

    .first-name {
        display: none;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        background-color: white;
        min-width: 260px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        border-radius: 12px;
        border: 1px solid #f0f0f0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-10px);
        transition: opacity 0.2s ease-out, transform 0.2s ease-out;
    }

    .dropdown-content.show {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    .dropdown-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .dropdown-header .profile-avatar {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }

    .dropdown-header strong {
        font-size: 1.1rem;
        color: #212529;
    }

    .dropdown-header small {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .dropdown-content a {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: #495057;
        text-decoration: none;
        font-size: 0.95rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .dropdown-content a:hover {
        background-color: #f8f9fa;
        color: #0d6efd;
    }

    .dropdown-content a i.fas {
        width: 24px;
        margin-right: 12px;
        text-align: center;
        color: #adb5bd;
    }

    .dropdown-divider-custom {
        height: 1px;
        margin: 0.5rem 0;
        background-color: #e9ecef;
    }

    .dropdown-content a.logout-link {
        color: #dc3545;
    }

    .dropdown-content a.logout-link:hover {
        background-color: #fdf2f2;
    }

    .dropdown-content a.logout-link i.fas {
        color: #f19ca5;
    }

    /* ✨ FIX: Updated guest button styles */
    .guest-buttons .btn-login,
    .guest-buttons .btn-signup {
        background-color: #000;
        /* Black background */
        color: #fff;
        /* White text */
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .guest-buttons .btn-login:hover,
    .guest-buttons .btn-signup:hover {
        background-color: #333;
        /* Lighter black on hover */
    }

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
</style>

<header>
    <div class="hamburger" onclick="toggleMenu()" aria-label="Toggle navigation">
        <div></div>
        <div></div>
        <div></div>
    </div>

    <h1 class="site-name">
        <i class="fas fa-utensils"></i>
        TheRusticAtelier
    </h1>

    <nav>
        <ul>
            <li><a href="index.php" class="<?= ($activePage === "home" ? "active" : "") ?>">Home</a></li>
            <li><a href="product.php" class="<?= ($activePage === "products" ? "active" : "") ?>">Products</a></li>
            <li><a href="about_us.php" class="<?= ($activePage === "about_us" ? "active" : "") ?>">About Us</a></li>
            <li><a href="share_recipe.php" class="<?= ($activePage === "share_recipe" ? "active" : "") ?>">Share Recipe</a></li>
        </ul>
    </nav>

    <div class="auth-buttons">
        <?php if ($is_authenticated): ?>
            <div class="profile-dropdown">
                <div class="profile-trigger" id="profileTrigger" tabindex="0">
                    <?php if ($profile_image): ?>
                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar <?= $avatar_color_class ?>">
                            <span><?= $user_initial ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="dropdown-content" id="profileMenu">
                    <div class="dropdown-header">
                        <?php if ($profile_image): ?>
                            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar <?= $avatar_color_class ?>">
                                <span><?= $user_initial ?></span>
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($full_name) ?></strong><br>
                            <small><?= htmlspecialchars($user['email']) ?></small>
                        </div>
                    </div>
                    <a href="user_profile.php"><i class="fas fa-user-circle"></i> Your Profile</a>
                    <a href="show_address.php"><i class="fas fa-map-marker-alt"></i> Address</a>
                    <a href="my_orders.php"><i class="fas fa-box-open"></i> My Orders</a>
                    <a href="my_recipe_view.php"><i class="fas fa-book-open"></i> My Recipes</a>
                    <div class="dropdown-divider-custom"></div>
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="guest-buttons" style="display: flex; align-items: center; gap: 8px;">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="signup.php" class="btn btn-signup">Sign Up</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="menu-overlay"></div>

<script src="<?= BASE_URL ?>js/script.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const profileTrigger = document.getElementById("profileTrigger");
        const profileMenu = document.getElementById("profileMenu");

        if (profileTrigger) {
            profileTrigger.addEventListener("click", function(event) {
                event.stopPropagation();
                const isShown = profileMenu.classList.toggle("show");
                profileTrigger.classList.toggle("active", isShown);
            });
            profileTrigger.addEventListener("keydown", function(event) {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    profileTrigger.click();
                }
            });
        }

        window.addEventListener("click", function(event) {
            if (profileMenu && profileMenu.classList.contains("show")) {
                if (!profileTrigger.contains(event.target)) {
                    profileMenu.classList.remove("show");
                    profileMenu.classList.remove("active");
                }
            }
        });
    });
</script>