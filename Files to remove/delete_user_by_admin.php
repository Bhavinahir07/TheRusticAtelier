<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Ensure admin
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . BASE_URL . 'admin/users.php');
  exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Invalid user id.'];
  header('Location: ' . BASE_URL . 'admin/users.php');
  exit;
}

// Optional: prevent deleting yourself
if ((int)($_SESSION['user']['id'] ?? 0) === (int)$id) {
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'You cannot delete your own account while logged in.'];
  header('Location: ' . BASE_URL . 'admin/users.php');
  exit;
}

try {
  $stmt = $conn->prepare('DELETE FROM users WHERE id = :id');
  $stmt->execute([':id' => $id]);
  if ($stmt->rowCount() > 0) {
    $_SESSION['users_feedback'] = ['type' => 'success', 'text' => 'User deleted successfully.'];
  } else {
    $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'No user deleted. The user may not exist.'];
  }
} catch (PDOException $e) {
  // Detect FK constraint (SQLSTATE 23000, or MySQL error 1451 in message)
  $sqlState = (string)$e->getCode();
  $msg = $e->getMessage();
  error_log('delete_user failed: '.$msg);
  if ($sqlState === '23000' || stripos($msg, '1451') !== false) {
    $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Cannot delete user because related records exist (e.g., orders, recipes). Remove related data first.'];
  } else {
    $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Failed to delete user due to a server error.'];
  }
} catch (Throwable $e) {
  error_log('delete_user failed: '.$e->getMessage());
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Failed to delete user due to a server error.'];
}

header('Location: ' . BASE_URL . 'admin/users.php');
exit;
