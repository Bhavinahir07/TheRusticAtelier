<?php
require_once __DIR__ . "/config/init.php";
$is_authenticated = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
$success = '';
$error = '';

// fetch categories for dropdown
try {
    $catStmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_authenticated) {
        header("Location: login.php");
        exit();
    }

    $recipeName = trim($_POST['recipeName'] ?? '');
    $intro = trim($_POST['intro'] ?? '');
    $ingredients = trim($_POST['recipeIngredients'] ?? '');
    $instructions = trim($_POST['recipeInstruction'] ?? '');
    $category_id = isset($_POST['category_id']) && ctype_digit((string)$_POST['category_id']) ? (int)$_POST['category_id'] : null;

    // ==================================================================
    // 1. (CHANGED) New Time Handling Logic
    // ==================================================================
    $times_array = $_POST['times'] ?? [];
    $time_strings = []; // To store formatted strings like "Prep: 20 minutes"

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

    // Join all the time strings with a " | " separator
    // This creates the single string for the database
    // e.g., "Prep: 20 minutes | Bake: 30-35 minutes | Total: ~1 hour"
    // Use double space to match the regex split in recipe_details.php
    $cooking_time_string = implode("  ", $time_strings);    // ==================================================================
    // End of Change
    // ==================================================================


    // Image upload handling
    if (isset($_FILES['recipeFile']) && $_FILES['recipeFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['recipeFile']['tmp_name'];
        $fileName = basename($_FILES['recipeFile']['name']);
        $targetDir = 'images/user_recipes/';
        $targetPath = $targetDir . uniqid() . "_" . $fileName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($fileTmp);

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Only JPG, PNG, GIF, or WEBP images are allowed.";
        } else {
            if (move_uploaded_file($fileTmp, $targetPath)) {

                // ==================================================================
                // 2. (CHANGED) Updated SQL INSERT Query
                // ==================================================================
                // We now insert into the 'cooking_time' column we created.
                $stmt = $conn->prepare("INSERT INTO shared_recipes 
                        (title, intro, ingredients, cooking_time, instructions, image, category_id, user_id)
                        VALUES (:title, :intro, :ingredients, :cooking_time, :instructions, :image, :category_id, :user_id)");

                $stmt->bindValue(':title', $recipeName);
                $stmt->bindValue(':intro', $intro);
                $stmt->bindValue(':ingredients', $ingredients);

                // Bind the new single string to the 'cooking_time' column
                $stmt->bindValue(':cooking_time', $cooking_time_string);

                $stmt->bindValue(':instructions', $instructions);
                $stmt->bindValue(':image', $targetPath);

                if ($category_id === null) {
                    $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
                }
                $stmt->bindValue(':user_id', $user['id'] ?? null, isset($user['id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);

                $stmt->execute();
                // ==================================================================
                // End of Change
                // ==================================================================

                $_SESSION['flash_success'] = "Recipe uploaded successfully! It is now pending review.";
                header("Location: my_recipe_view.php");
                exit();
            } else {
                $error = "Image upload failed.";
            }
        }
    } else {
        $error = "Please upload a valid image.";
    }
}

// Flash message handling
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Share Your Recipe</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/share_recipe.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap"
        rel="stylesheet">

    <style>
        /* --- Modern Dynamic Field Styles --- */
        .time-component-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            /* Smooth fade-in animation for new rows */
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .time-component-row select {
            flex-basis: 35%;
            flex-shrink: 0;
            cursor: pointer;
        }

        .time-component-row input {
            flex-grow: 1;
        }

        /* Modern 'Ghost' Remove Button */
        .time-component-row .remove-time-btn {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            border: none;
            background: transparent;
            color: #9ca3af;
            /* Soft gray default */
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .time-component-row .remove-time-btn:hover {
            background-color: #fee2e2;
            /* Soft red background */
            color: #dc2626;
            /* Deep red icon */
            transform: scale(1.1);
        }

        /* 'Add Button' that matches the Orange Theme */
        #add-time-btn {
            background-color: #fff;
            color: #f97316;
            /* Theme Orange */
            border: 2px dashed #f97316;
            /* Dashed border indicates 'insert here' */
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        #add-time-btn:hover {
            background-color: #fff7ed;
            /* Very light orange tint */
            border-style: solid;
            /* Solid border on hover */
            transform: translateY(-2px);
        }
    </style>

</head>

<body>
    <div class="menu-overlay"></div>

    <?php
    $activePage = "share_recipe";
    $showCapsuleEffect = false;
    include __DIR__ . "/partials/header.php";
    ?>

    <div class="recipe-form-container">
        <h2>Share Your Recipe</h2>


        <form enctype="multipart/form-data" method="post">
            <div class="form-group">
                <label for="recipeName">Recipe Name:</label>
                <input type="text" id="recipe-name" class="form-control" name="recipeName" required>
            </div>

            <div class="form-group">
                <label for="intro">Intro (Short Description):</label>
                <textarea id="intro" class="form-control" name="intro" rows="2" required></textarea>
            </div>

            <div class="form-group">
                <label for="recipeIngredients">Ingredients:</label>
                <textarea id="ingredients" name="recipeIngredients" class="form-control" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label>Recipe Timings</label>
                <div id="time-components-container" class="mt-2">
                </div>
                <button type="button" id="add-time-btn" class="mt-2">+ Add Time Component</button>
            </div>
            <div class="form-group">
                <label for="recipeInstruction">Instructions:</label>
                <textarea id="instructions" class="form-control" name="recipeInstruction" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <option value="">-- Select a Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Recipe Image</label>
                <div class="file-upload-wrapper" onclick="document.getElementById('recipeFile').click()">
                    <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p>Click to upload an image</p>
                    <small class="text-muted" id="file-name-display">No file chosen</small>
                </div>
                <input type="file" name="recipeFile" id="recipeFile" accept="image/*" style="display:none;"
                    onchange="updateFileNameAndPreview(this)" required>
                <div id="image-preview-container">
                    <img id="image-preview" src="#" alt="Image Preview" />
                </div>
            </div>

            <button type="submit" id="submit-btn" class="submit-btn">Submit Recipe</button>
        </form>
    </div>

    <script>
        function updateFileNameAndPreview(input) {
            const fileNameSpan = document.getElementById("file-name-display");
            const previewContainer = document.getElementById("image-preview-container");
            const previewImage = document.getElementById("image-preview");

            if (input.files && input.files.length > 0) {
                fileNameSpan.textContent = input.files[0].name;

                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);

            } else {
                fileNameSpan.textContent = "No file chosen";
                previewContainer.style.display = 'none';
            }
        }

        // --- New JavaScript for Dynamic Time Fields ---
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('time-components-container');
            const addButton = document.getElementById('add-time-btn');
            let timeIndex = 0; // To keep track of the array index for PHP

            // Function to add a new time row
            function addTimeRow() {
                const newRow = document.createElement('div');
                newRow.className = 'time-component-row';

                // This HTML will be added
                newRow.innerHTML = `
                    <select name="times[${timeIndex}][type]" class="form-select form-control" required>
                        <option value="">-- Select Type --</option>
                        <option value="Preparation">Preperation</option>
                        <option value="Cooking">Cooking</option>
                        <option value="Baking">Baking</option>
                        <option value="Rising">Rising / Proofing</option>
                        <option value="Resting">Resting</option>
                        <option value="Marinating">Marinating</option>
                        <option value="Chilling">Chilling / Cooling</option>
                        <option value="Active-Work">Active Work</option>
                        <option value="Fermantation">Fermantation</option>
                        <option value="Shaping">Shaping</option>
                        <option value="Soaking">Soaking</option>
                        <option value="Assembly">Assembly / Decorating</option>
                        <option value="Total-Time">Total Time</option>
                    </select>
                    
                    <input type="text" name="times[${timeIndex}][value]" 
                           placeholder="e.g., 20 minutes" 
                           class="form-control" required>
                    
                    <button type="button" class="remove-time-btn" title="Remove time">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                container.appendChild(newRow);
                timeIndex++; // Increment index for the next row
            }

            // Add a new row when the button is clicked
            addButton.addEventListener('click', addTimeRow);

            // Add event listener for remove buttons (using event delegation)
            container.addEventListener('click', function(e) {
                // Use .closest() to find the button, even if the user clicked the icon <i> inside it
                const removeBtn = e.target.closest('.remove-time-btn');

                if (removeBtn) {
                    // If the button was found, remove the row
                    removeBtn.closest('.time-component-row').remove();
                }
            });

            // Add one row by default when the page loads
            addTimeRow();

            // --- Removed old validation JS ---
            // The 'required' attribute on the form fields will now handle
            // validation when the user clicks submit.
        });
    </script>
    <footer>
        <p>&copy; 2025 MyRecipe. All rights reserved.</p>
        <a href="index.php">Home Page</a>
        <a href="about_us.php">About Us</a>
        <a href="#">Our Products</a>
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <div class="footer-social">
            <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i
                    class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i
                    class="fab fa-instagram"></i></a>
        </div>
    </footer>
</body>

</html>