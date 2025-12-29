<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    $_SESSION['redirect_after_login'] = 'admin/shared.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'admin/shared.php');
    exit;
}

// Load recipe BEFORE handling POST so we can preserve existing image
$stmt = $conn->prepare('SELECT * FROM shared_recipes WHERE id = :id');
$stmt->execute([':id' => $id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) {
    header('Location: ' . BASE_URL . 'admin/shared.php');
    exit;
}

// Fetch categories
$catsStmt = $conn->prepare('SELECT id, name FROM categories ORDER BY name ASC');
$catsStmt->execute();
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare state
$errors = [];
$flash = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $cooking_time = trim($_POST['cooking_time'] ?? '');
    $image_file = $recipe['image'] ?? null; // keep existing by default
    $slug = trim($_POST['slug'] ?? '');
    $intro = trim($_POST['intro'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    if ($title === '') $errors[] = 'Title is required.';
    // if ($slug === '') $errors[] = 'Slug is required.';

    if (empty($errors)) {
        // Optional image upload
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[] = 'File upload error (code ' . (int)$_FILES['image']['error'] . ').';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['image']['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
                } else {
                    $ext = $allowed[$mime];
                    $origBasename = basename($_FILES['image']['name']);
                    $cleanBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origBasename);
                    $cleanBase = preg_replace('/\.[^.]+$/', '', $cleanBase) . '_' . time() . '.' . $ext;
                    $targetDir = BASE_PATH . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'recipes' . DIRECTORY_SEPARATOR;
                    $targetFile = $targetDir . $cleanBase;

                    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                        $errors[] = 'Failed to create images directory on server.';
                    } else {
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                            @chmod($targetFile, 0644);
                            if (!empty($image_file) && file_exists(BASE_PATH . DIRECTORY_SEPARATOR . $image_file)) {
                                unlink(BASE_PATH . DIRECTORY_SEPARATOR . $image_file);
                            }
                            $image_file = 'images/recipes/' . $cleanBase;
                        } else {
                            $errors[] = 'Failed to move uploaded file.';
                        }
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = 'UPDATE shared_recipes SET title = :title, slug = :slug, image = :image, intro = :intro, instructions = :instructions, '
            . 'category_id = :category_id, cooking_time = :cooking_time, ingredients = :ingredients WHERE id = :id';
        $upd = $conn->prepare($sql);
        $params = [
            ':title' => $title,
            ':slug' => $slug ?: null,
            ':image' => $image_file,
            ':intro' => $intro,
            ':instructions' => $instructions,
            ':category_id' => $category_id ?: null,
            ':cooking_time' => $cooking_time,
            ':ingredients' => $ingredients,
            ':id' => $id,
        ];
        $upd->execute($params);
        $_SESSION['flash_success'] = "Recipe '" . htmlspecialchars($title) . "' updated successfully!";
        header('Location: ' . BASE_URL . 'admin/shared.php');
        exit;
    }
}

// Build image URL for preview
$image_url = ($recipe['image'] ? BASE_URL . htmlspecialchars($recipe['image']) : BASE_URL . 'images/placeholders/recipe-cover.svg');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Recipe • <?= htmlspecialchars($recipe['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background-color: #0b1120;
            color: #e6e9ef;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body>
    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">Edit Recipe</h1>
                <p class="text-gray-400">Update the details and save your changes</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>admin/shared.php" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors no-underline">
                    <i class="fa-solid fa-arrow-left"></i> Back to User Shared Recipes
                </a>
            </div>
        </div>

        <form method="post" class="bg-gray-900/50 border border-gray-700/50 rounded-2xl p-8" autocomplete="off" enctype="multipart/form-data">

            <?php if ($errors): ?>
                <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-lg p-4 space-y-1">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Recipe Details Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-6">Recipe Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Recipe Title</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($recipe['title'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required />
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-300 mb-2">URL Slug (Optional)</label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($recipe['slug'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Auto-generates from title if blank" />
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                        <select id="category_id" name="category_id" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id']; ?>" <?= ((int)$recipe['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="cooking_time" class="block text-sm font-medium text-gray-300 mb-2">Cooking Time</label>
                        <input type="text" id="cooking_time" name="cooking_time" value="<?= htmlspecialchars($recipe['cooking_time'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="e.g., 30 minutes" required />
                    </div>
                </div>
            </div>

            <!-- Recipe Image Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-6">Recipe Image</h2>
                <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-600 px-6 py-10" id="image-dropzone">
                    <div class="text-center">
                        <img id="imagePreview" src="<?= $image_url ?>" alt="Image preview" class="mx-auto h-32 w-32 object-cover rounded-lg mb-4">
                        <div class="mt-4 flex text-sm leading-6 text-gray-400">
                            <label for="image" class="relative cursor-pointer rounded-md font-semibold text-purple-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-purple-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-900 hover:text-purple-300">
                                <span>Change image</span>
                                <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                            </label>
                        </div>
                        <p class="text-xs leading-5 text-gray-500">PNG, JPG, GIF up to 10MB</p>
                    </div>
                </div>
            </div>

            <!-- Recipe Instructions Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-6">Recipe instructions</h2>
                <div class="space-y-6">
                    <div>
                        <label for="intro" class="block text-sm font-medium text-gray-300 mb-2">Introduction</label>
                        <textarea id="intro" name="intro" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="3" placeholder="Brief introduction..."><?= htmlspecialchars($recipe['intro'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label for="ingredients" class="block text-sm font-medium text-gray-300 mb-2">Ingredients</label>
                        <textarea id="ingredients" name="ingredients" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="6" placeholder="One ingredient per line..."><?= htmlspecialchars($recipe['ingredients'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label for="Instructions" class="block text-sm font-medium text-gray-300 mb-2">Instructions</label>
                        <textarea id="instructions" name="instructions" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="8" placeholder="One step per line..."><?= htmlspecialchars($recipe['instructions'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="submitBtn" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-500 px-5 py-2.5 text-sm font-medium text-white hover:from-purple-500 hover:to-purple-400 disabled:opacity-50 disabled:cursor-not-allowed disabled:from-gray-700 disabled:to-gray-800 transition-all duration-200 shadow-lg" disabled>
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');
            const allInputs = form.querySelectorAll('input, select, textarea');
            const fileInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');

            const initialValues = {};
            allInputs.forEach(input => {
                if (input.type !== 'file') {
                    initialValues[input.name] = input.value;
                }
            });

            function checkFormValidity() {
                let isDirty = false;
                allInputs.forEach(input => {
                    if (input.type === 'file') {
                        if (input.files.length > 0) isDirty = true;
                    } else {
                        if (initialValues[input.name] !== input.value) isDirty = true;
                    }
                });
                submitBtn.disabled = !isDirty;
            }

            allInputs.forEach(input => {
                input.addEventListener('input', checkFormValidity);
                input.addEventListener('change', checkFormValidity);
            });

            fileInput?.addEventListener('change', (e) => {
                const file = e.target.files?.[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        imagePreview.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            checkFormValidity();
        });
    </script>
</body>

</html>