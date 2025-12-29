<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Access control: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/stats.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$username = htmlspecialchars($_SESSION['user']['username'] ?? 'Admin');

// Fetch user visit stats (username, visit_count, last_visit)
try {
  $stmt = $conn->query("SELECT username, visit_count, last_visit FROM users ORDER BY visit_count DESC LIMIT 10");
  $userVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $userVisits = [];
}

$labels = array_map(fn($r) => $r['username'], $userVisits);
$visits = array_map(fn($r) => (int)$r['visit_count'], $userVisits);
$lasts  = array_map(fn($r) => $r['last_visit'], $userVisits);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Site Stats • Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root{--bg:#0b0f19;--panel:#0f172a;--muted:#9aa4b2;--text:#e6e9ef;--primary:#7c3aed;--border:rgba(255,255,255,.08)}
    body{margin:0;background:radial-gradient(1200px 600px at 20% -10%, rgba(124,58,237,.18), transparent),
                  radial-gradient(1000px 500px at 100% 0%, rgba(34,211,238,.14), transparent),
                  linear-gradient(180deg,#0b0f19 0%,#0f172a 100%);color:var(--text);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0}
    a{text-decoration:none;color:inherit}
    .wrap{max-width:1100px;margin:34px auto;padding:0 18px}

    .admin-topbar{position:sticky;top:0;background:linear-gradient(180deg,#0b0f19,#0f172a);border-bottom:1px solid var(--border);padding:14px 18px;margin-bottom:12px}
    .admin-topbar__inner{display:flex;align-items:center;gap:12px}
    .admin-topbar .page-title{font-weight:800;font-size:1.6rem}
    .admin-identity{margin-left:auto;display:flex;align-items:center;gap:10px}
    .admin-badge{background:rgba(124,58,237,.18);border:1px solid rgba(124,58,237,.35);color:#c4b5fd;padding:2px 8px;border-radius:9999px;font-weight:800}
    .admin-user span{color:var(--muted)}

    .page-head{display:flex;align-items:center;justify-content:space-between;margin:8px 0 18px}
    .title{font-size:1.6rem;font-weight:800}
    .sub{color:var(--muted)}

    .btn{display:inline-flex;align-items:center;gap:8px;border-radius:12px;padding:10px 14px;font-weight:700;border:1px solid transparent;color:var(--text)}
    .btn-soft{background:rgba(255,255,255,.06);border:1px solid var(--border)}
    .btn-soft:hover{background:rgba(255,255,255,.1)}

    .card{background:linear-gradient(180deg,#0d1424,#0c1220);border:1px solid var(--border);border-radius:16px;box-shadow:0 22px 60px rgba(2,6,23,.55);overflow:hidden}
    .card-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--border)}
    .card-body{padding:16px}

    .empty-state{padding:36px;text-align:center;color:var(--muted)}
    .empty-state .title{color:var(--text);font-weight:800;margin-top:8px}
  </style>
</head>
<body>
  <?php $__page_title = 'Site Stats'; include __DIR__ . '/_topbar.php'; ?>

  <main class="wrap">
    <div class="page-head">
      <div>
        <div class="title">Analytics Overview</div>
        <div class="sub">Track user engagement and visits</div>
      </div>
      <div class="toolbar">
        <a href="<?= BASE_URL ?>admin/index.php" class="btn btn-soft"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back</a>
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;Logout</a>
      </div>
    </div>

    <section class="card">
      <div class="card-head">
        <div style="font-weight:700">Top Users by Site Visits</div>
      </div>
      <div class="card-body">
        <?php if (!empty($userVisits)): ?>
          <canvas id="visitChart" height="120"></canvas>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-chart-line"></i>
            <div class="title">No visit data yet</div>
            <div class="desc">Data will appear as users browse the site.</div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <?php if (!empty($userVisits)): ?>
  <script>
    const ctx = document.getElementById('visitChart').getContext('2d');
    const labels = <?= json_encode($labels) ?>;
    const data = <?= json_encode($visits) ?>;
    const lastVisits = <?= json_encode($lasts) ?>;

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Visit Count',
          data,
          backgroundColor: 'rgba(124, 58, 237, 0.55)',
          borderColor: 'rgba(124, 58, 237, 1)',
          borderWidth: 1,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { afterLabel: (ctx) => '\nLast visit: ' + (lastVisits[ctx.dataIndex] || 'Never') } },
          title: { display: false }
        },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { stepSize: 1, color: '#cdd5df' }, grid: { color: 'rgba(255,255,255,.06)' } }
        }
      }
    });
  </script>
  <?php endif; ?>
</body>
</html>
