<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Ensure admin
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  header('Location: '.BASE_URL.'login.php'); exit;
}
// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: '.BASE_URL.'admin/users.php'); exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Invalid user id.'];
  header('Location: ' . BASE_URL . 'admin/users.php'); exit;
}

// Prevent admin from deactivating their own account
if ((int)($_SESSION['user']['id'] ?? 0) === (int)$id) {
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'You cannot deactivate your own account.'];
  header('Location: ' . BASE_URL . 'admin/users.php'); exit;
}

try {
    $conn->beginTransaction();
    
    // 1. Deactivate the user
    $conn->prepare("UPDATE users SET account_status = 'deactivated_by_admin' WHERE id = ?")->execute([$id]);
    
    // 2. Deactivate their shared recipes
    $conn->prepare(
        "UPDATE shared_recipes SET status = 'deactivated_by_admin' 
         WHERE user_id = ? AND status IN ('pending', 'approved', 'unpublished')"
    )->execute([$id]);
    
    // 3. Deactivate their active orders
    $conn->prepare(
        "UPDATE orders SET status = 'deactivated_by_admin' 
         WHERE user_id = ? AND status IN ('Pending', 'Processed', 'Shipped')"
    )->execute([$id]);
    
    $conn->commit();
    $_SESSION['users_feedback'] = ['type' => 'success', 'text' => 'User deactivated successfully.'];

} catch (Throwable $e) {
    if ($conn->inTransaction()) { try { $conn->rollBack(); } catch (Throwable $__) {} }
    // Log the error for debugging
    error_log('User deactivation failed: ' . $e->getMessage());
    $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Failed to deactivate user. A database error occurred.'];
}

header('Location: ' . BASE_URL . 'admin/users.php');
exit;