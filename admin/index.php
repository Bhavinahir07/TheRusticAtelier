<?php
// admin/index.php
require_once __DIR__ . '/../config/initAdmin.php';
// Optional: protect this route for admins only (uncomment/adjust as needed)
// if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') { header('Location: /login.php'); exit; }
// Derive admin display name
$adminName = $_SESSION['user']['username'] ?? 'Admin';

// Metrics: Total Users, Recipes, Orders, Products
$metrics = [
  'users' => '—',
  'recipes' => '—',
  'orders' => '—',
  'products' => '—'
];
try { $metrics['users'] = (string)($conn->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: '0'); } catch (Throwable $e) {}
try { $metrics['recipes'] = (string)($conn->query("SELECT COUNT(*) FROM recipes")->fetchColumn() ?: '0'); } catch (Throwable $e) {}
try { $metrics['orders'] = (string)($conn->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: '0'); } catch (Throwable $e) {}
try { $metrics['products'] = (string)($conn->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: '0'); } catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard • MyRecipe</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/admin.css" />
</head>
<body class="admin-shell">
  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar__header">
      <div class="admin-logo">TRA</div>
      <div class="admin-brand">TheRusticAtelier</div>
    </div>
    <nav class="admin-nav" id="adminNav">
      <a href="<?= BASE_URL ?>admin/index.php" class="active"><i class="fa-solid fa-gauge icon"></i>Admin Dashboard</a>
      <a href="<?= BASE_URL ?>admin/recipes.php"><i class="fa-solid fa-bowl-food icon"></i>Manage Recipes</a>
      <a href="<?= BASE_URL ?>admin/users.php"><i class="fa-solid fa-users icon"></i>View Users</a>
      <a href="<?= BASE_URL ?>admin/orders.php"><i class="fa-solid fa-receipt icon"></i>Manage Orders</a>
      <a href="<?= BASE_URL ?>admin/stats.php"><i class="fa-solid fa-chart-line icon"></i>Site Stats</a>
      <a href="<?= BASE_URL ?>admin/products.php"><i class="fa-solid fa-box-open icon"></i>Manage Products</a>
      <a href="<?= BASE_URL ?>admin/shared.php"><i class="fa-solid fa-share-nodes icon"></i>User Shared Recipes</a>
    </nav>
  </aside>

  <!-- Topbar -->
  <header class="admin-topbar">
    <div class="admin-topbar__inner">
      <div class="page-title" id="pageTitle">TheRusticAtelier</div>
      <div class="admin-identity" title="You are in the Admin area">
        <span class="admin-badge">ADMIN</span>
        <div class="admin-user"><i class="fa-solid fa-user-shield"></i><span><?php echo htmlspecialchars($adminName); ?></span></div>
        <a href="<?= BASE_URL ?>logout.php" class="btn-ghost" style="margin-left:8px;text-decoration:none"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
      </div>
    </div>
  </header>

  <!-- Content -->
  <main class="admin-content">
    <!-- Metrics summary -->
    <section class="cards-grid" aria-label="Key metrics" style="margin-bottom:16px">
      <article class="card">
        <div class="card-head"><div class="icon icon-users"><i class="fa-solid fa-users"></i></div><div class="title">Total Users</div></div>
        <div class="desc" style="font-size:24px;font-weight:800;color:#fff;"><?= htmlspecialchars($metrics['users']) ?></div>
        <a class="cta" href="<?= BASE_URL ?>admin/users.php">View users <i class="fa-solid fa-arrow-right"></i></a>
      </article>
      <article class="card">
        <div class="card-head"><div class="icon icon-recipe"><i class="fa-solid fa-bowl-food"></i></div><div class="title">Recipes</div></div>
        <div class="desc" style="font-size:24px;font-weight:800;color:#fff;"><?= htmlspecialchars($metrics['recipes']) ?></div>
        <a class="cta" href="<?= BASE_URL ?>admin/recipes.php">View recipes <i class="fa-solid fa-arrow-right"></i></a>
      </article>
      <article class="card">
        <div class="card-head"><div class="icon icon-stats"><i class="fa-solid fa-receipt"></i></div><div class="title">Orders</div></div>
        <div class="desc" style="font-size:24px;font-weight:800;color:#fff;"><?= htmlspecialchars($metrics['orders']) ?></div>
        <a class="cta" href="<?= BASE_URL ?>admin/orders.php">View orders <i class="fa-solid fa-arrow-right"></i></a>
      </article>
      <article class="card">
        <div class="card-head"><div class="icon icon-prod"><i class="fa-solid fa-box-open"></i></div><div class="title">Products</div></div>
        <div class="desc" style="font-size:24px;font-weight:800;color:#fff;"><?= htmlspecialchars($metrics['products']) ?></div>
        <a class="cta" href="<?= BASE_URL ?>admin/products.php">View products <i class="fa-solid fa-arrow-right"></i></a>
      </article>
    </section>
    <!-- Quick cards -->
    <section class="cards-grid" aria-label="Admin sections">
      <article class="card" data-card="recipes">
        <div class="card-head"><div class="icon icon-recipe"><i class="fa-solid fa-bowl-food"></i></div><div class="title">Manage Recipes</div></div>
        <div class="desc">Create, update, organize, and publish recipes with images and tags.</div>
        <a class="cta" href="<?= BASE_URL ?>admin/recipes.php">Go to recipes <i class="fa-solid fa-arrow-right"></i></a>
      </article>

      <article class="card" data-card="users">
        <div class="card-head"><div class="icon icon-users"><i class="fa-solid fa-users"></i></div><div class="title">View Users</div></div>
        <div class="desc">Browse, search and manage user profiles, roles, and access.</div>
        <a class="cta" href="<?= BASE_URL ?>admin/users.php">Go to users <i class="fa-solid fa-arrow-right"></i></a>
      </article>

      <article class="card" data-card="stats">
        <div class="card-head"><div class="icon icon-stats"><i class="fa-solid fa-chart-line"></i></div><div class="title">Site Stats</div></div>
        <div class="desc">Track page views, signups, orders, and engagement in real-time.</div>
        <a class="cta" href="<?= BASE_URL ?>admin/stats.php">View analytics <i class="fa-solid fa-arrow-right"></i></a>
      </article>

      <article class="card" data-card="products">
        <div class="card-head"><div class="icon icon-prod"><i class="fa-solid fa-box-open"></i></div><div class="title">Manage Products</div></div>
        <div class="desc">Add, edit, and categorize products. Manage inventory and pricing.</div>
        <a class="cta" href="<?= BASE_URL ?>admin/products.php">Go to products <i class="fa-solid fa-arrow-right"></i></a>
      </article>

      <article class="card" data-card="shared">
        <div class="card-head"><div class="icon icon-share"><i class="fa-solid fa-share-nodes"></i></div><div class="title">User Shared Recipes</div></div>
        <div class="desc">Review and approve recipes shared by users before publishing.</div>
        <a class="cta" href="<?= BASE_URL ?>admin/shared.php">Review submissions <i class="fa-solid fa-arrow-right"></i></a>
      </article>

      <article class="card" data-card="orders">
        <div class="card-head"><div class="icon icon-stats"><i class="fa-solid fa-receipt"></i></div><div class="title">Manage Orders</div></div>
        <div class="desc">View and process customer orders, update statuses and receipts.</div>
        <a class="cta" href="<?= BASE_URL ?>admin/orders.php">Go to orders <i class="fa-solid fa-arrow-right"></i></a>
      </article>
    </section>

    <!-- Removed Recent Activity and Tips -->
  </main>

  <script src="assets/js/admin.js"></script>
</body>
</html>
