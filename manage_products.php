<?php
// manage_products.php
// session_start();
// require_once 'db.php';
require_once __DIR__ . "/config/init.php";

// ✅ Access control: only admin can access
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
    $_SESSION['redirect_after_login'] = 'manage_products.php';
    header("Location: login.php");
    exit();
}

// Extra session safety
session_regenerate_id(true);
$username = htmlspecialchars($_SESSION['user']['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');

// Fetch products (match fields used in product.php)
$products = [];
try {
    $stmt = $conn->query("SELECT id, name, price, image, category FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Optional: log error in real app
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Products | MyRecipe Admin</title>
    <!-- Styles reused from your project -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css"> <!-- if used globally -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css"> <!-- .auth-buttons, .btn, etc. -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/product.css"> <!-- .products, .product-card, search UI -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/manage_products.css">
</head>

<body>
    <div class="page-container"><!-- keeps footer pinned as in admin.css -->

        <!-- Admin Header -->
        <header>
            <h1>Manage Products</h1>
            <div class="auth-buttons">
                <span class="admin-user-label">Logged in as: <?= $username ?></span>
                <a href="logout.php"><button class="btn">Logout</button></a>
            </div>
        </header>

        <!-- Dashboard + Add Product row -->
        <!-- <div class="top-bar">
            <a href="admin.php" class="auth-buttons"><button class="btn">Dashboard</button></a>
            <a href="add_product.php" class="auth-buttons"><button class="btn btn-add">+ Add Product</button></a>
        </div> -->
        <!-- <div class="add-buttons"> -->
        <!-- <a href="admin.php" class="btn dashboard">⬅ Dashboard</a> -->
        <!-- <a class="back-link" href="admin.php">&larr; Back to admin panel</a>
            <a href="add_product.php" class="btn btn-add">+ Add Product</a>
        </div> -->
        <div class="add-buttons">
            <a class="back-link" href="admin.php">&larr; Back to admin panel</a>
            <a href="add_product.php" class="btn btn-add">
                <span class="plus-icon">+</span> &nbsp;Add Recipe
            </a>
        </div>


        <!-- Optional intro -->
        <section class="admin-welcome">
            <h2>Products</h2>
            <p>Search, view, edit, or delete products.</p>
        </section>

        <!-- Search -->
        <main class="container">
            <div class="search-wrapper">
                <input
                    type="search"
                    id="search"
                    class="search-bar"
                    placeholder="Search products by name..."
                    onkeyup="filterCards()"
                    aria-label="Search products" />
            </div>

            <!-- Products Grid -->
            <section class="products" id="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $id   = (int)($p['id'] ?? 0);
                        $name = htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $img  = htmlspecialchars($p['image'] ?? '', ENT_QUOTES, 'UTF-8');
                        $price = number_format((float)($p['price'] ?? 0), 2);
                        $category = strtolower(trim($p['category'] ?? ''));
                        $isVeg = ($category === 'veg' || $category === 'vegetarian');
                        ?>
                        <div class="product-card" data-name="<?= $name ?>">
                            <?php
                            $imgPath = !empty($p['image']) ? $p['image'] : 'images/products/default.png';
                            ?>
                            <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>">
                            <h3><?= $name ?></h3>
                            <p>
                                <?php if ($isVeg): ?>
                                    <span class="veg">Vegetarian</span>
                                <?php else: ?>
                                    <span class="non-veg">Non-Vegetarian</span>
                                <?php endif; ?>
                            </p>
                            <p class="price">₹<?= $price ?></p>

                            <div class="auth-buttons">
                                <a href="product_admin.php?id=<?= $id ?>" class="btn">Manage</a>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <p id="no-results-container" style="text-align:center;">No products found.</p>
                <?php endif; ?>
            </section>

            <div id="no-results-container" style="display:none;">
                <p>No matching products found.</p>
            </div>
        </main>

        <footer>
            &copy; <?= date("Y") ?> MyRecipe. All rights reserved.
        </footer>
    </div>

    <script>
        function filterCards() {
            const q = document.getElementById('search').value.trim().toLowerCase();
            const grid = document.getElementById('product-grid');
            const cards = grid ? grid.querySelectorAll('.product-card') : [];
            let matches = 0;

            cards.forEach(card => {
                const name = (card.getAttribute('data-name') || '').toLowerCase();
                const show = name.includes(q);
                card.style.display = show ? '' : 'none';
                if (show) matches++;
            });

            const noRes = document.getElementById('no-results-container');
            if (noRes) noRes.style.display = matches === 0 ? 'block' : 'none';
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = 'delete_product.php?id=' + encodeURIComponent(id);
            }
        }
    </script>
</body>

</html>