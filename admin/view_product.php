<?php
require_once __DIR__ . '/../config/initAdmin.php';
// Admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/products.php';
}
header('Location: ' . BASE_URL . 'admin/products.php');
exit;
