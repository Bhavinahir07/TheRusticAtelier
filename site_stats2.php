<?php
// session_start();
// require_once 'db.php';
require_once __DIR__ . "/config/init.php";

// ✅ Today's total user visits (users whose last_visit is today)
$todayVisits = $conn->query("
    SELECT COUNT(*) 
    FROM auth_user 
    WHERE DATE(last_visit) = CURDATE()
")->fetchColumn();

// ✅ Most active user today
$mostActive = $conn->query("
    SELECT username, visit_count 
    FROM auth_user 
    WHERE DATE(last_visit) = CURDATE()
    ORDER BY visit_count DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Stats</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 50px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 4px 6px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }

        .stat-btn {
            border: none;
            background: #fff;
            color: inherit;
        }

        .stat-card:hover {
            background: #f7f7f7ff;
            transform: scale(1.05);
        }

        .stat-title {
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #000;
        }

        .btn-dashboard {
            margin-left: 370px;
        }

        @media (max-width: 1024px) {
            .stats-container {
                padding: 0 20px 0 20px;
            }

            .stat-card {
                padding: 18px;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 10px;
            }

            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                padding: 0 20px 0 20px;
            }

            .stat-title {
                font-size: 1.1em;
            }

            .stat-value {
                font-size: 1.8em;
            }

            .btn-dashboard {
                max-width: 200px;
                margin-left: 50px;
            }
        }

        @media (max-width: 480px) {
            header {
                font-size: 1.2em;
                padding: 12px;
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-title {
                font-size: 1em;
            }

            .stat-value {
                font-size: 1.6em;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                display: block;
                margin: 8px auto;
            }
        }
    </style>
</head>

<body>

    <h2 style="text-align:center;">📊 Site Statistics (Today)</h2>
    <div style="width:80%;margin:auto;">
        <canvas id="statsChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('statsChart').getContext('2d');
        let statsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Users Today', '<?php echo $mostActive['username'] ?? "No Active User"; ?> (Most Active User)'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo (int)$todayVisits; ?>, <?php echo (int)($mostActive['visit_count'] ?? 0); ?>],
                    backgroundColor: ['#4CAF50', '#FF9800']
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 🔄 Refresh every 5 seconds
        setInterval(() => {
            fetch('stats_api.php')
                .then(res => res.json())
                .then(data => {
                    statsChart.data.labels[1] = data.mostActiveUser;
                    statsChart.data.datasets[0].data = [data.todayVisits, data.mostActiveCount];
                    statsChart.update();
                });
        }, 5000);
    </script>

</body>

</html>