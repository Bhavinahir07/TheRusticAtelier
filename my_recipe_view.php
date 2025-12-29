<?php
require_once __DIR__ . "/config/init.php";

// Auth check
if (empty($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user']['id'];

// Fetch user's shared recipes
$recipe_stmt = $conn->prepare("SELECT id, title, status, created_at FROM shared_recipes WHERE user_id = ? ORDER BY created_at DESC");
$recipe_stmt->execute([$user_id]);
$recipes = $recipe_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Recipes</title>
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
    body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    header .site-name { margin: 0; padding: 0; text-align: left; }
    header .site-name i { display: inline-block; transform: translateY(-10%); }
    header .header-row { padding-top: 0.46rem; padding-bottom: 0.46rem; }
    header { box-shadow: none !important; }
    .submenu a { padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 14px; }
    .submenu a.active { background: #111; color: #fff; }
    .submenu a:not(.active) { background: #f3f4f6; color: #111; }
    .submenu a:not(.active):hover { background: #e5e7eb; }
    .badge-status { font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 999px; }
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
        <a href="user_profile.php" class="text-sm font-medium text-gray-600 hover:text-black flex items-center">
          <i class="fas fa-user mr-2"></i> Back to Profile
        </a>
      </div>
    </div>
  </header>

  <main class="container mx-auto max-w-6xl p-4 sm:p-6 lg:p-8">
    <section class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-800">My Shared Recipes</h2>
        <a href="share_recipe.php" class="inline-flex items-center gap-2 bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">
          <i class="fas fa-plus"></i> Share New Recipe
        </a>
      </div>

      <?php if ($recipes): ?>
        <div class="divide-y">
          <?php foreach ($recipes as $recipe): ?>
            <?php
              $status = strtolower((string)$recipe['status']);
              $cls = 'bg-gray-100 text-gray-800';
              if ($status === 'approved') $cls = 'bg-green-100 text-green-800';
              elseif ($status === 'pending') $cls = 'bg-yellow-100 text-yellow-800';
              elseif ($status === 'rejected') $cls = 'bg-red-100 text-red-800';
            ?>
            <div class="py-3 flex items-center justify-between">
              <div>
                <div class="font-semibold text-gray-900"><?= htmlspecialchars($recipe['title']) ?></div>
                <span class="badge-status <?= $cls ?>"><?= ucfirst(htmlspecialchars($status)) ?></span>
              </div>
              <div class="flex items-center gap-2">
                <a href="view_shared_recipes.php?id=<?= (int)$recipe['id'] ?>" class="text-sm text-orange-600 hover:underline">View</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-12">
          <i class="fas fa-book text-4xl text-gray-300"></i>
          <p class="mt-4 text-gray-500">You haven't shared any recipes yet.</p>
          <a href="share_recipe.php" class="mt-4 inline-block bg-orange-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors">Share Your First Recipe</a>
        </div>
      <?php endif; ?>
    </section>
  </main>

</body>
</html>
