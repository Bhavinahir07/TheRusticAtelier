<?php
// session_start();
// require 'db.php'; // Must return PDO object in $conn
require_once __DIR__ . "/config/init.php";

header('Content-Type: application/json');

// Check login
$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['message' => 'User not logged in']);
    exit;
}

// Get input
$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['productId'] ?? null;
$quantity = $data['quantity'] ?? 1;

// Debug log
file_put_contents("debug_log.txt", "--- New Request ---\n✅ User ID: $user_id\n📝 Data: " . print_r($data, true), FILE_APPEND);

try {
    // 1. Get or create cart
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $cart_id = $cart['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $cart_id = $conn->lastInsertId();
    }

    // 2. Check for existing cartitem
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cart_id, $product_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $new_qty = $item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_qty, $item['id']]);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$cart_id, $product_id, $quantity]);
    }

    echo json_encode(['message' => 'Added to cart']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
