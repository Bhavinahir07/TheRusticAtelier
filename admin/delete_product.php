<?php
require_once __DIR__ . '/../config/initAdmin.php';

// Guard: admin only
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  $_SESSION['redirect_after_login'] = 'admin/products.php';
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: ' . BASE_URL . 'admin/products.php');
  exit;
}

try {
  $conn->beginTransaction();

  // 1) Discover current database name
  $dbStmt = $conn->query('SELECT DATABASE() AS db');
  $dbName = (string)($dbStmt->fetch(PDO::FETCH_ASSOC)['db'] ?? '');

  // 2) Manual deletes for known FK tables (fast path)
  $candidates = [
    ['table' => 'cart_items', 'col' => 'product_id'],
    ['table' => 'order_items', 'col' => 'product_id'],
    ['table' => 'favorites', 'col' => 'product_id'],
    ['table' => 'product_reviews', 'col' => 'product_id'],
  ];
  foreach ($candidates as $c) {
    try {
      // Check if table exists
      if ($dbName !== '') {
        $existsQ = $conn->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t');
        $existsQ->execute([':db' => $dbName, ':t' => $c['table']]);
        if ((int)$existsQ->fetchColumn() === 0) { continue; }
      }
      $stmt = $conn->prepare("DELETE FROM `{$c['table']}` WHERE `{$c['col']}` = :id");
      $stmt->execute([':id' => $id]);
    } catch (Throwable $e) { /* ignore */ }
  }

  // 3) Dynamic FK-based cleanup: find all tables referencing products(id)
  if ($dbName !== '') {
    $fkSql = "
      SELECT TABLE_NAME, COLUMN_NAME
      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = :db
        AND REFERENCED_TABLE_NAME = 'products'
        AND REFERENCED_COLUMN_NAME = 'id'
    ";
    $fk = $conn->prepare($fkSql);
    $fk->execute([':db' => $dbName]);
    $refs = $fk->fetchAll(PDO::FETCH_ASSOC);
    foreach ($refs as $ref) {
      $t = $ref['TABLE_NAME'];
      $c = $ref['COLUMN_NAME'];
      // Skip known handled tables
      if (in_array($t, ['cart_items', 'order_items', 'favorites', 'product_reviews'], true)) continue;
      try {
        $sql = sprintf('DELETE FROM `%s` WHERE `%s` = :id', str_replace('`','',$t), str_replace('`','',$c));
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
      } catch (Throwable $e) {
        // Log but continue
        error_log('delete_product: could not purge '+$t+' via FK: '+$e->getMessage());
      }
    }
  }

  // 4) Now delete the product
  $del = $conn->prepare('DELETE FROM products WHERE id = :id');
  $del->execute([':id' => $id]);

  $conn->commit();
  $_SESSION['products_feedback'] = ['type' => 'success', 'text' => 'Product deleted successfully.'];
} catch (Throwable $e) {
  if ($conn->inTransaction()) { try { $conn->rollBack(); } catch (Throwable $__) {} }
  error_log('delete_product failed: ' . $e->getMessage());
  $_SESSION['products_feedback'] = ['type' => 'error', 'text' => 'Failed to delete product. It may have related data.'];
}

header('Location: ' . BASE_URL . 'admin/products.php');
exit;
