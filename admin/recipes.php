<?php require_once __DIR__ . '/../config/initAdmin.php'; ?>
<?php
// session_start();
// require_once 'db.php'; // Include DB connection
// admin/_bootstrap.php


// Guard: allow only admins (adjust if you store role differently)
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/manage_recipes.php';
  header("Location: " . BASE_URL . "login.php");
  exit();
}

$adminName = $_SESSION['user']['username'] ?? 'Admin';

// Data: fetch recipes with category
$currentCategory = strtolower(trim($_GET['category'] ?? 'all'));
$sql = "
    SELECT r.id, r.title, r.cooking_time, r.created_at, c.name AS category
    FROM recipes r
    LEFT JOIN categories c ON r.category_id = c.id
";
if ($currentCategory !== 'all' && $currentCategory !== '') {
  $sql .= " WHERE LOWER(COALESCE(c.name,'')) = :cat ";
}
$sql .= " ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
if ($currentCategory !== 'all' && $currentCategory !== '') {
  $stmt->bindValue(':cat', $currentCategory, PDO::PARAM_STR);
}
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Recipes • Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Removed admin.css; using page-scoped premium styles -->
  <style>
    :root {
      --bg: #0b0f19;
      --panel: #0f172a;
      --muted: #9aa4b2;
      --text: #e6e9ef;
      --primary: #7c3aed;
      --primary2: #8b5cf6;
      --accent: #22d3ee;
      --border: rgba(255, 255, 255, .08);
      --danger: #ef4444
    }

    * {
      box-sizing: border-box
    }

    body.admin-shell {
      margin: 0;
      background: radial-gradient(1200px 600px at 20% -10%, rgba(124, 58, 237, .18), transparent), radial-gradient(1000px 500px at 100% 0%, rgba(34, 211, 238, .14), transparent), linear-gradient(180deg, #0b0f19 0%, #0f172a 100%);
      color: var(--text);
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif
    }

    a {
      text-decoration: none;
      color: inherit
    }

    .btn-soft,
    .btn-primary,
    .btn-mini {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 12px;
      padding: 9px 12px;
      font-weight: 700;
      border: 1px solid transparent
    }

    .btn-soft {
      background: rgba(255, 255, 255, .06);
      border-color: var(--border)
    }

    .btn-soft:hover {
      background: rgba(255, 255, 255, .1)
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--primary2));
      box-shadow: 0 12px 28px rgba(124, 58, 237, .35)
    }

    .btn-mini {
      padding: 8px 10px;
      background: rgba(255, 255, 255, .06);
      border: 1px solid var(--border);
      border-radius: 10px
    }

    .btn-mini.danger {
      background: rgba(239, 68, 68, .12);
      border-color: rgba(239, 68, 68, .35)
    }

    /* Unified topbar (no sidebar) */
    .admin-topbar {
      position: sticky;
      top: 0;
      left: 0;
      right: 0;
      background: linear-gradient(180deg, #0b0f19, #0f172a);
      border-bottom: 1px solid var(--border);
      padding: 14px 18px;
      z-index: 2
    }

    .admin-topbar__inner {
      display: flex;
      align-items: center;
      gap: 12px
    }

    .hamburger {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: 10px;
      padding: 8px
    }

    .admin-name{
      margin: 10px;
    }

    .page-title {
      font-weight: 800;
      font-size: 1.6rem
    }

    .admin-identity {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .admin-badge {
      background: rgba(124, 58, 237, .18);
      border: 1px solid rgba(124, 58, 237, .35);
      color: #c4b5fd;
      padding: 2px 8px;
      border-radius: 9999px;
      font-weight: 800
    }

    .admin-user span {
      color: var(--muted)
    }

    .admin-content {
      margin-left: 0;
      padding: 18px
    }

    .recipes-page {
      max-width: 1100px;
      margin: 0 auto
    }

    .recipes-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px
    }

    .breadcrumbs {
      color: var(--muted);
      display: flex;
      gap: 8px;
      align-items: center
    }

    .head-actions {
      display: flex;
      gap: 10px
    }

    /* Make action buttons scrollable to save table width */
    td.actions {
      white-space: nowrap
    }

    td.actions .actions-inner {
      display: inline-flex;
      gap: 8px;
      overflow-x: auto;
      max-width: 260px;
      padding-bottom: 4px
    }

    td.actions .actions-inner::-webkit-scrollbar {
      height: 6px
    }

    td.actions .actions-inner::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, .15);
      border-radius: 9999px
    }

    /* Floating Add button */
    .recipes-fab {
      position: fixed;
      right: 22px;
      bottom: 22px;
      width: 46px;
      height: 46px;
      border-radius: 9999px;
      display: grid;
      place-items: center;
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--primary2));
      box-shadow: 0 10px 30px rgba(124, 58, 237, .35);
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, .12)
    }

    .recipes-fab:hover {
      filter: brightness(1.05)
    }

    .filters-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin: 12px 0 18px
    }

    .filter-chip {
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: 9999px;
      background: rgba(255, 255, 255, .04);
      cursor: pointer
    }

    .filter-chip.active {
      background: rgba(34, 211, 238, .12);
      border-color: rgba(34, 211, 238, .35)
    }

    .table-wrap {
      background: linear-gradient(180deg, #0d1424, #0c1220);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 24px 60px rgba(2, 6, 23, .55);
      overflow: hidden
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0
    }

    thead {
      background: rgba(255, 255, 255, .04)
    }

    th,
    td {
      padding: 12px 14px;
      text-align: left
    }

    th {
      font-size: .9rem;
      color: #cdd5df
    }

    tbody tr {
      border-top: 1px solid var(--border)
    }

    tbody tr:hover {
      background: rgba(255, 255, 255, .03)
    }

    .title-cell .title-text {
      font-weight: 700
    }

    .muted {
      color: var(--muted)
    }

    .badge {
      padding: 4px 8px;
      border-radius: 9999px;
      border: 1px solid var(--border);
      font-size: .8rem
    }

    .badge-veg {
      background: rgba(34, 197, 94, .12);
      border-color: rgba(34, 197, 94, .35)
    }

    .badge-nonveg {
      background: rgba(239, 68, 68, .12);
      border-color: rgba(239, 68, 68, .35)
    }

    .badge-neutral {
      background: rgba(255, 255, 255, .06)
    }

    .empty-state {
      padding: 36px;
      text-align: center;
      color: var(--muted)
    }

    .empty-state .title {
      color: var(--text);
      font-weight: 800;
      margin-top: 8px
    }

    .admin-footer {
      margin-left: 0;
      padding: 18px;
      color: var(--muted)
    }
  </style>
</head>

<body class="admin-shell">

  <!-- Topbar (unified, no sidebar offsets) -->
  <header class="admin-topbar">
    <div class="admin-topbar__inner">
      <div class="page-title" id="pageTitle">TheRusticAtelier</div>
      <div class="admin-identity" title="You are in the Admin area">
        <span class="admin-badge">ADMIN</span>
        <div class="admin-user"><i class="fa-solid fa-user-shield"></i><span class="admin-name"><?= htmlspecialchars($adminName) ?></span></div>
      </div>
    </div>
  </header>

  <!-- Content -->
  <main class="admin-content">
    <section class="recipes-page">
      <div class="page-head" style="display:flex;align-items:center;justify-content:space-between;margin:10px 0 16px">
        <div>
          <div class="title" style="font-size:1.6rem;font-weight:800">Manage Recipes</div>
          <div class="sub" style="color:var(--muted)">Create, update, organize, and publish recipes</div>
        </div>
        <div class="head-actions" style="display:flex;gap:10px">
          <a href="<?= BASE_URL ?>admin/index.php" class="btn-soft"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back</a>
          <a href="<?= BASE_URL ?>logout.php" class="btn-soft"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
        </div>
      </div>

      <!-- Filters (server-side via ?category=...) -->
      <div class="filters-row">
        <?php
        $chips = ['all' => 'All', 'pizza' => 'Pizza', 'cake' => 'Cake', 'non-veg' => 'Non‑Veg', 'soups' => 'Soups', 'breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner', 'salad' => 'Salad'];
        foreach ($chips as $key => $label):
          $active = ($currentCategory === $key) || ($key === 'all' && ($currentCategory === 'all' || $currentCategory === ''));
          $href = BASE_URL . 'admin/recipes.php?category=' . urlencode($key);
        ?>
          <a class="filter-chip <?= $active ? 'active' : '' ?>" href="<?= $href ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($recipes)): ?>
        <div class="table-wrap">
          <table class="recipes-table" id="recipesTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Cooking Time</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recipes as $row): ?>
                <tr>
                  <td><?= (int)$row['id'] ?></td>
                  <td class="title-cell">
                    <div class="title-wrap">
                      <span class="title-text"><?= htmlspecialchars($row['title']) ?></span>
                    </div>
                  </td>
                  <td>
                    <?php $cat = trim((string)($row['category'] ?? '')); ?>
                    <span class="badge <?= stripos($cat, 'veg') === 0 ? 'badge-veg' : (stripos($cat, 'non') === 0 ? 'badge-nonveg' : 'badge-neutral') ?>">
                      <?= $cat !== '' ? htmlspecialchars($cat) : 'Uncategorized' ?>
                    </span>
                  </td>
                  <td><span class="muted"><?= htmlspecialchars($row['cooking_time']) ?></span></td>
                  <td><span class="muted"><?= date('d M Y', strtotime($row['created_at'])) ?></span></td>
                  <td class="actions">
                    <span class="actions-inner">
                      <a class="btn-mini" href="<?= BASE_URL ?>admin/view_recipe.php?id=<?= (int)$row['id'] ?>" title="View">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                      </a>
                      <a class="btn-mini" href="<?= BASE_URL ?>admin/edit_recipe.php?id=<?= (int)$row['id'] ?>" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                      </a>
                      <a class="btn-mini danger" href="<?= BASE_URL ?>admin/delete_recipe.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this recipe?');">
                        <i class="fa-solid fa-trash"></i>
                      </a>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-bowl-food"></i>
          <div class="title">No recipes found</div>
          <div class="desc">Create your first recipe to get started.</div>
        </div>
      <?php endif; ?>
    </section>
    <a href="<?= BASE_URL ?>admin/add_recipes.php" class="recipes-fab" title="Add Recipe"><i class="fa-solid fa-plus"></i></a>
  </main>
</body>

</html>