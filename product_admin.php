<?php
// product_admin.php
// require_once "db.php";
require_once __DIR__ . "/config/init.php";

// Get product ID from query parameter
if (!isset($_GET['id'])) {
    die("Product ID not provided!");
}
$product_id = intval($_GET['id']);

// Fetch product by ID
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found!");
}

// Handle Update (Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = $_POST['name'];
    $description = $_POST['description'] ?? null;
    $price = $_POST['price'];
    $image = $product['image']; // keep old image if none uploaded

    // Handle file upload if a new image is provided
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . "/images/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;

        // Avoid duplicate images
        if (!file_exists($targetPath)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image = "products/" . $fileName; // store relative path
            }
        } else {
            $image = "products/" . $fileName; // reuse existing file
        }
    }

    $update_stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
    $update_stmt->execute([$name, $description, $price, $image, $product_id]);

    echo "<script>alert('Product updated successfully!'); window.location='manage_products.php';</script>";
    exit;
}

// Handle Delete (only if delete button is clicked)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
    $check_stmt->execute([$product_id]);
    $in_use = $check_stmt->fetchColumn();

    if ($in_use > 0) {
        echo "<script>alert('Cannot delete this product because it is used in orders.'); window.location='manage_products.php';</script>";
        exit;
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->execute([$product_id]);
        echo "<script>alert('Product deleted successfully!'); window.location='manage_products.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/recipe_admin.css">

    <style>
        body {
            background: #f9f9f9;
            font-family: Arial, sans-serif;
        }

        .section {
            display: none;
        }

        .view-section {
            display: block;
        }

        .admin-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* .auth-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        } */

        .button-group {
            text-align: center;
            margin-bottom: 20px;
        }

        .button-group .btn {
            display: inline-block;
            margin: 0 5px;
        }

        .section {
            display: none;
            padding: 15px 0;
            /* keep spacing */
            border: none;
            /* remove extra border */
            border-radius: 0;
            /* remove rounded box */
            background: transparent;
            /* no background box */
        }

        .section.active {
            display: block;
        }

        .btn {
            margin-top: 10px;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }

        .view-btn,
        .edit-btn {
            background: #000;
            color: white;
        }

        .section .delete-btn {
            background: #dd0909ff;
            /* ✅ restore red delete */
            color: white;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .product-img {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 8px;
            display: block;
        }

        /* From add_product.php */
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
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

        .file-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            display: block;
            /* margin: 10px 0 15px 0; */
            margin-top: 10px;
            margin-bottom: 15px;
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

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-upload-button:hover {
            background-color: #333;
        }

        .file-name {
            margin-left: 10px;
            font-size: 14px;
        }
    </style>

    <script>
        function showSection(section) {
            document.querySelectorAll('.section').forEach(el => el.style.display = 'none');
            document.getElementById(section).style.display = 'block';
        }

        function updateFileName(event) {
            const fileName = event.target.files.length > 0 ? event.target.files[0].name : "No file chosen";
            document.getElementById("file-name").textContent = fileName;
        }
    </script>
</head>

<body>
    <div class="admin-container">
        <h1>Manage Product: <?php echo htmlspecialchars($product['name']); ?></h1>
        <!-- <div class="auth-buttons">
            <button class="btn view-btn" onclick="showSection('view')">View</button>
            <button class="btn edit-btn" onclick="showSection('edit')">Edit</button>
            <button class="btn delete-btn" onclick="showSection('delete')">Delete</button>
        </div> -->
        <div class="button-group">
            <button type="button" class="btn" onclick="showSection('view')">View</button>
            <button type="button" class="btn" onclick="showSection('edit')">Edit</button>
            <button type="button" class="btn" onclick="showSection('delete')">Delete</button>
        </div>


        <!-- View Section -->
        <div id="view" class="section view-section" style="display: block;">
            <h2>Product Details</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
            <?php if (!empty($product['description'])): ?>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
            <?php endif; ?>
            <p><strong>Price:</strong> ₹<?php echo htmlspecialchars($product['price']); ?></p>
            <?php if (!empty($product['image'])): ?>
                <img class="product-img" src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">
            <?php endif; ?>
        </div>

        <!-- Edit Section -->
        <div id="edit" class="section" style="display:none;">
            <h2>Edit Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

                <?php if (isset($product['description'])): ?>
                    <label>Description:</label>
                    <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                <?php endif; ?>

                <label>Price:</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>

                <label>Current Image:</label><br>
                <?php if (!empty($product['image'])): ?>
                    <img class="product-img" src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image"><br>
                <?php endif; ?>

                <label>Upload New Image:</label>
                <div class="file-upload-wrapper">
                    <button type="button" class="file-upload-button">Choose File</button>
                    <input type="file" name="image" id="image" accept="image/*" onchange="updateFileName(event)">
                    <span class="file-name" id="file-name">No file chosen</span>
                </div>

                <button type="submit" name="update" class="btn">Update Product</button> <!-- ✅ added back -->
            </form>
        </div>

        <!-- Delete Section -->
        <div id="delete" class="section" style="display:none;">
            <h2>Delete Product</h2>
            <p>Are you sure you want to delete this product?</p>
            <form method="post">
                <button type="submit" name="delete" class="btn delete-btn"
                    onclick="return confirm('Really delete this product?');">Yes, Delete</button>
            </form>
        </div>
    </div>
</body>

</html>