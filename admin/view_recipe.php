<?php
require_once __DIR__ . '/../config/initAdmin.php';

// --- 1. GET AND VALIDATE RECIPE ID ---
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'admin/recipes.php');
    exit;
}

// --- 2. FETCH RECIPE DATA FROM DATABASE ---
$stmt = $conn->prepare(
    'SELECT r.*, c.name AS category_name FROM recipes r LEFT JOIN categories c ON r.category_id = c.id WHERE r.id = :id'
);
$stmt->execute([':id' => $id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if recipe not found
if (!$recipe) {
    header('Location: ' . BASE_URL . 'admin/recipes.php');
    exit;
}

// --- 3. PREPARE VARIABLES FOR DISPLAY ---
$title = $recipe['title'] ?? 'Untitled Recipe';
$category = $recipe['category_name'] ?: 'Uncategorized';
$cooking_time = $recipe['cooking_time'] ?? 'N/A';
$created_at = $recipe['created_at'] ? date('F d, Y', strtotime($recipe['created_at'])) : 'N/A';
$intro = $recipe['intro'] ?? '';
$ingredients = $recipe['ingredients'] ?? '';
$instructions = $recipe['content'] ?? ''; // 'content' column holds the instructions
$image_path = trim($recipe['image'] ?? '');
// Build image URL
if ($image_path === '') {
    $image_url = BASE_URL . 'images/placeholders/recipe-cover.svg';
} else {
    $image_url = rtrim(BASE_URL, '/') . '/' . ltrim($image_path, '/');
}
$image_url = str_replace(' ', '%20', $image_url) . '?v=' . time(); // Cache bust

// Helper function to format text into lists
function format_text_as_list($text, $list_type = 'ul', $class = '') {
    if (empty(trim($text))) return '';
    $items = explode("\n", trim($text));
    $output = "<{$list_type} class='{$class}'>";
    foreach ($items as $item) {
        $trimmed_item = trim($item);
        if (!empty($trimmed_item)) {
            $output .= '<li>' . htmlspecialchars($trimmed_item) . '</li>';
        }
    }
    $output .= "</{$list_type}>";
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Recipe • <?= htmlspecialchars($title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Lora:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background-color: #0b1120;
            color: #e6e9ef;
            font-family: 'Inter', sans-serif;
        }
        .recipe-content ul { list-style-type: disc; padding-left: 1.5rem; }
        .recipe-content ol { list-style-type: decimal; padding-left: 1.5rem; }
        .recipe-content li { margin-bottom: 0.75rem; line-height: 1.7; }
    </style>
</head>
<body>
    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">View Recipe</h1>
                <p class="text-gray-400">Reviewing "<?= htmlspecialchars($title) ?>"</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?= BASE_URL ?>admin/recipes.php" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors no-underline">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="<?= BASE_URL ?>admin/edit_recipe.php?id=<?= (int)$id; ?>" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-500 px-4 py-2 text-sm font-medium text-white hover:from-purple-500 hover:to-purple-400 shadow-lg">
                    <i class="fa-solid fa-pen"></i> Edit
                </a>
            </div>
        </div>

        <div class="bg-gray-900/50 border border-gray-700/50 rounded-2xl overflow-hidden">
            <img src="<?= htmlspecialchars($image_url); ?>" 
                 alt="<?= htmlspecialchars($title); ?>" 
                 class="w-full h-80 object-cover"
                 onerror="this.onerror=null;this.src='<?= BASE_URL ?>images/placeholders/recipe-cover.svg';">
            
            <div class="p-8">
                <header class="flex justify-between items-start gap-4 mb-4">
                    <h1 class="text-4xl font-extrabold text-white leading-tight"><?= htmlspecialchars($title); ?></h1>
                    <span class="mt-1 flex-shrink-0 inline-block bg-purple-500/10 text-purple-300 px-3 py-1 rounded-full text-sm font-medium border border-purple-500/30"><?= htmlspecialchars($category); ?></span>
                </header>

                <div class="flex flex-wrap gap-x-6 gap-y-2 text-gray-400 text-sm border-b border-gray-700/50 pb-6 mb-6">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-purple-400"></i>
                        <span><?= htmlspecialchars($cooking_time); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-purple-400"></i>
                        <span><?= htmlspecialchars($created_at); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($intro)): ?>
                    <p class="text-lg text-gray-300 italic mb-8"><?= htmlspecialchars($intro); ?></p>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="md:col-span-1">
                        <h2 class="text-xl font-semibold text-white mb-4">Ingredients</h2>
                        <div class="recipe-content text-gray-300">
                             <?= format_text_as_list($ingredients, 'ul'); ?>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                         <h2 class="text-xl font-semibold text-white mb-4">Instructions</h2>
                         <div class="recipe-content text-gray-300">
                            <?= format_text_as_list($instructions, 'ol'); ?>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
