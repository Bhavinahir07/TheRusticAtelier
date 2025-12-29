<?php
$host = "localhost";       // or 127.0.0.1
$dbname = "MyRecipe";   // your database name
// $dbname = "myrecipe_for_testing";   // your database name
$db_username = "root";        // your DB username (default for XAMPP)
$db_password = "";            // your DB password (empty in most local setups)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: set charset to utf8
    $conn->exec("SET NAMES utf8");
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>


