<?php
// admin/shared.php — Admin moderation for user shared recipes
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin-only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/shared.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$errors = [];
$flash  = isset($_GET['flash']) ? (string)$_GET['flash'] : '';

// ---------- Helpers ----------
function slugify_str(string $text): string
{
  $text = trim($text);
  $text = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  return $text ?: 'recipe';
}
function unique_slug(PDO $conn, string $base): string
{
  $slug = slugify_str($base);
  $check = $conn->prepare('SELECT COUNT(*) FROM recipes WHERE slug = ?');
  $i = 1;
  $candidate = $slug;
  while (true) {
    $check->execute([$candidate]);
    if ((int)$check->fetchColumn() === 0) return $candidate;
    $candidate = $slug . '-' . (++$i);
  }
}
// ---------- Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  try {
    if ($action === 'approve') {
      if ($id <= 0) throw new RuntimeException('Invalid submission id.');
      $conn->beginTransaction();
      $s = $conn->prepare('SELECT * FROM shared_recipes WHERE id = ? FOR UPDATE');
      $s->execute([$id]);
      $r = $s->fetch(PDO::FETCH_ASSOC);

      if (!$r) {
        throw new RuntimeException('Recipe not found.');
      }

      $title       = (string)($r['title'] ?? 'Recipe');
      $customSlug  = (string)($r['slug'] ?? ''); // Get your new custom slug

      // Use the custom slug if provided, otherwise fall back to the title
      $baseSlug = ($customSlug !== '') ? $customSlug : $title;
      $slug     = unique_slug($conn, $baseSlug);
      $intro       = (string)($r['intro'] ?? '');
      $ingredients = (string)($r['ingredients'] ?? '');
      $content     = (string)($r['instructions'] ?? '');
      // $prepTime    = $r['prep_time_minutes'] ?? null; // not stored in recipes table currently
      $cookTime    = $r['cooking_time'] ?? null; // stored in recipes.cooking_time
      $image       = (string)($r['image'] ?? '');
      $categoryId  = (int)($r['category_id'] ?? 0);
      $userId      = $r['user_id'] ?? null; // To create a data link between recipes table and users table
      if ($categoryId <= 0) {
        throw new RuntimeException('Please select a Category before approving so it shows under the right section on Home.');
      }

      // Prevent duplicate publish: match on title+intro
      $ck = $conn->prepare('SELECT COUNT(*) FROM recipes WHERE title = ? AND intro = ?');
      $ck->execute([$title, $intro]);
      $exists = (int)$ck->fetchColumn();

      if ($exists === 0) {
        // IMPORTANT: align with site schema used by index/recipe_details
        // columns: title, slug, image, intro, content, created_at, category_id, cooking_time, ingredients
        // Added "user_id" to the columns list
        $ins = $conn->prepare('INSERT INTO recipes (title, slug, image, intro, content, created_at, category_id, user_id, cooking_time, ingredients) VALUES (?,?,?,?,?,NOW(),?,?,?,?)');
        // Added $userId to the execute array
        $ins->execute([$title, $slug, $image, $intro, $content, $categoryId, $userId, $cookTime, $ingredients]);
      }

      $u = $conn->prepare("UPDATE shared_recipes SET status = 'approved', updated_at = NOW() WHERE id = ?");
      $u->execute([$id]);
      $conn->commit();
      $flash = 'Recipe approved and published under its category.';
    } elseif ($action === 'reject' && $id > 0) {
      $u = $conn->prepare("UPDATE shared_recipes SET status = 'rejected', updated_at = NOW() WHERE id = ?");
      $u->execute([$id]);
      $flash = 'Recipe rejected.';
    } elseif ($action === 'unpublish' && $id > 0) {
      // Remove from live recipes table so it disappears from home/categories
      $s = $conn->prepare('SELECT title, intro FROM shared_recipes WHERE id = ?');
      $s->execute([$id]);
      $r = $s->fetch(PDO::FETCH_ASSOC);
      if ($r) {
        $d = $conn->prepare('DELETE FROM recipes WHERE title = ? AND intro = ?');
        $d->execute([(string)$r['title'], (string)$r['intro']]);
      }
      $u = $conn->prepare("UPDATE shared_recipes SET status = 'unpublished', updated_at = NOW() WHERE id = ?");
      $u->execute([$id]);
      $flash = 'Recipe unpublished.';
    }
  } catch (Throwable $e) {
    if ($conn->inTransaction()) {
      try {
        $conn->rollBack();
      } catch (Throwable $__) {
      }
    }
    $errors[] = $e->getMessage();
  }

  // PRG redirect
  $qs = $flash ? ('?flash=' . urlencode($flash)) : '';
  header('Location: ' . BASE_URL . 'admin/shared.php' . $qs);
  exit;
}

// ---------- Data ----------
$filterStatus = $_GET['status'] ?? '';
$allowedStatus = ['', 'pending', 'approved', 'rejected', 'unpublished', 'deactivated_by_user', 'deactivated_by_admin'];
if (!in_array($filterStatus, $allowedStatus, true)) {
  $filterStatus = '';
}

try {
  $sql = 'SELECT sr.id, sr.title, sr.intro, sr.image, sr.status, sr.created_at,
                 sr.cooking_time, sr.category_id,
                 u.username, u.email,
                 c.name AS category_name
          FROM shared_recipes sr
          LEFT JOIN users u ON u.id = sr.user_id
          LEFT JOIN categories c ON c.id = sr.category_id';
  $params = [];
  if ($filterStatus !== '') {
    $sql .= ' WHERE sr.status = :st';
    $params[':st'] = $filterStatus;
  }
  $sql .= ' ORDER BY sr.id DESC';
  $st = $conn->prepare($sql);
  $st->execute($params);
  $shared = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $shared = [];
  $errors[] = 'Failed to load shared recipes.';
}

$categories = [];
try {
  $cs = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
  $categories = $cs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

function badgeClass(string $status): string
{
  $s = strtolower($status);
  return [
    'pending' => 'badge pending',
    'approved' => 'badge approved',
    'rejected' => 'badge rejected',
    'deactivated_by_user' => 'badge rejected',  // <-- Added
    'deactivated_by_admin' => 'badge rejected', // <-- Added
    'unpublished' => 'badge'
  ][$s] ?? 'badge';
}
function mins_h($m)
{
  $m = (int)$m;
  if ($m <= 0) return '—';
  $h = intdiv($m, 60);
  $r = $m % 60;
  if ($h && $r) return $h . 'h ' . $r . 'm';
  if ($h) return $h . 'h';
  return $r . 'm';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Shared Recipes</title>
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
      --success: #22c55e;
      --warn: #f59e0b
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
      max-width: 1180px;
      margin: 36px auto;
      padding: 0 18px
    }

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

    .page-title {
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

    .sub {
      color: var(--muted)
    }

    .check-rcp {
      margin-bottom: 6px;
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

    .btn-soft {
      background: rgba(255, 255, 255, .06);
      border: 1px solid var(--border);
      color: var(--text)
    }

    .btn-soft:hover {
      background: rgba(255, 255, 255, .1)
    }

    .card {
      background: linear-gradient(180deg, #0d1424, #0c1220);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 22px;
      box-shadow: 0 20px 55px rgba(2, 6, 23, .55)
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
      text-align: left;
      vertical-align: top
    }

    tbody tr {
      border-top: 1px solid var(--border)
    }

    tbody tr:hover {
      background: rgba(255, 255, 255, .03)
    }

    .img-thumb {
      width: 90px;
      height: 70px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid var(--border)
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      border: 1px solid var(--border);
      font-weight: 700;
      font-size: .8rem
    }

    .badge.pending {
      background: rgba(245, 158, 11, .12);
      border-color: rgba(245, 158, 11, .35);
      color: #fcd34d
    }

    .badge.approved {
      background: rgba(34, 197, 94, .12);
      border-color: rgba(34, 197, 94, .35);
      color: #bbf7d0
    }

    .badge.rejected {
      background: rgba(239, 68, 68, .12);
      border-color: rgba(239, 68, 68, .35);
      color: #fecaca
    }

    .actions {
      white-space: nowrap
    }

    /* Flash message fade-out */
    .flash-card {
      transition: opacity .5s ease, transform .5s ease
    }

    .flash-hide {
      opacity: 0;
      transform: translateY(-6px)
    }

    /* Filter box styling */
    .filter-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, .06);
      border: 1px solid var(--border);
      padding: 10px 12px;
      border-radius: 12px
    }

    .filter-bar label {
      color: var(--muted);
      font-weight: 600
    }

    .filter-bar select {
      background: #0b1324;
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 8px 12px;
      min-width: 160px
    }

    .filter-bar button {
      background: rgba(255, 255, 255, .06);
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: 10px;
      padding: 8px 12px
    }

    .filter-bar button:hover {
      background: rgba(255, 255, 255, .1)
    }

    .intro {
      color: #cbd5e1;
      max-width: 440px
    }

    .time {
      color: #9aa4b2;
      font-size: .9rem
    }
  </style>
</head>

<body>
  <?php $__page_title = 'User Shared Recipes';
  include __DIR__ . '/_topbar.php'; ?>
  <main class="wrap">
    <div class="page-head">
      <div>
        <div class="page-title">User Shared Recipes</div>
        <div class="sub">Review, approve or reject submissions.</div>
      </div>
      <div class="toolbar">
        <a href="<?= BASE_URL ?>admin/index.php" class="btn btn-soft"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back</a>
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="card flash-card" data-flash style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.08); margin-bottom:14px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="card" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.08); margin-bottom:14px;">
        <?php foreach ($errors as $e): ?>
          <div>• <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <section class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div style="font-weight:800;font-size:1.1rem">Submissions</div>
        <form method="get" class="filter-bar">
          <label for="status" class="sub">Filter:</label>
          <?php $sel = $filterStatus; ?>
          <select id="status" name="status">
            <option value="" <?= ($sel === '' ? 'selected' : '') ?>>All</option>
            <option value="pending" <?= ($sel === 'pending' ? 'selected' : '') ?>>Pending</option>
            <option value="approved" <?= ($sel === 'approved' ? 'selected' : '') ?>>Approved</option>
            <option value="rejected" <?= ($sel === 'rejected' ? 'selected' : '') ?>>Rejected</option>
            <option value="unpublished" <?= ($sel === 'unpublished' ? 'selected' : '') ?>>Unpublished</option>
            <option value="deactivated_by_user" <?= ($sel === 'deactivated_by_user' ? 'selected' : '') ?>>Deactivated (User)</option>
            <option value="deactivated_by_admin" <?= ($sel === 'deactivated_by_admin' ? 'selected' : '') ?>>Deactivated (Admin)</option>
          </select>
          <button class="btn btn-soft" type="submit"><i class="fa-solid fa-filter"></i>&nbsp;Apply</button>
        </form>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Image</th>
              <th>Title & Intro</th>
              <th>User</th>
              <th>Category</th>
              <th>Times</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($shared): ?>
              <?php foreach ($shared as $row): ?>
                <?php
                $img = trim((string)($row['image'] ?? ''));
                if ($img === '') {
                  $imgUrl = BASE_URL . 'images/placeholders/recipe-cover.svg';
                } elseif (preg_match('~^https?://~i', $img)) {
                  $imgUrl = $img;
                } else {
                  $imgUrl = rtrim(BASE_URL, '/') . '/' . ltrim($img, '/');
                }
                $imgUrl = str_replace(' ', '%20', $imgUrl);
                $st = strtolower((string)$row['status']);
                ?>
                <tr>
                  <td><?= (int)$row['id'] ?></td>
                  <td><img src="<?= htmlspecialchars($imgUrl) ?>" alt="cover" class="img-thumb" onerror="this.onerror=null;this.src='<?= BASE_URL ?>images/placeholders/recipe-cover.svg';" /></td>
                  <td>
                    <div style="font-weight:700; margin-bottom:4px;"><?= htmlspecialchars($row['title'] ?: 'Untitled') ?></div>
                    <div class="intro"><?= htmlspecialchars(mb_strimwidth($row['intro'] ?? '', 0, 120, '…')) ?></div>
                  </td>
                  <td>
                    <div><?= htmlspecialchars($row['username'] ?? '—') ?></div>
                    <div class="sub" style="font-size:.85rem"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                  </td>
                  <td><span class="sub"><?= htmlspecialchars($row['category_name'] ?? '—') ?></span></td>
                  <td class="time">
                    Cook: <?= htmlspecialchars($row['cooking_time'] ?? '—') ?>
                  </td>
                  <td><span class="<?= badgeClass((string)$row['status']) ?>"><?= htmlspecialchars(ucfirst((string)$row['status'])) ?></span></td>
                  <td class="actions">
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                      <?php if (in_array($st, ['pending', 'rejected', 'unpublished', 'deactivated_by_user', 'deactivated_by_admin'])): ?>
                        <form method="post" onsubmit="return confirm('Approve and publish this recipe?');">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                          <div class="check-rcp">
                            <a href="edit_shared_recipe.php?id=<?= (int)$row['id'] ?>" class="btn btn-soft">Edit</a>
                          </div>
                          <button class="btn btn-soft" type="submit" name="action" value="approve"><i class="fa-solid fa-circle-check"></i>&nbsp;Approve</button>
                        </form>
                      <?php endif; ?>
                      <?php if ($st === 'pending'): ?>
                        <form method="post" onsubmit="return confirm('Reject this recipe?');">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                          <button class="btn btn-soft" type="submit" name="action" value="reject"><i class="fa-solid fa-ban"></i>&nbsp;Reject</button>
                        </form>
                      <?php endif; ?>
                      <?php if ($st === 'approved'): ?>
                        <form method="post" onsubmit="return confirm('Unpublish this recipe (remove from live site)?');">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                          <button class="btn btn-soft" type="submit" name="action" value="unpublish"><i class="fa-solid fa-eye-slash"></i>&nbsp;Unpublish</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="sub" style="padding:16px">No shared recipes found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <script>
    // Auto-hide flash after a short delay and remove ?flash= from URL
    (function() {
      const flash = document.querySelector('[data-flash]');
      if (!flash) return;
      const hideAfterMs = 2800; // ~2.8s
      setTimeout(() => {
        flash.classList.add('flash-hide');
        setTimeout(() => {
          flash.remove();
        }, 600);
      }, hideAfterMs);
      try {
        const url = new URL(window.location.href);
        if (url.searchParams.has('flash')) {
          url.searchParams.delete('flash');
          window.history.replaceState({}, document.title, url.toString());
        }
      } catch (e) {}
    })();
  </script>
</body>

</html>