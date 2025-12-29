<?php
// session_start();
require_once __DIR__ . "/config/init.php";

// ✅ Access control: only admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['redirect_after_login'] = 'admin.php';
    header("Location: login.php");
    exit();
}

// Extra security
session_regenerate_id(true);
$username = htmlspecialchars($_SESSION['user']['username']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TheRusticAtelier</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css"> <!-- Global styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css"> <!-- Admin-specific styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Font styles for testing -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>


</head>

<body>
    <div class="page-container"> <!-- ✅ START WRAPPER -->

        <!-- Admin Header -->
        <header>
            <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier </h1>
            <div class="auth-buttons">
                <span class="admin-user-label">
                    Logged in as: <?= $username ?>
                </span>
                <a href="logout.php">
                    <button class="btn">Logout</button>
                </a>
            </div>
        </header>

        <!-- Admin Welcome Section -->
        <section class="admin-welcome">
            <h2>Welcome, Admin <?= $username ?>!</h2>
            <p>Use the dashboard below to manage the website.</p>
        </section>

        <!-- Admin Dashboard Cards -->
        <div class="box-container">
            <div class="admin-grid">

                <!-- Manage Recipes -->
                <div class="admin-card">
                    <div>
                        <p class="p">Manage Recipes</p>
                        <p class="description">View, add, edit or delete user-submitted recipes.</p>
                        <div class="showRecipe">
                            <button onclick="window.location.href='manage_recipes.php'">Go</button>
                            <!-- <a href="#">Go</a> -->
                        </div>
                    </div>
                </div>

                <!-- View Users -->
                <div class="admin-card">
                    <div>
                        <p class="p">View Users</p>
                        <p class="description">See registered users and their details.</p>
                        <div class="showRecipe">
                            <button onclick="window.location.href='view_users.php'">Go</button>
                            <!-- <a href="#">Go</a> -->
                        </div>
                    </div>
                </div>

                <!-- Site Stats -->
                <div class="admin-card">
                    <div>
                        <p class="p">Site Stats</p>
                        <p class="description">Track recipe counts, user activity, etc.</p>
                        <div class="showRecipe">
                            <button onclick="window.location.href='site_stats.php'">Go</button>
                            <!-- <a href="#">Go</a> -->
                        </div>
                    </div>
                </div>

                <!-- Manage Products -->
                <div class="admin-card">
                    <div>
                        <p class="p">Manage Products</p>
                        <p class="description">Add, edit, or remove products from the store.</p>
                        <div class="showRecipe">
                            <button onclick="window.location.href='manage_products.php'">Go</button>
                            <!-- <a href="#">Go</a> -->
                        </div>
                    </div>
                </div>

                <!-- View Feedback -->
                <div class="admin-card">
                    <div>
                        <p class="p">User Shared Recipes</p>
                        <p class="description">Check user shared recipes and consider a permission.</p>
                        <div class="showRecipe">
                            <button onclick="window.location.href='shared_recipe_admin.php'">Go</button>
                            <!-- <a href="#">Go</a> -->
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer>
            <p>&copy; <?= date("Y") ?> TheRusticAtelier. All rights reserved.</p>
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
    </div>
</body>

</html>