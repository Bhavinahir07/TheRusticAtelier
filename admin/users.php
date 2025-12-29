<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Access control: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/users.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$username = htmlspecialchars($_SESSION['user']['username'] ?? 'Admin');

// Fetch users (keep backend logic minimal and intact)
$stmt = $conn->prepare('SELECT id, username, email, role, date_joined, account_status FROM users ORDER BY id DESC');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pick up feedback from delete action
$users_feedback = $_SESSION['users_feedback'] ?? null;
unset($_SESSION['users_feedback']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Users • Admin</title>
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
      --danger: #ef4444;
      --success: #22c55e
    }

    body {
      margin: 0;
      background: radial-gradient(1200px 600px at 20% -10%, rgba(124, 58, 237, .18), transparent),
        radial-gradient(1000px 500px at 100% 0%, rgba(34, 211, 238, .14), transparent),
        linear-gradient(180deg, #0b0f19 0%, #0f172a 100%);
      color: var(--text);
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      margin: 0
    }

    a {
      text-decoration: none;
      color: inherit
    }

    .wrap {
      max-width: 1100px;
      margin: 34px auto;
      padding: 0 18px
    }

    /* Admin header (matching other admin pages, no hamburger) */
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
      margin: 8px 0 18px
    }

    .title {
      font-size: 1.6rem;
      font-weight: 800
    }

    .sub {
      color: var(--muted)
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 12px;
      padding: 10px 14px;
      font-weight: 700;
      border: 1px solid transparent;
      color: var(--text)
    }

    .btn-primary {
      background: linear-gradient(135deg, #7c3aed, #8b5cf6);
      border-color: transparent;
      color: white;
      box-shadow: 0 10px 24px rgba(124, 58, 237, .35)
    }

    .btn-primary:hover {
      filter: brightness(1.05)
    }

    .btn-soft {
      background: rgba(255, 255, 255, .06);
      border: 1px solid var(--border)
    }

    .btn-soft:hover {
      background: rgba(255, 255, 255, .1)
    }

    .btn-danger {
      background: rgba(239, 68, 68, .15);
      border: 1px solid rgba(239, 68, 68, .35);
      color: #fecaca
    }

    .btn-danger:hover {
      filter: brightness(1.05)
    }

    .card {
      background: linear-gradient(180deg, #0d1424, #0c1220);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 22px 60px rgba(2, 6, 23, .55);
      overflow: hidden
    }

    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 18px;
      border-bottom: 1px solid var(--border)
    }

    .toolbar {
      display: flex;
      gap: 10px;
      flex-wrap: wrap
    }

    .table-wrap {
      overflow: auto
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0
    }

    thead th {
      position: sticky;
      top: 0;
      background: #0e1527;
      border-bottom: 1px solid var(--border);
      text-align: left;
      padding: 12px 14px;
      font-size: .95rem;
      color: #c7d2fe
    }

    tbody td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      color: #e5e7eb
    }

    tbody tr:hover {
      background: rgba(255, 255, 255, .03)
    }

    tbody td .role {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 9999px;
      font-size: .8rem;
      border: 1px solid var(--border);
      color: #a5b4fc
    }

    .search {
      display: flex;
      gap: 8px;
      align-items: center;
      background: #0b1324;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 8px 10px;
      color: #94a3b8
    }

    .search input {
      background: transparent;
      border: none;
      outline: none;
      color: var(--text);
      min-width: 220px
    }

    .empty {
      padding: 22px;
      color: #94a3b8
    }

    /* Feedback banner */
    .feedback {
      margin: 12px 0;
      padding: 12px;
      border-radius: 12px
    }

    .ok {
      background: rgba(34, 197, 94, .12);
      border: 1px solid rgba(34, 197, 94, .35);
      color: #bbf7d0
    }

    .err {
      background: rgba(239, 68, 68, .12);
      border: 1px solid rgba(239, 68, 68, .35);
      color: #fecaca
    }

    /* Flash fade */
    .flash-card {
      transition: opacity .5s ease, transform .5s ease
    }

    .flash-hide {
      opacity: 0;
      transform: translateY(-6px)
    }
  </style>
</head>

<body>
  <?php $__page_title = 'Users';
  include __DIR__ . '/_topbar.php'; ?>

  <main class="wrap">
    <div class="page-head">
      <div>
        <div class="title">Manage Users</div>
        <div class="sub">View registered users and manage access</div>
      </div>
      <div class="toolbar">
        <a href="<?= BASE_URL ?>admin/index.php" class="btn btn-soft"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back</a>
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
      </div>
    </div>

    <?php if ($users_feedback): ?>
      <div class="feedback <?= $users_feedback['type'] === 'success' ? 'ok' : 'err' ?> flash-card" data-flash>
        <?= htmlspecialchars($users_feedback['text']) ?>
      </div>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <div class="search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="search" id="filter" placeholder="Search by username or email..." />
        </div>
      </div>
      <div class="table-wrap">
        <table id="usersTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined On</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($users): ?>
              <?php foreach ($users as $u): ?>

                <tr>
                  <td><?= (int)$u['id'] ?></td>
                  <td><?= htmlspecialchars($u['username']) ?></td>
                  <td><?= htmlspecialchars($u['email']) ?></td>

                  <td><span class="role"><?= htmlspecialchars($u['role']) ?></span></td>
                  <td>
                    <?php $status = $u['account_status'] ?? 'active'; ?>
                    <span class="role" style="color: <?= $status === 'active' ? 'var(--success)' : 'var(--danger)' ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($u['date_joined']) ?></td>
                  <td>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                      <?php if ($status === 'active'): ?>
                        <form method="POST" action="<?= BASE_URL ?>admin/deactivate_user.php" onsubmit="return confirm('Deactivate user &quot;<?= htmlspecialchars($u['username']) ?>&quot;?');">
                          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                          <button type="submit" class="btn btn-danger" title="Deactivate user"><i class="fa-solid fa-user-slash"></i>&nbsp;Deactivate</button>
                        </form>
                      <?php else: ?>
                        <form method="POST" action="<?= BASE_URL ?>admin/reactivate_user.php">
                          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                          <button type="submit" class="btn btn-soft" style="color:var(--success); border-color:var(--success);" title="Reactivate user"><i class="fa-solid fa-user-check"></i>&nbsp;Reactivate</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="empty">No users found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    // Simple client-side filter (non-destructive, backend unchanged)
    const filter = document.getElementById('filter');
    const table = document.getElementById('usersTable');
    filter?.addEventListener('input', () => {
      const q = filter.value.toLowerCase();
      for (const tr of table.tBodies[0].rows) {
        const user = (tr.cells[1]?.innerText || '').toLowerCase();
        const email = (tr.cells[2]?.innerText || '').toLowerCase();
        tr.style.display = (user.includes(q) || email.includes(q)) ? '' : 'none';
      }
    });
  </script>
  <script>
    // Auto-hide feedback and refresh page after a short delay
    (function() {
      const flash = document.querySelector('[data-flash]');
      if (!flash) return;
      const hideAfterMs = 2800;
      setTimeout(() => {
        flash.classList.add('flash-hide');
        setTimeout(() => {
          try {
            flash.remove();
          } catch (e) {}
        }, 600);
      }, hideAfterMs);
      // Refresh slightly after hide to clear any transient state
      setTimeout(() => {
        try {
          window.location.reload();
        } catch (e) {}
      }, hideAfterMs + 900);
    })();
  </script>
</body>

</html>