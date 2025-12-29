<?php
// session_start();
// require 'db.php';
require_once __DIR__ . "/config/init.php";

if (!isset($_SESSION['user']) || !isset($_POST['item_id'])) {
    header("Location: cart.php");
    exit;
}

$item_id = $_POST['item_id'];

$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("
    SELECT ci.id FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.id
    WHERE ci.id = ? AND c.user_id = ?
");
$stmt->execute([$item_id, $user_id]);

if ($stmt->fetch()) {
    $delete = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
    $delete->execute([$item_id]);
}

header("Location: cart.php");
exit;
