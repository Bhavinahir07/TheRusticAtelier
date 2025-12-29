<?php
// session_start();
// require_once 'db.php'; // adjust path if needed
require_once __DIR__ . "/config/init.php";

// ✅ Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Access denied.");
}

// ✅ Fetch user registrations per day
$query = "SELECT DATE(date_joined) AS reg_date, COUNT(*) AS count 
          FROM users GROUP BY DATE(date_joined) ORDER BY reg_date ASC";
$result = $conn->query($query);

$dates = [];
$counts = [];

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $dates[] = $row['reg_date'];
    $counts[] = $row['count'];
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Signup Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f9f9f9;
        }

        .chart-container {
            width: 90%;
            max-width: 800px;
            margin: auto;
        }
    </style>
</head>

<body>
    <h2>User Registrations Per Day 📈</h2>
    <div class="chart-container">
        <canvas id="signupChart"></canvas>
    </div>

    <script>
        const labels = <?= json_encode($dates); ?>;
        const data = <?= json_encode($counts); ?>;

        new Chart(document.getElementById('signupChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'User Signups',
                    data: data,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Users Registered'
                        },
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>

</html>