<?php
// session_start();
// require_once 'db.php';
require_once __DIR__ . "/config/init.php";

// ✅ Access control: Only admin can see stats
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Access denied. Only admin can view this page.");
}
// Set username for header display
$username = $_SESSION['user']['username'] ?? 'Admin';

// ✅ Fetch total counts (only what we show)
try {
    $totalRecipes = $conn->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
} catch (PDOException $e) {
    die("Error fetching statistics: " . $e->getMessage());
}

// Fetch user visit stats (username, visit_count, last_visit)
try {
    $stmt = $conn->query("SELECT username, visit_count, last_visit 
                          FROM users 
                          ORDER BY visit_count DESC 
                          LIMIT 10"); // Top 10 users by visits
    $userVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user visits: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Statistics - TheRusticAtelier</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Font styles for testing -->
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

    <!-- Bootstrap link for site icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site_stats.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- Header -->
    <header>
        <!-- <h1>TheRusticAtelier</h1> -->
        <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier</h1>
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

    <div class="stats-container">
        <div class="stat-card">
            <a href="manage_recipes.php" class="stat-btn">
                <div class="stat-title">Total Recipes</div>
                <div class="stat-value"><?php echo $totalRecipes; ?></div>
            </a>
        </div>
    </div>

    <div class="chart-container" style="max-width: 900px; margin: 40px auto;">
        <canvas id="visitChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('visitChart').getContext('2d');
        const visitChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($userVisits, 'username')) ?>,
                datasets: [{
                    label: 'Visit Count',
                    data: <?= json_encode(array_column($userVisits, 'visit_count')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top Users by Site Visits'
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const lastVisits = <?= json_encode(array_column($userVisits, 'last_visit')) ?>;

                                return 'Last visit: ' + (lastVisits[context.dataIndex] ?? 'Never');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>

</html>