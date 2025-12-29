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

try {
    $conn->beginTransaction();
    
    // 1. Reactivate the user
    $conn->prepare("UPDATE users SET account_status = 'active' WHERE id = ?")->execute([$id]);
    
    // 2. Set their recipes to 'unpublished' so admin can re-review
    $conn->prepare(
        "UPDATE shared_recipes SET status = 'unpublished' 
         WHERE user_id = ? AND status = 'deactivated_by_admin'"
    )->execute([$id]);
    
    // 3. Set their orders back to 'Pending' for re-processing
    $conn->prepare(
        "UPDATE orders SET status = 'Pending' 
         WHERE user_id = ? AND status = 'deactivated_by_admin'"
    )->execute([$id]);
    
    $conn->commit();
    // ✅ FIX: Removed the typo ('type's') on this line
    $_SESSION['users_feedback'] = ['type' => 'success', 'text' => 'User reactivated successfully.'];

} catch (Throwable $e) {
    if ($conn->inTransaction()) { try { $conn->rollBack(); } catch (Throwable $__) {} }
    // Log the error for debugging
    error_log('User reactivation failed: ' . $e->getMessage());
    $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Failed to reactivate user. A database error occurred.'];
}

header('Location: '.BASE_URL.'admin/users.php');
exit;