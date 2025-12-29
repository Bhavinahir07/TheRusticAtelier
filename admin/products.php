<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Access control: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/products.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$errors = [];
$flash  = '';

// Handle Add Product with image upload (rules like add_recipes.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $name = trim($_POST['name'] ?? '');
  $price = trim($_POST['price'] ?? '');
  $category = strtolower(trim($_POST['category'] ?? 'veg'));
  $image_path = '';

  if ($name === '') $errors[] = 'Product name is required.';
  if ($price === '' || !is_numeric($price) || (float)$price < 0) $errors[] = 'Valid price is required.';
  if (!in_array($category, ['veg', 'non-veg'], true)) $errors[] = 'Invalid category selected.';

  // Image required on create
  if (empty($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Please upload a product image.';
  }

  if (!$errors) {
    // Validate and store image to images/products/
    if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      $errors[] = 'File upload error (code ' . (int)$_FILES['image']['error'] . ').';
    } else {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($_FILES['image']['tmp_name']);
      $allowed = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
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
      $stmt = $conn->prepare('INSERT INTO products (name, price, image, category) VALUES (:name, :price, :image, :category)');
      $stmt->execute([
        ':name' => $name,
        ':price' => (float)$price,
        ':image' => $image_path,
        ':category' => $category,
      ]);
      $flash = 'Product added successfully!';
      $_POST = [];
    } catch (Throwable $e) {
      $errors[] = 'Failed to add product. ' . $e->getMessage();
    }
  }
}

// Fetch products list
try {
  $stmt = $conn->query('SELECT id, name, price, image, category FROM products ORDER BY id DESC');
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $products = [];
}

// Pick up feedback from delete action
$products_feedback = $_SESSION['products_feedback'] ?? null;
unset($_SESSION['products_feedback']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Products</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    :root {
      --bg: #0b0f19;
      --panel: #0f172a;
      --muted: #9aa4b2;
      --text: #e6e9ef;
      --primary: #7c3aed;
      --border: rgba(255, 255, 255, .08);
      --accent: #22d3ee;
      --danger: #ef4444;
      --success: #22c55e
    }

    body {
      margin: 0;
      background: radial-gradient(1200px 600px at 20% -10%, rgba(124, 58, 237, .18), transparent),
        radial-gradient(1000px 500px at 100% 0%, rgba(34, 211, 238, .14), transparent),
        linear-gradient(180deg, #0b0f19 0%, #0f172a 100%);
      color: var(--text);
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif
    }

    a {
      text-decoration: none;
      color: inherit
    }

    .wrap {
      max-width: 1100px;
      margin: 36px auto;
      padding: 0 18px
    }

    /* Topbar styles (match other admin pages) */
    .admin-topbar {
      position: sticky;
      top: 0;
      background: linear-gradient(180deg, #0b0f19, #0f172a);
      border-bottom: 1px solid var(--border);
      padding: 14px 18px;
      margin-bottom: 12px
    }

    .admin-topbar__inner {
      display: flex;
      align-items: center;
      gap: 12px
    }

    .admin-topbar .page-title {
      font-weight: 800;
      font-size: 1.6rem
    }

    .admin-identity {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .admin-badge {
      background: rgba(124, 58, 237, .18);
      border: 1px solid rgba(124, 58, 237, .35);
      color: #c4b5fd;
      padding: 2px 8px;
      border-radius: 9999px;
      font-weight: 800
    }

    .admin-user span {
      color: var(--muted)
    }

    .page-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px
    }

    .page-title {
      font-size: 1.6rem;
      font-weight: 800
    }

    .sub {
      color: var(--muted);
      font-size: .95rem
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 12px;
      padding: 10px 14px;
      font-weight: 600;
      border: 1px solid transparent
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), #8b5cf6);
      border-color: transparent;
      color: white;
      box-shadow: 0 10px 24px rgba(124, 58, 237, .35)
    }

    .btn-primary:hover {
      filter: brightness(1.05)
    }

    .btn-soft {
      background: rgba(255, 255, 255, .06);
      border-color: var(--border);
      color: var(--text)
    }

    .btn-soft:hover {
      background: rgba(255, 255, 255, .1)
    }

    /* Disabled look mirroring add_recipes */
    .btn[disabled],
    .btn:disabled {
      opacity: .45;
      filter: saturate(.2) brightness(.95);
      cursor: not-allowed;
      box-shadow: none
    }

    .btn-primary[disabled],
    .btn-primary:disabled {
      background: rgba(124, 58, 237, .25);
      border: 1px solid var(--border);
      color: #cbd5e1
    }

    .card {
      background: linear-gradient(180deg, #0d1424, #0c1220);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 22px;
      box-shadow: 0 20px 55px rgba(2, 6, 23, .55)
    }

    .grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px
    }

    @media (min-width: 980px) {
      .grid {
        grid-template-columns: 1fr
      }
    }

    .field label {
      font-weight: 700;
      margin-bottom: 6px;
      display: block;
      color: #eaeef5
    }

    input,
    select {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--border);
      border-radius: 12px;
      background: #0b1324;
      color: var(--text)
    }

    input:focus,
    select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(34, 211, 238, .15)
    }

    .hint {
      color: #9aa4b2;
      font-size: .85rem
    }

    .error {
      background: rgba(239, 68, 68, .12);
      border: 1px solid rgba(239, 68, 68, .35);
      color: #fecaca;
      padding: 12px;
      border-radius: 12px;
      margin-bottom: 14px
    }

    .success {
      background: rgba(34, 197, 94, .12);
      border: 1px solid rgba(34, 197, 94, .35);
      color: #bbf7d0;
      padding: 12px;
      border-radius: 12px;
      margin-bottom: 14px
    }

    .table-wrap {
      overflow: auto
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0
    }

    thead {
      background: rgba(255, 255, 255, .04)
    }

    th,
    td {
      padding: 12px 14px;
      text-align: left
    }

    tbody tr {
      border-top: 1px solid var(--border)
    }

    tbody tr:hover {
      background: rgba(255, 255, 255, .03)
    }

    .img-thumb {
      width: 60px;
      /* slightly square is better for bottles */
      height: 60px;
      object-fit: contain;
      /* Ensures the whole product is seen */
      background: #fff;
      /* White background makes the product pop */
      padding: 2px;
      /* Adds a tiny border inside */
      border-radius: 8px;
      border: 1px solid var(--border);
    }

    .actions {
      white-space: nowrap
    }

    /* Floating Add button (match recipes) */
    .recipes-fab {
      position: fixed;
      right: 22px;
      bottom: 22px;
      width: 46px;
      height: 46px;
      border-radius: 9999px;
      display: grid;
      place-items: center;
      color: #fff;
      background: linear-gradient(135deg, #7c3aed, #8b5cf6);
      box-shadow: 0 10px 30px rgba(124, 58, 237, .35);
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, .12)
    }

    .recipes-fab:hover {
      filter: brightness(1.05)
    }

    .actions .btn {
      padding: 8px 10px;
      border-radius: 10px
    }
  </style>
</head>

<body>
  <?php $__page_title = 'Products';
  include __DIR__ . '/_topbar.php'; ?>
  <main class="wrap">
    <div class="page-head">
      <div>
        <div class="page-title">Manage Products</div>
        <div class="sub">Add new products and view your list.</div>
      </div>
      <div class="toolbar">
        <a href="<?= BASE_URL ?>admin/index.php" class="btn btn-soft"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back</a>
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
      </div>
    </div>

    <?php if ($products_feedback): ?>
      <div class="card flash-card" data-flash style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.08); margin-bottom:14px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($products_feedback['text']) ?>
      </div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="card flash-card" data-flash style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.08); margin-bottom:14px;">
        <?php foreach ($errors as $e): ?>
          <div>• <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <section class="grid">
      <!-- Products List with actions -->
      <article class="card">
        <div style="font-weight:800;font-size:1.1rem;margin-bottom:6px">Products</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price (₹)</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($products): ?>
                <?php foreach ($products as $p): ?>
                  <?php
                  $imgPath = trim((string)($p['image'] ?? ''));
                  if ($imgPath === '') {
                    $imgUrl = BASE_URL . 'images/placeholders/recipe-cover.svg';
                  } elseif (preg_match('~^https?://~i', $imgPath)) {
                    $imgUrl = $imgPath;
                  } else {
                    $imgUrl = rtrim(BASE_URL, '/') . '/' . ltrim($imgPath, '/');
                  }
                  $imgUrl = str_replace(' ', '%20', $imgUrl);
                  ?>
                  <tr>
                    <td><?= (int)$p['id'] ?></td>
                    <td><img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="img-thumb" onerror="this.onerror=null;this.src='<?= BASE_URL ?>images/placeholders/recipe-cover.svg';" /></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><span class="hint"><?= htmlspecialchars($p['category']) ?></span></td>
                    <td>₹<?= htmlspecialchars(number_format((float)$p['price'], 2)) ?></td>
                    <td class="actions">
                      <a class="btn btn-soft" href="<?= BASE_URL ?>admin/edit_product.php?id=<?= (int)$p['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                      <a class="btn btn-soft" href="<?= BASE_URL ?>admin/delete_product.php?id=<?= (int)$p['id'] ?>" title="Delete" onclick="return confirm('Delete this product?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="hint" style="padding:16px">No products found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    </section>
  </main>
  <a href="<?= BASE_URL ?>admin/add_product.php" class="recipes-fab" title="Add Product"><i class="fa-solid fa-plus"></i></a>
</body>

</html>