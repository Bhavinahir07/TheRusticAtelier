<?php
// session_start();
// require_once 'db.php';
require_once __DIR__ . "/config/init.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Prevent deleting yourself
    if ($_SESSION['user']['id'] == $id) {
        die("You cannot delete your own account.");
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: view_users.php");
    exit();
} else {
    die("Invalid request.");
}
