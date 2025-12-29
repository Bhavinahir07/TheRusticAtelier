<?php
// session_start();
// require_once 'db.php';
require_once __DIR__ . "/config/init.php";

// --- Auth check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Access denied. Only admin can access this page.");
}

// Get categories for the select
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$message = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $intro = trim($_POST['intro'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $cooking_time = trim($_POST['cooking_time'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    // validate minimal fields
    if ($title === '') $errors[] = "Title is required.";
    if ($slug === '')  $errors[] = "Slug is required.";

    // IMAGE UPLOAD HANDLING
    $image_path = null; // what we'll store in DB
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error (code {$_FILES['image']['error']}).";
        } else {
            // validate mime type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['image']['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/pjpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif'
            ];

            if (!isset($allowed[$mime])) {
                $errors[] = "Only JPG, PNG and GIF images are allowed.";
            } else {
                // sanitize original name
                $ext = $allowed[$mime];
                $origBasename = basename($_FILES['image']['name']);
                $cleanBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origBasename);
                // force correct extension
                $cleanBase = preg_replace('/\.[^.]+$/', '', $cleanBase) . '.' . $ext;

                // $target_dir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
                // New Corrected Line
                $target_dir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'recipes' . DIRECTORY_SEPARATOR;
                $target_file = $target_dir . $cleanBase;

                // create folder if doesn't exist
                if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
                    $errors[] = "Failed to create images directory on server.";
                } else {
                    if (file_exists($target_file)) {
                        // ✅ File already exists — don't copy
                        // $image_path = 'images/' . $cleanBase;
                        // New Corrected Line
                        $image_path = 'images/recipes/' . $cleanBase;
                    } else {
                        // ❌ New file — move it
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                            @chmod($target_file, 0644);
                            // $image_path = 'images/' . $cleanBase;
                            // New Corrected Line
                            $image_path = 'images/recipes/' . $cleanBase;
                        } else {
                            $errors[] = "Failed to move uploaded file.";
                        }
                    }
                }
            }
        }
    }

    // If no errors, insert into DB
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO recipes (title, slug, image, intro, content, ingredients, cooking_time, category_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $title,
                $slug,
                $image_path,
                $intro,
                $description,
                $ingredients,
                $cooking_time,
                $category_id
            ]);
            $message = "Recipe added successfully.";
            // optionally redirect
            header("Location: manage_recipes.php?msg=" . urlencode($message));
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Recipe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .container {
            max-width: 720px;
            margin: auto;
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        /* Styled file input container */
        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-button {
            background-color: black;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            display: inline-block;
            cursor: pointer;
        }

        .file-upload-button:hover {
            background-color: #333;
        }

        .file-name {
            margin-left: 10px;
            font-size: 14px;
        }

        button[type="submit"] {
            background-color: black;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }

        button[type="submit"]:hover {
            background-color: rgb(238, 150, 17);
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Add New Recipe</h2>

        <?php if (!empty($message)): ?>
            <p style="color:green"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div style="color:red">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Title</label>
            <input type="text" name="title" value="<?= isset($title) ? htmlspecialchars($title) : '' ?>" required>

            <label>Slug</label>
            <input type="text" name="slug" value="<?= isset($slug) ? htmlspecialchars($slug) : '' ?>" required>

            <label>Category</label>
            <select name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= (isset($category_id) && $category_id == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="image">Recipe Image</label>
            <div class="file-upload-wrapper">
                <button type="button" class="file-upload-button">Choose File</button>
                <input type="file" name="image" id="image" accept="image/*" onchange="updateFileName(this)">
                <span class="file-name" id="file-name">No file chosen</span>
            </div>

            <label>Intro</label>
            <textarea name="intro"><?= isset($intro) ? htmlspecialchars($intro) : '' ?></textarea>

            <label>Ingredients</label>
            <textarea name="ingredients"><?= isset($ingredients) ? htmlspecialchars($ingredients) : '' ?></textarea>

            <label>Cooking time</label>
            <input type="text" name="cooking_time" value="<?= isset($cooking_time) ? htmlspecialchars($cooking_time) : '' ?>">

            <label>Description</label>
            <textarea name="description"><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>

            <button type="submit">Add Recipe</button>
        </form>
    </div>
    <script>
        function updateFileName(input) {
            // Find the span element next to the button
            const fileNameSpan = document.getElementById('file-name');

            // Check if a file was selected
            if (input.files && input.files.length > 0) {
                // Display the name of the selected file
                fileNameSpan.textContent = input.files[0].name;
            } else {
                // If no file is chosen, show the default text
                fileNameSpan.textContent = 'No file chosen';
            }
        }
    </script>
</body>

</html>