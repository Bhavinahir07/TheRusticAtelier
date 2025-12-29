<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    $_SESSION['redirect_after_login'] = 'admin/recipes.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$errors = [];
$adminName = $_SESSION['user']['username'] ?? 'Admin';

// Fetch categories for dropdown
$catsStmt = $conn->prepare('SELECT id, name FROM categories ORDER BY name ASC');
$catsStmt->execute();
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $imageRelPath = '';
    $slug = trim($_POST['slug'] ?? '');
    $intro = trim($_POST['intro'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');

    // ==================================================================
    // 1. (CHANGED) New Time Handling Logic
    // ==================================================================
    $times_array = $_POST['times'] ?? [];
    $time_strings = []; // To store formatted strings

    if (is_array($times_array)) {
        foreach ($times_array as $time_component) {
            // Only add if both type and value are set and not empty
            if (!empty($time_component['type']) && !empty($time_component['value'])) {
                $type = htmlspecialchars($time_component['type']);
                $value = htmlspecialchars($time_component['value']);
                // Format the string and add it to our array
                $time_strings[] = $type . ": " . $value;
            }
        }
    }
    
    // Join all the time strings with TWO SPACES, just like your old recipes
    // Assign to $cooking_time so the rest of the script works
    $cooking_time = implode("  ", $time_strings);
    // ==================================================================
    // End of Change
    // ==================================================================

    // Minimal validations per legacy logic
    if ($title === '') $errors[] = 'Title is required.';
    if ($slug === '') $errors[] = 'Slug is required.';

    if (!$errors) {
        // Legacy-style image upload (optional)
        $image_path = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[] = 'File upload error (code ' . (int)$_FILES['image']['error'] . ').';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['image']['tmp_name']);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/pjpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'wbep',
                    'image/gif' => 'gif'
                ];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Only JPG, PNG and GIF images are allowed.';
                } else {
                    $ext = $allowed[$mime];
                    $origBasename = basename($_FILES['image']['name']);
                    $cleanBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origBasename);
                    $cleanBase = preg_replace('/\.[^.]+$/', '', $cleanBase) . '.' . $ext; // force ext
                    $targetDir = BASE_PATH . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'recipes' . DIRECTORY_SEPARATOR;
                    $targetFile = $targetDir . $cleanBase;
                    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                        $errors[] = 'Failed to create images directory on server.';
                    } else {
                        if (file_exists($targetFile)) {
                            $image_path = 'images/recipes/' . $cleanBase;
                        } else {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                                @chmod($targetFile, 0644);
                                $image_path = 'images/recipes/' . $cleanBase;
                            } else {
                                $errors[] = 'Failed to move uploaded file.';
                            }
                        }
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare(
                    'INSERT INTO recipes (title, slug, image, intro, content, ingredients, cooking_time, category_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                $stmt->execute([
                    $title,
                    $slug,
                    $image_path,
                    $intro,
                    $content,
                    $ingredients,
                    $cooking_time, // This now contains our new formatted string
                    ($category_id ?: null)
                ]);
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }

        // NEW CODE: Only redirect if there are NO errors
        if (empty($errors)) {
            $newId = (int)$conn->lastInsertId();
            header('Location: ' . BASE_URL . 'admin/recipes.php');
            exit;
        }
        // If there IS an error, the script will continue down 
        // and show the error message in the red box on your form.
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Recipe</title>
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
                <h1 class="text-3xl font-bold text-white">Add Recipe</h1>
                <p class="text-gray-400">Create a new recipe by filling the details below</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>admin/recipes.php" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors no-underline">
                    <i class="fa-solid fa-arrow-left"></i> Back to Recipes
                </a>
            </div>
        </div>

        <form method="post" class="bg-gray-900/50 border border-gray-700/50 rounded-2xl p-8" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create" />

            <?php if ($errors): ?>
                <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-lg p-4 space-y-1">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-6">Recipe Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Recipe Title</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Enter recipe title" required />
                        <small class="text-gray-500 text-xs mt-1 block">Example: Creamy Tomato Soup</small>
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-300 mb-2">URL Slug</label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="enter-url-slug" style="max-width:420px" required />
                        <small class="text-gray-500 text-xs mt-1 block">URL-friendly identifier</small>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                        <select id="category_id" name="category_id" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id']; ?>" <?= ((string)($_POST['category_id'] ?? '') === (string)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Recipe Times</label>
                        <div id="time-components-container" class="space-y-3">
                            </div>
                        <button type="button" id="add-time-btn" class="mt-3 text-sm font-medium text-purple-400 hover:text-purple-300">
                            <i class="fa-solid fa-plus"></i> Add Time Component
                        </button>
                    </div>
                    </div>
            </div>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-6">Recipe Image</h2>
                <div class="flex justify-center">
                    <div class="w-full max-w-md">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Cover Image</label>
                        <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-600 px-6 py-10" id="image-dropzone">
                            <div class="text-center">
                                <img id="imagePreview" src="<?= BASE_URL ?>images/placeholders/recipe-cover.svg" alt="Image preview" class="mx-auto h-32 w-32 object-cover rounded-lg mb-4">
                                <div id="image-placeholder">
                                    <i class="fa-solid fa-image text-4xl text-gray-500"></i>
                                    <div class="mt-4 flex text-sm leading-6 text-gray-400">
                                        <label for="image" class="relative cursor-pointer rounded-md font-semibold text-purple-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-purple-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-900 hover:text-purple-300">
                                            <span>Upload a file</span>
                                            <input id="image" name="image" type="file" class="sr-only" accept="image/*" required>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs leading-5 text-gray-500">PNG, JPG, PNG, WEBP or GIF up to 10MB</p>
                                </div>
                            </div>
                        </div>
                        <small class="text-gray-500 text-xs mt-2 block text-center">Upload a recipe cover image (JPG, PNG, GIF, WEBP)</small>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-6">Recipe Content</h2>
                <div class="space-y-6">
                    <div>
                        <label for="intro" class="block text-sm font-medium text-gray-300 mb-2">Introduction</label>
                        <textarea id="intro" name="intro" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="3" placeholder="Brief introduction shown with the recipe card container..." required><?= htmlspecialchars($_POST['intro'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label for="ingredients" class="block text-sm font-medium text-gray-300 mb-2">Ingredients</label>
                        <textarea id="ingredients" name="ingredients" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="6" placeholder="List each ingredient on a new line&#10;Example:&#10;For Dough:&#10;&#x09;2 cups '00' flour&#10;&#x09;1 cup butter&#10;&#x09;2 cloves garlic..." required><?= htmlspecialchars($_POST['ingredients'] ?? '') ?></textarea>
                        <small class="text-gray-500 text-xs mt-1 block">One ingredient per line</small>
                    </div>
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-300 mb-2">Instructions</label>
                        <textarea id="content" name="content" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="8" placeholder="Write each step on a new line&#10;Example:&#10;1. Prepare the dough&#10;&#x09;Add oil in a bowl&#10;2. Ready baking pan&#10;&#x09; Prepare the baking pan for dough&#10;3. Shape the crust..." required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        <small class="text-gray-500 text-xs mt-1 block">One instruction per line</small>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="submitBtn" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-500 px-5 py-2.5 text-sm font-medium text-white hover:from-purple-500 hover:to-purple-400 disabled:opacity-50 disabled:cursor-not-allowed disabled:from-gray-700 disabled:to-gray-800 transition-all duration-200 shadow-lg" disabled>
                    <i class="fa-solid fa-plus"></i> Create Recipe
                </button>
            </div>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');
            // Removed 'cooking_time' from this array
            const requiredFields = ['title', 'slug', 'category_id', 'intro', 'ingredients', 'content'];
            const fileInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');
            const imagePlaceholder = document.getElementById('image-placeholder');

            // --- New JavaScript for Dynamic Time Fields ---
            const timeContainer = document.getElementById('time-components-container');
            const addButton = document.getElementById('add-time-btn');
            let timeIndex = 0; // To keep track of the array index for PHP

            // Function to add a new time row
            function addTimeRow() {
                const newRow = document.createElement('div');
                // Use Tailwind classes for layout
                newRow.className = 'flex items-center space-x-3 time-row';

                // This HTML will be added, styled with Tailwind classes
                newRow.innerHTML = `
                    <select name="times[${timeIndex}][type]" 
                            class="w-1/3 bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                            required>
                        <option value="">-- Select Type --</option>
                        <option value="Preparation">Preparation</option>
                        <option value="Baking">Baking</option>
                        <option value="Cooking">Cooking</option>
                        <option value="Cooling">Cooling</option>
                        <option value="Rising/Fermentation">Rising/Fermentation</option>
                        <option value="Frosting">Frosting</option>
                        <option value="Resting">Resting</option>
                        <option value="Marinating">Marinating</option>
                        <option value="Total">Total Time</option>
                    </select>
                    
                    <input type="text" name="times[${timeIndex}][value]" 
                           placeholder="e.g., 20 minutes" 
                           class="flex-grow bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                           required>
                    
                    <button type="button" 
                            class="remove-time-btn flex-shrink-0 text-red-400 p-2 hover:text-red-300 rounded-full" 
                            title="Remove time">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                `;

                timeContainer.appendChild(newRow);
                timeIndex++; // Increment index for the next row
                checkFormValidity(); // Re-check validity when a row is added
            }

            // Add a new row when the button is clicked
            addButton.addEventListener('click', addTimeRow);

            // Add event listener for remove buttons (using event delegation)
            timeContainer.addEventListener('click', function(e) {
                const removeButton = e.target.closest('.remove-time-btn');
                if (removeButton) {
                    // Find the parent 'time-row' and remove it
                    removeButton.closest('.time-row').remove();
                    checkFormValidity(); // Re-check validity when a row is removed
                }
            });

            // Add one row by default when the page loads
            addTimeRow();
            // --- End of new JS ---


            function checkFormValidity() {
                // Check required text/select fields
                let allFieldsValid = true;
                for (const fieldName of requiredFields) {
                    const field = document.getElementById(fieldName);
                    if (!field || !field.value || !field.value.trim()) {
                        allFieldsValid = false;
                        break;
                    }
                }
                
                // Also check if at least one time row exists
                const timeRows = timeContainer.querySelectorAll('.time-row input');
                if (timeRows.length === 0) {
                    allFieldsValid = false;
                } else {
                    // Check if all *existing* time rows are filled
                    timeRows.forEach(input => {
                        if (input.value.trim() === '') allFieldsValid = false;
                    });
                }

                submitBtn.disabled = !allFieldsValid;
            }

            // Listen for input changes on all required fields
            requiredFields.forEach(fieldName => {
                document.getElementById(fieldName)?.addEventListener('input', checkFormValidity);
                document.getElementById(fieldName)?.addEventListener('change', checkFormValidity);
            });
            
            // Also listen for changes in the time container
            timeContainer.addEventListener('input', checkFormValidity);

            // Image preview logic
            fileInput?.addEventListener('change', (e) => {
                const file = e.target.files?.[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        imagePreview.src = event.target.result;
                        imagePreview.classList.remove('hidden');
                        imagePlaceholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                }
                checkFormValidity(); // Re-check after file is selected
            });

            // Initial check
            checkFormValidity();
        });
    </script>
</body>

</html>