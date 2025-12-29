<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    $_SESSION['redirect_after_login'] = 'admin/products.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$errors = [];
$flash = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category = strtolower(trim($_POST['category'] ?? 'vegetarian'));

    if ($name === '') $errors[] = 'Product name is required.';
    if ($price === '' || !is_numeric($price) || (float)$price < 0) $errors[] = 'Valid price is required.';
    if (!in_array($category, ['vegetarian','non-vegetarian','vegan'], true)) $errors[] = 'Invalid category selected.';

    // Handle optional image upload
    $image_path = '';
    if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error (code ' . (int)$_FILES['image']['error'] . ').';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg', 'image/pjpeg' => 'jpg',
                'image/png' => 'png', 'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            if (!isset($allowed[$mime])) {
                $errors[] = 'Only JPG, PNG, GIF, WEBP images are allowed.';
            } else {
                $ext = $allowed[$mime];
                $origBasename = basename($_FILES['image']['name']);
                $cleanBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origBasename);
                $cleanBase = preg_replace('/\.[^.]+$/', '', $cleanBase) . '.' . $ext; // force ext
                $targetDir = BASE_PATH . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;
                $targetFile = $targetDir . $cleanBase;
                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                    $errors[] = 'Failed to create images directory on server.';
                } else {
                    if (file_exists($targetFile)) {
                        $image_path = 'images/products/' . $cleanBase;
                    } else {
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                            @chmod($targetFile, 0644);
                            $image_path = 'images/products/' . $cleanBase;
                        } else {
                            $errors[] = 'Failed to move uploaded file.';
                        }
                    }
                }
            }
        }
    }

    if (!$errors) {
        try {
            $ins = $conn->prepare('INSERT INTO products (name, price, image, category) VALUES (:name, :price, :image, :category)');
            $ins->execute([
                ':name' => $name,
                ':price' => (float)$price,
                ':image' => $image_path,
                ':category' => $category,
            ]);
            $_SESSION['products_feedback'] = ['type' => 'success', 'text' => 'Product created successfully!'];
            header('Location: ' . BASE_URL . 'admin/products.php');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to create product. ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Product</title>
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
                <h1 class="text-3xl font-bold text-white">Add Product</h1>
                <p class="text-gray-400">Create a new product for your store</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>admin/products.php" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors no-underline">
                    <i class="fa-solid fa-arrow-left"></i> Back to Products
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left Column: Details -->
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Product Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Enter product name" required />
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-300 mb-2">Price (₹)</label>
                        <input type="number" step="0.01" min="0" id="price" name="price" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="0.00" required />
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                        <select id="category" name="category" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="vegetarian" <?= ((($_POST['category'] ?? 'vegetarian') === 'vegetarian') ? 'selected' : '') ?>>Vegetarian</option>
                            <option value="non-vegetarian" <?= ((($_POST['category'] ?? '') === 'non-vegetarian') ? 'selected' : '') ?>>Non‑Vegetarian</option>
                            <option value="vegan" <?= ((($_POST['category'] ?? '') === 'vegan') ? 'selected' : '') ?>>Vegan</option>
                        </select>
                    </div>
                </div>

                <!-- Right Column: Image -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Product Image</label>
                    <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-600 px-6 py-10" id="image-dropzone">
                        <div class="text-center">
                            <img id="imagePreview" src="<?= BASE_URL ?>images/placeholders/recipe-cover.svg" alt="Image preview" class="mx-auto h-32 w-32 object-cover rounded-lg mb-4">
                            <div id="image-placeholder">
                                <i class="fa-solid fa-image text-4xl text-gray-500"></i>
                                <div class="mt-4 flex text-sm leading-6 text-gray-400">
                                    <label for="image" class="relative cursor-pointer rounded-md font-semibold text-purple-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-purple-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-900 hover:text-purple-300">
                                        <span>Upload a file</span>
                                        <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs leading-5 text-gray-500">PNG, JPG, GIF up to 10MB</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" id="submitBtn" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-500 px-5 py-2.5 text-sm font-medium text-white hover:from-purple-500 hover:to-purple-400 disabled:opacity-50 disabled:cursor-not-allowed disabled:from-gray-700 disabled:to-gray-800 transition-all duration-200 shadow-lg" disabled>
                    <i class="fa-solid fa-plus"></i> Create Product
                </button>
            </div>
        </form>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');
            const requiredFields = ['name', 'price', 'category'];
            const fileInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');
            const imagePlaceholder = document.getElementById('image-placeholder');

            function checkFormValidity() {
                // Check required fields
                let allFieldsValid = true;
                for (const fieldName of requiredFields) {
                    const field = document.getElementById(fieldName);
                    if (!field || !field.value || !field.value.trim()) {
                        allFieldsValid = false;
                        break;
                    }
                }

                // Check price is valid number and >= 0
                if (allFieldsValid) {
                    const priceVal = parseFloat(document.getElementById('price').value);
                    if (isNaN(priceVal) || priceVal < 0) {
                        allFieldsValid = false;
                    }
                }

                submitBtn.disabled = !allFieldsValid;
            }

            // Listen for input changes
            requiredFields.forEach(fieldName => {
                document.getElementById(fieldName)?.addEventListener('input', checkFormValidity);
            });

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
            });

            // Initial check
            checkFormValidity();
        });
    </script>
</body>
</html>
