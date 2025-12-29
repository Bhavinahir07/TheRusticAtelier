<?php
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
    require_once __DIR__ . "/config/init.php";
}

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}
?>
