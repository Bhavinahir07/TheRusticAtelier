<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/recipes.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: ' . BASE_URL . 'admin/recipes.php');
  exit;
}

// Optionally: delete related data (e.g., images) here
$del = $conn->prepare('DELETE FROM recipes WHERE id = :id');
$del->execute([':id' => $id]);

header('Location: ' . BASE_URL . 'admin/recipes.php');
exit;
