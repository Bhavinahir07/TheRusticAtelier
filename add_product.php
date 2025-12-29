<?php
// add_product.php
// require_once "db.php";
require_once __DIR__ . "/config/init.php";

// (You don't need categories from DB anymore for the product-type select,
// but keeping this line is harmless. Remove if you want.)
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // 1) Read fields
    $name     = trim($_POST['name'] ?? '');
    $price    = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? ''; // vegetarian | non-vegetarian | vegan

    if ($name === '' || $category === '') {
        die("Name and Category are required.");
    }

    // 2) Image upload handling
    // We'll save relative path like: "products/filename.jpg"
    $imagePath = "products/default.png"; // fallback if no image uploaded

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            die("Error uploading image (code " . (int)$_FILES['image']['error'] . ").");
        }

        // Ensure folder exists: htdocs/team_project/images/products
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "products" . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            die("Error: could not create images/products directory.");
        }

        // Validate & normalize extension using MIME (prevents .exe etc.)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['image']['tmp_name']);
        $allowed = [
            'image/jpeg'  => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png'   => 'png',
            'image/gif'   => 'gif',
            'image/webp'  => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            die("Only JPG, PNG, GIF or WEBP images are allowed.");
        }

        // Clean the original base name and force the correct extension
        $origBase = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', $origBase);
        $ext      = $allowed[$mime];
        $fileName = $safeBase . '.' . $ext;

        $targetFile = $uploadDir . $fileName;

        // If the file does not already exist, move it; otherwise reuse existing one.
        if (!file_exists($targetFile)) {
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                die("Error: Failed to move uploaded image.");
            }
            @chmod($targetFile, 0644);
        }

        // Save path with "products/" prefix into DB
        $imagePath = "products/" . $fileName;
    }

    // 3) Insert into database (store $imagePath, not just file name)
    $stmt = $conn->prepare("INSERT INTO products (name, price, category, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $category, $imagePath]);

    echo "<script>alert('Product added successfully!'); window.location='manage_products.php';</script>";
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/recipe_admin.css">

    <style>
        body {
            background: #f9f9f9;
            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-bottom: 20px;
            font-size: 1.6rem;
            text-align: center;
            color: #333;
        }

        /* label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        } */

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
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

        /* Fix: limit the invisible input to just the button */
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            /* ✅ but now relative to wrapper, not form */
            height: 100%;
            /* ✅ same here */
        }

        .file-upload-button:hover {
            background-color: #333;
        }

        .file-name {
            margin-left: 10px;
            font-size: 14px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #333;
            outline: none;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: #000;
            color: #fff;
            transition: 0.2s;
            width: 100%;
            margin-top: 15px;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        /* button[type="submit"] {
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
        } */
    </style>

    <!-- <style>
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
    </style> -->
</head>

<body>

    <div class="form-container">
        <h2>Add New Product</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Product Name:</label>
            <input type="text" name="name" required>

            <label>Price (₹):</label>
            <input type="number" name="price" step="0.01" required>

            <label>Category:</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <option value="vegetarian">Vegetarian</option>
                <option value="non-vegetarian">Non-Vegetarian</option>
                <option value="vegan">Vegan</option>
            </select>

            <label for="image">Product Image:</label>
            <!-- <input type="file" name="image" accept="image/*"> -->
            <div class="file-upload-wrapper">
                <button type="button" class="file-upload-button">Choose File</button>
                <input type="file" name="image" id="image" accept="image/*" onchange="updateFileName(event)" required>
                <span class="file-name" id="file-name">No file chosen</span>
            </div>

            <button type="submit" name="add_product" class="btn">Add Product</button>
        </form>
    </div>

    <script>
        function updateFileName(event) {
            const fileName = event.target.files[0]?.name || "No file chosen";
            document.getElementById("file-name").textContent = fileName;
        }
    </script>


</body>

</html>