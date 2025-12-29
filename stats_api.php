<?php
// require_once 'db.php';
require_once __DIR__ . "/config/init.php";

// ✅ Today's visits
$todayVisits = $conn->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE DATE(last_visit) = CURDATE()
")->fetchColumn();

// ✅ Most active user
$mostActive = $conn->query("
    SELECT username, visit_count 
    FROM users 
    WHERE DATE(last_visit) = CURDATE()
    ORDER BY visit_count DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'todayVisits' => (int)$todayVisits,
    'mostActiveUser' => ($mostActive['username'] ?? 'No Active User') . ' (Most Active User)',
    'mostActiveCount' => (int)($mostActive['visit_count'] ?? 0)
]);
