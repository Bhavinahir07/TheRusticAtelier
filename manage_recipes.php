<?php
// session_start();
// require_once 'db.php'; // Include DB connection
require_once __DIR__ . "/config/init.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['redirect_after_login'] = 'manage_recipes.php';
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($_SESSION['user']['username']);

// ✅ Fetch recipes with category name using PDO
$sql = "SELECT r.id, r.title, r.cooking_time, r.created_at, c.name AS category 
        FROM recipes r
        LEFT JOIN categories c ON r.category_id = c.id
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC); // ✅ Correct PDO method
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipes | Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/manage_recipes.css">
    <!-- <link rel="stylesheet" href="<?= BASE_URL ?>css/buttons.css"> -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
    
    <!-- Site icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Font styles for testing -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

</head>

<body>

    <!-- Header -->
    <header>
        <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier</h1>
        <div class="auth-buttons">
            <span class="admin-user-label">Logged in as: <?= $username ?></span>
            <a href="logout.php">
                <button class="btn">Logout</button>
            </a>
        </div>
    </header>

    <!-- Add Recipe Button -->
    <div class="add-buttons">
        <a class="back-link" href="admin.php">&larr; Back to admin panel</a>
        <a href="add_recipe.php" class="btn btn-add">
            <span class="plus-icon">+</span> &nbsp;Add Recipe
        </a>
    </div>

    <!-- Recipe Table -->
    <section class="table-section">
        <h2 class="section-title">Manage Recipes</h2>

        <?php if (count($recipes) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Cooking Time</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?: 'Uncategorized' ?></td>
                            <td><?= htmlspecialchars($row['cooking_time']) ?></td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="recipe_admin.php?id=<?= $row['id'] ?>&action=view" class="btn btn-view">Go</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">No recipes found.</p>
        <?php endif; ?>
    </section>

</body>

</html>