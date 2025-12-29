<?php
// index.php - full file (PDO version)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/config/init.php";

// ----- Get all categories (PDO) -----
$categories = [];
try {
    $category_sql = "SELECT * FROM categories";
    $stmt = $conn->query($category_sql);
    $categories_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

foreach ($categories_raw as $category) {
    $category_id = $category['id'];
    try {
        // $recipe_sql = "SELECT * FROM recipes WHERE category_id = :category_id";
        // ----- MODIFICATION START: Updated SQL Query -----
        // We JOIN with the 'users' table (aliased as 'u') on the 'user_id'
        // to get the first_name, last_name, and username of the recipe's owner.
        $recipe_sql = "SELECT r.*, u.first_name, u.last_name, u.username
               FROM recipes r
               LEFT JOIN users u ON r.user_id = u.id
               WHERE r.category_id = :category_id";
        // ----- MODIFICATION END -----
        $rstmt = $conn->prepare($recipe_sql);
        $rstmt->execute(['category_id' => $category_id]);
        $recipes = $rstmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching recipes: " . $e->getMessage());
    }

    $categories[] = [
        'slug' => $category['slug'],
        'name' => $category['name'],
        'recipes' => $recipes
    ];
    $rstmt = null;
}

// Move 'pizza' category to the top of the list for display order, though 'All' will be the default view
usort($categories, function ($a, $b) {
    if ($a['slug'] === 'pizza')
        return -1;
    if ($b['slug'] === 'pizza')
        return 1;
    return 0;
});

// User display name and profile image logic (kept as is)
$user = $_SESSION['user'] ?? null;
$is_authenticated = isset($user) && !empty($user['id']);
$first_name = '';
$profile_image = "images/user_profile/download.png";
if ($is_authenticated) {
    if (!empty($user['first_name'])) {
        $first_name = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
    }
    if (!empty($user['profile_image'])) {
        $profile_image = htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8');
    }
    if ($first_name === '' || $profile_image === "images/user_profile/download.png") {
        try {
            $ustmt = $conn->prepare("SELECT first_name, profile_image FROM users WHERE id = ?");
            $ustmt->execute([$user['id']]);
            if ($row = $ustmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['first_name'])) {
                    $first_name = htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['user']['first_name'] = $row['first_name'];
                }
                if (!empty($row['profile_image'])) {
                    $profile_image = htmlspecialchars($row['profile_image'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['user']['profile_image'] = $row['profile_image'];
                }
            }
            $ustmt = null;
        } catch (PDOException $e) {
            // handle error
        }
    }
    if ($first_name === '' && !empty($user['username'])) {
        $parts = preg_split('/\s+/', trim($user['username']));
        $first_name = htmlspecialchars($parts[0] ?? $user['username'], ENT_QUOTES, 'UTF-8');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheRusticAtelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">
    <script src="public/js/script.js"></script>
</head>


<body>
    <div class="menu-overlay"></div>

    <?php
    $activePage = "home";
    $showCapsuleEffect = true;
    include __DIR__ . "/partials/header.php";
    ?>

    <main>
        <section class="sub-nav">
            <div class="sub-nav-inner">
                <ul class="category-list">
                    <li data-category="all" class="active" tabindex="-1">All</li>
                    <li data-category="pizza">Pizza</li>
                    <li data-category="cake">Cake</li>
                    <li data-category="non-veg">Non-Veg</li>
                    <li data-category="soups">Soups</li>
                    <li data-category="breakfast">Breakfast</li>
                    <li data-category="lunch">Lunch</li>
                    <li data-category="dinner">Dinner</li>
                    <li data-category="salad">Salad</li>
                </ul>

                <div class="search-bar-wrapper">
                    <input type="search" class="search" id="search-bar" placeholder="Search for food...">
                </div>
            </div>
        </section>

        <section class="box-container">
            <div id="no-results" class="no-results-message" style="display: none;">
                <span>No items found. Try another search.</span>
            </div>

            <?php foreach ($categories as $category): ?>
                <div id="<?= htmlspecialchars($category['slug']) ?>" class="food-grid food-category" style="display: grid;">
                    <?php if (!empty($category['recipes'])): ?>
                        <?php foreach ($category['recipes'] as $recipe): ?>
                            <div class="food-item">
                                <img src="<?= htmlspecialchars($recipe['image']) ?>"
                                    alt="<?= htmlspecialchars($recipe['title']) ?>">
                                <p class="p"><?= htmlspecialchars($recipe['title']) ?></p>

                                <?php
                                // Logic to determine the owner's name
                                $owner_name = '';
                                if (!empty($recipe['first_name']) || !empty($recipe['last_name'])) {
                                    $owner_name = trim(htmlspecialchars($recipe['first_name'] . ' ' . $recipe['last_name']));
                                } elseif (!empty($recipe['username'])) {
                                    $owner_name = htmlspecialchars($recipe['username']);
                                } else {
                                    // Fallback if recipe has no user or user was deleted
                                    $owner_name = 'The Rustic Atelier';
                                }
                                ?>
                                <p id="recipe-owner">By: <?= $owner_name ?></p>
                                <div class="description">
                                    <p><?= htmlspecialchars($recipe['intro']) ?></p>
                                </div>
                                <div class="showRecipe">
                                    <a
                                        href="recipe_details.php?category=<?= urlencode($category['slug']) ?>&slug=<?= urlencode($recipe['slug']) ?>">Recipe</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No recipes found in <?= htmlspecialchars($category['name']) ?> category.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
        <?php
        include("chatbot.php");
        ?>
    </main>
    <footer>
        <p>&copy; 2025 MyRecipe. All rights reserved.</p>
        <a href="index.php">Home Page</a>
        <a href="about_us.php">About Us</a>
        <a href="#">Our Products</a>
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <div class="footer-social">
            <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i
                    class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i
                    class="fab fa-instagram"></i></a>
        </div>
    </footer>
</body>

</html>