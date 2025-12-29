<?php
// shared_recipe_admin.php
declare(strict_types=1);

// session_start();
// require_once "db.php"; // must only define $conn (PDO) and NOT echo anything
require_once __DIR__ . "/config/init.php";

// ---------- Helper: slugify + unique slug ----------
function slugify(string $text): string
{
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'recipe';
}
function unique_slug(PDO $conn, string $base): string
{
    $slug = slugify($base);
    $check = $conn->prepare("SELECT COUNT(*) FROM recipes WHERE slug = ?");
    $i = 1;
    $candidate = $slug;
    while (true) {
        $check->execute([$candidate]);
        $cnt = (int)$check->fetchColumn();
        if ($cnt === 0) return $candidate;
        $i++;
        $candidate = $slug . '-' . $i;
    }
}

// ---------- Admin Session Check ----------
$username     = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? null;
$sessionRole  = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? null;
$isAdminFlag  = isset($_SESSION['is_admin']) ? (bool)$_SESSION['is_admin'] : false;

$isAdmin = false;
if ($sessionRole && strtolower((string)$sessionRole) === 'admin') {
    $isAdmin = true;
} elseif ($isAdminFlag) {
    $isAdmin = true;
} elseif ($username) {
    try {
        $q = $conn->prepare("SELECT role FROM users WHERE username = ? LIMIT 1");
        $q->execute([$username]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row && strtolower((string)$row['role']) === 'admin') {
            $isAdmin = true;
        }
    } catch (Throwable $e) {
    }
}

if (!$username) {
    header("Location: login.php");
    exit;
}
if (!$isAdmin) {
    header("Location: login.php");
    exit;
}

// ---------- Actions ----------
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'update_category' && $id > 0) {
        $catRaw = $_POST['category_id'] ?? '';
        $categoryId = (trim($catRaw) === '' ? null : (int)$catRaw);
        $u = $conn->prepare("UPDATE shared_recipes SET category_id = ? WHERE id = ?");
        $u->execute([$categoryId, $id]);
        $flash = "Category updated.";
    } elseif ($action === 'approve' && $id > 0) {
        $conn->beginTransaction();
        try {
            $s = $conn->prepare("SELECT * FROM shared_recipes WHERE id = ? FOR UPDATE");
            $s->execute([$id]);
            $r = $s->fetch(PDO::FETCH_ASSOC);

            if ($r) {
                $title       = $r['title'] ?? 'Recipe';
                $slug        = unique_slug($conn, $title);
                $intro       = $r['intro'] ?? '';
                $ingredients = $r['ingredients'] ?? '';
                $content     = $r['instructions'] ?? '';
                $prepTime    = $r['prep_time_minutes'] ?? null;
                $cookTime    = $r['cook_time_minutes'] ?? null;
                $image       = $r['image_file'] ?? null;
                $categoryId  = $r['category_id'] ?? 1;

                // Insert again only if not already in recipes
                $check = $conn->prepare("SELECT COUNT(*) FROM recipes WHERE title = ? AND intro = ?");
                $check->execute([$title, $intro]);
                $exists = (int)$check->fetchColumn();

                if ($exists === 0) {
                    $ins = $conn->prepare("
                        INSERT INTO recipes
                            (title, slug, image, intro, content, created_at, category_id, cooking_time, ingredients)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)
                    ");
                    $ins->execute([
                        $title,
                        $slug,
                        $image,
                        $intro,
                        $content,
                        $categoryId,
                        $cookTime,
                        $ingredients
                    ]);
                }

                $u = $conn->prepare("UPDATE shared_recipes SET status = 'approved', updated_at = NOW() WHERE id = ?");
                $u->execute([$id]);

                $conn->commit();
                $flash = "Recipe approved and published.";
            } else {
                $conn->rollBack();
                $flash = "Recipe not found.";
            }
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $flash = "Error approving recipe: " . $e->getMessage();
        }
    } elseif ($action === 'reject' && $id > 0) {
        $u = $conn->prepare("UPDATE shared_recipes SET status = 'rejected', updated_at = NOW() WHERE id = ?");
        $u->execute([$id]);
        $flash = "Recipe rejected.";
    } elseif ($action === 'unpublish' && $id > 0) {
        // Remove from recipes table so it disappears from index.php
        $s = $conn->prepare("SELECT title, intro FROM shared_recipes WHERE id = ?");
        $s->execute([$id]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $d = $conn->prepare("DELETE FROM recipes WHERE title = ? AND intro = ?");
            $d->execute([$r['title'], $r['intro']]);
        }

        $u = $conn->prepare("UPDATE shared_recipes SET status = 'unpublished', updated_at = NOW() WHERE id = ?");
        $u->execute([$id]);
        $flash = "Recipe unpublished (removed from live site).";
    }

    header("Location: shared_recipe_admin.php" . ($flash ? "?flash=" . urlencode($flash) : ""));
    exit;
}

// ---------- Fetch Data ----------
$cats = [];
try {
    $cstmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    $cats = $cstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$filter = strtolower($_GET['status'] ?? 'all');
$where  = "1=1";
$params = [];
if (in_array($filter, ['pending', 'approved', 'rejected', 'unpublished'], true)) {
    $where = "sr.status = ?";
    $params[] = $filter;
}
$sql = "
    SELECT sr.*, u.username AS submitted_by, c.name AS category_name
    FROM shared_recipes sr
    LEFT JOIN users u ON u.id = sr.user_id
    LEFT JOIN categories c ON c.id = sr.category_id
    WHERE $where
    ORDER BY sr.created_at DESC, sr.id DESC
";
$st = $conn->prepare($sql);
$st->execute($params);
$recipes = $st->fetchAll(PDO::FETCH_ASSOC);

$displayUser = $username;

// Helper for display labels
function status_label(string $s): string
{
    return ucfirst(strtolower($s));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Shared Recipes – Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/shared_recipe_admin.css">
</head>

<body>
    <!-- <header>
        <h1>MyRecipe • Shared Recipes (Admin)</h1>
        <div class="user">Logged in as: <strong><?= htmlspecialchars($displayUser) ?></strong><a href="logout.php">Logout</a></div>
    </header> -->

    <header>
        <h1>Manage Recipes</h1>
        <div class="auth-buttons">
            <span class="admin-user-label">Logged in as: <?= $username ?></span>
            <a href="logout.php">
                <button class="btn">Logout</button>
            </a>
        </div>
    </header>

    <div class="add-buttons">
        <a class="back-link" href="admin.php">&larr; Back to admin panel</a>
    </div>

    <div class="wrap">
        <?php if (!empty($_GET['flash'])): ?><div class="flash"><?= htmlspecialchars($_GET['flash']) ?></div><?php endif; ?>
        <div class="toolbar">
            <form method="get" action="shared_recipe_admin.php">
                <label for="status">Filter:</label>
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="unpublished" <?= $filter === 'unpublished' ? 'selected' : '' ?>>Unpublished</option>
                </select>
            </form>
        </div>
        <?php if (!$recipes): ?>
            <div class="card pad">
                <div class="muted">No recipes found for this filter.</div>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($recipes as $r):
                    $status = strtolower($r['status']);
                ?>
                    <div class="card">
                        <?php if (!empty($r['image_file'])): ?>
                            <img class="thumb" src="<?= htmlspecialchars(ltrim((string)$r['image_file'], '/')) ?>" alt="Recipe Image">
                        <?php else: ?><div class="thumb"></div><?php endif; ?>
                        <div class="pad">
                            <h3><?= htmlspecialchars($r['title'] ?: 'Untitled') ?></h3>
                            <div class="muted">By: <?= htmlspecialchars($r['submitted_by'] ?? 'Unknown') ?></div>
                            <div class="meta">
                                <?php if (!empty($r['intro'])): ?><p><strong>Intro:</strong> <?= htmlspecialchars($r['intro']) ?></p><?php endif; ?>
                                <?php if (!empty($r['prep_time_minutes'])): ?><p><strong>Prep Time:</strong> <?= htmlspecialchars($r['prep_time_minutes']) ?></p><?php endif; ?>
                                <?php if (!empty($r['cook_time_minutes'])): ?><p><strong>Cook Time:</strong> <?= htmlspecialchars($r['cook_time_minutes']) ?></p><?php endif; ?>
                                <p><strong>Category:</strong> <?= htmlspecialchars($r['category_name'] ?? 'Not selected') ?></p>
                            </div>
                            <div class="row">
                                <span class="badge <?= $status ?>"><?= status_label($status) ?></span>
                            </div>

                            <div class="row">
                                <a class="btn" href="view_shared_recipe.php?id=<?= (int)$r['id'] ?>">View Full Recipe</a>
                            </div>

                            <div class="row">
                                <?php if ($status === 'pending'): ?>
                                    <form method="post" action="shared_recipe_admin.php" onsubmit="return confirm('Approve this recipe?');">
                                        <input type="hidden" name="action" value="approve" />
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                        <button class="btn" type="submit">Approve & Publish</button>
                                    </form>
                                    <form method="post" action="shared_recipe_admin.php" onsubmit="return confirm('Reject this recipe?');">
                                        <input type="hidden" name="action" value="reject" />
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                        <button class="btn" type="submit">Reject</button>
                                    </form>
                                <?php elseif ($status === 'approved'): ?>
                                    <form method="post" action="shared_recipe_admin.php" onsubmit="return confirm('Unpublish this recipe?');">
                                        <input type="hidden" name="action" value="unpublish" />
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                        <button class="btn" type="submit">Unpublish</button>
                                    </form>
                                <?php elseif ($status === 'unpublished'): ?>
                                    <form method="post" action="shared_recipe_admin.php" onsubmit="return confirm('Re-approve and republish this recipe?');">
                                        <input type="hidden" name="action" value="approve" />
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                        <button class="btn" type="submit">Re-Approve & Publish</button>
                                    </form>
                                <?php elseif ($status === 'rejected'): ?>
                                    <form method="post" action="shared_recipe_admin.php" onsubmit="return confirm('Approve this previously rejected recipe?');">
                                        <input type="hidden" name="action" value="approve" />
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                        <button class="btn" type="submit">Approve & Publish</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2025 MyRecipe. All rights reserved.</p>
        <a href="about.php">About Us</a>
        <a href="share_recipe.php">Share Recipe</a>
        <a href="product.php">Our Products</a>
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <div class="footer-social">
            <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>
</body>

</html>