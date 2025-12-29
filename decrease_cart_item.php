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
    SELECT ci.id, ci.quantity FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.id
    WHERE ci.id = ? AND c.user_id = ?
");
$stmt->execute([$item_id, $user_id]);
$item = $stmt->fetch();

if ($item && $item['quantity'] > 1) {
    $update = $conn->prepare("UPDATE cart_items SET quantity = quantity - 1 WHERE id = ?");
    $update->execute([$item_id]);
}

header("Location: cart.php");
exit;
