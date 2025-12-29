<?php
require_once __DIR__ . "/config/init.php";

// --- 1. Authentication & Authorization Check ---
if (empty($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user']['id'];

// --- 2. Get Recipe ID from URL and Validate ---
$recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recipe_id) {
    // If the ID is missing or invalid, redirect with an error.
    $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Invalid recipe link.'];
    header("Location: user_profile.php#recipes");
    exit;
}

// --- 3. Fetch Recipe Data Securely ---
// This query ensures the user can only view their own shared recipes.
$stmt = $conn->prepare("SELECT * FROM shared_recipes WHERE id = ? AND user_id = ?");
$stmt->execute([$recipe_id, $user_id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

// If no recipe is found (or it belongs to another user), redirect.
if (!$recipe) {
    $_SESSION['feedback'] = ['type' => 'error', 'text' => 'Recipe not found or you do not have permission to view it.'];
    header("Location: user_profile.php#recipes");
    exit;
}

// Prepare data for display
$recipe_image_url = !empty($recipe['image'])
    ? htmlspecialchars($recipe['image']) . '?v=' . time()
    : "https://placehold.co/800x600/e2e8f0/718096?text=No+Image";

$status_classes = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
];
$status_class = $status_classes[$recipe['status']] ?? 'bg-gray-100 text-gray-800';

// Humanize cook time from minutes
$cook_minutes = (int)($recipe['cooking_time'] ?? 0);
function humanize_minutes($m) {
    if ($m <= 0) return 'N/A';
    $h = intdiv($m, 60);
    $min = $m % 60;
    if ($h > 0 && $min > 0) return $h . ' hr ' . $min . ' mins';
    if ($h > 0) return $h . ' hr' . ($h > 1 ? 's' : '');
    return $min . ' mins';
}
$cook_time_text = humanize_minutes($cook_minutes);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Recipe: <?= htmlspecialchars($recipe['title']) ?></title>
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        /* Use inside positioning so two-digit numbers/bullets aren't cropped in overflow containers */
        .recipe-content ul { list-style-type: disc; list-style-position: inside; padding-left: 0; }
        .recipe-content ol { list-style-type: decimal; list-style-position: inside; padding-left: 0; }
        .recipe-content li { margin-bottom: 0.5rem; }
        /* Header tweaks */
        header .site-name { margin: 0; padding: 0; text-align: left; }
        header .site-name i { display: inline-block; transform: translateY(-10%); }
        header .header-row { padding-top: 0.46rem; padding-bottom: 0.46rem; }
        header { box-shadow: none !important; }
        /* Clamp intro to 2 lines */
        .clamp-2 {
            display: -webkit-box;
            /* -webkit-line-clamp: 2; */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        /* Expanded intro: scrollable with fixed max height */
        .intro-expanded { max-height: 220px; overflow-y: auto; padding-right: 6px; }
        /* Optional: nicer thin scrollbar */
        .intro-expanded::-webkit-scrollbar { width: 6px; }
        .intro-expanded::-webkit-scrollbar-track { background: #f1f1f1; }
        .intro-expanded::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 6px; }
        .intro-expanded::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        /* Collapsible sections (Ingredients/Instructions) */
        .section-collapsed { max-height: 140px; overflow: hidden; padding-bottom: 12px; }
        .section-expanded { max-height: 260px; overflow-y: auto; padding-right: 6px; padding-bottom: 12px; }
        .section-expanded::-webkit-scrollbar { width: 6px; }
        .section-expanded::-webkit-scrollbar-track { background: #f1f1f1; }
        .section-expanded::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 6px; }
        .section-expanded::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        /* Slightly taller collapsed height for ingredients to avoid clipping last line */
        #ingredients-content.section-collapsed { max-height: 160px; }
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
                <a href="my_recipe_view.php" class="text-sm font-medium text-gray-600 hover:text-black flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to My Recipes
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <!-- Recipe Image -->
                <div class="w-full h-64 md:h-[520px]">
                    <img src="<?= $recipe_image_url ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-full h-full object-cover">
                </div>

                <!-- Recipe Details -->
                <div class="p-8">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold px-3 py-1 rounded-full <?= $status_class ?>">
                                <?= ucfirst(htmlspecialchars($recipe['status'])) ?>
                            </span>
                            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight mt-2"><?= htmlspecialchars($recipe['title']) ?></h2>
                        </div>
                        <!-- Edit button removed as requested -->
                    </div>
                    
                    <p id="intro-text" class="mt-4 text-gray-600 leading-relaxed clamp-2">
                        <?= htmlspecialchars($recipe['intro']) ?>
                    </p>
                    <button id="intro-toggle" class="mt-2 text-sm font-medium text-orange-600 hover:text-orange-700">See more</button>

                    <div class="mt-6 flex items-center space-x-6 text-sm text-gray-500">
                        <div class="flex items-center">
                            <i class="fas fa-blender mr-2 text-gray-500"></i>
                            <div>
                                <span class="font-bold">Prep Time:</span> <?= htmlspecialchars((int)($recipe['prep_time_minutes'] ?? 0)) ?> min
                            </div>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-fire mr-2 text-orange-500"></i>
                            <div>
                                <span class="font-bold">Cook Time:</span> <?= htmlspecialchars($recipe['cooking_time']) ?> min
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <div class="recipe-content">
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Ingredients</h3>
                            <div id="ingredients-content" class="section-collapsed">
                              <ul class="text-gray-700">
                                <?php foreach (explode("\n", $recipe['ingredients']) as $ingredient): ?>
                                    <?php if(trim($ingredient)): ?>
                                        <li><?= htmlspecialchars(trim($ingredient)) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                              </ul>
                            </div>
                            <button id="ingredients-toggle" class="mt-2 text-sm font-medium text-orange-600 hover:text-orange-700">See more</button>

                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4 mt-8">Instructions</h3>
                            <div id="instructions-content" class="section-collapsed">
                              <ol class="text-gray-700 space-y-4">
                                <?php foreach (explode("\n", $recipe['instructions']) as $instruction): ?>
                                     <?php if(trim($instruction)): ?>
                                        <li><?= htmlspecialchars(trim($instruction)) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                              </ol>
                            </div>
                            <button id="instructions-toggle" class="mt-2 text-sm font-medium text-orange-600 hover:text-orange-700">See more</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const intro = document.getElementById('intro-text');
        const toggle = document.getElementById('intro-toggle');
        if (!intro || !toggle) return;

        // Determine if content exceeds 2 lines; if not, hide toggle and show full text
        const style = window.getComputedStyle(intro);
        const lineHeight = parseFloat(style.lineHeight) || 20;

        // Temporarily remove clamps to measure full height
        intro.classList.remove('clamp-2', 'intro-expanded');
        intro.style.maxHeight = 'none';
        const fullHeight = intro.scrollHeight;
        // Clear inline override so CSS classes can control height
        intro.style.maxHeight = '';

        const twoLineHeight = lineHeight * 2 + 1; // small buffer
        if (fullHeight <= twoLineHeight) {
          toggle.style.display = 'none';
          // keep text fully visible, no clamp
        } else {
          // start clamped
          intro.classList.add('clamp-2');
          toggle.style.display = '';
          let expanded = false;
          toggle.addEventListener('click', () => {
            expanded = !expanded;
            if (expanded) {
              intro.classList.remove('clamp-2');
              intro.classList.add('intro-expanded'); // scrollable with fixed max height
              toggle.textContent = 'See less';
            } else {
              // collapsing: reset scroll to top
              intro.scrollTop = 0;
              intro.classList.remove('intro-expanded');
              intro.classList.add('clamp-2');
              toggle.textContent = 'See more';
            }
          });
        }
      });
      // Generic toggle helper for sections
      document.addEventListener('DOMContentLoaded', function () {
        function setupSectionToggle(contentId, buttonId) {
          const el = document.getElementById(contentId);
          const btn = document.getElementById(buttonId);
          if (!el || !btn) return;
          // Decide if toggle needed (content taller than collapsed max)
          const originalMax = parseFloat(window.getComputedStyle(el).maxHeight || '140');
          // Ensure we can measure full height
          el.classList.remove('section-collapsed', 'section-expanded');
          el.style.maxHeight = 'none';
          const full = el.scrollHeight;
          el.style.maxHeight = '';
          let expanded = false;
          if (full <= originalMax + 1) {
            btn.style.display = 'none';
            // keep fully visible without toggling
          } else {
            el.classList.add('section-collapsed');
            btn.addEventListener('click', () => {
              expanded = !expanded;
              if (expanded) {
                el.classList.remove('section-collapsed');
                el.classList.add('section-expanded');
                btn.textContent = 'See less';
              } else {
                // collapsing: reset scroll to top
                el.scrollTop = 0;
                el.classList.remove('section-expanded');
                el.classList.add('section-collapsed');
                btn.textContent = 'See more';
              }
            });
          }
        }
        setupSectionToggle('ingredients-content', 'ingredients-toggle');
        setupSectionToggle('instructions-content', 'instructions-toggle');
      });
    </script>

</body>
</html>

