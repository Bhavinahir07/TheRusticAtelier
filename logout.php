<?php
// session_start();
require_once __DIR__ . "/config/init.php";

$_SESSION = [];
session_destroy();

echo "<p>Logging out...</p>";
header("Refresh: 2; URL=login.php"); // Wait 2 seconds before redirecting
exit();
?>
