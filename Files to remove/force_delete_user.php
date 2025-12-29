<?php
require_once __DIR__ . '/../config/initAdmin.php';

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

// Prevent deleting self
if ((int)($_SESSION['user']['id'] ?? 0) === (int)$id) {
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'You cannot delete your own account while logged in.'];
  header('Location: ' . BASE_URL . 'admin/users.php');
  exit;
}

try {
  $conn->beginTransaction();

  // 1) Discover current database name
  $dbStmt = $conn->query('SELECT DATABASE() AS db');
  $dbName = (string)($dbStmt->fetch(PDO::FETCH_ASSOC)['db'] ?? '');

  // 2) Best-effort deletes for actual schema
  $lastErr = '';
  // Helper to check table exists
  $tableExists = function(string $t) use ($conn, $dbName): bool {
    if ($dbName === '') return true;
    $q = $conn->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t');
    $q->execute([':db' => $dbName, ':t' => $t]);
    return ((int)$q->fetchColumn()) > 0;
  };

  // addresses.user_id
  if ($tableExists('addresses')) {
    try { $stmt = $conn->prepare('DELETE FROM `addresses` WHERE `user_id` = :id'); $stmt->execute([':id'=>$id]); } catch (Throwable $e) { $lastErr = $e->getMessage(); }
  }
  // addresses2.user_id
  if ($tableExists('addresses2')) {
    try { $stmt = $conn->prepare('DELETE FROM `addresses2` WHERE `user_id` = :id'); $stmt->execute([':id'=>$id]); } catch (Throwable $e) { $lastErr = $e->getMessage(); }
  }
  // carts.user_id then cart_items by cart_id
  if ($tableExists('carts')) {
    try {
      if ($tableExists('cart_items')) {
        $stmt = $conn->prepare('DELETE FROM `cart_items` WHERE `cart_id` IN (SELECT id FROM carts WHERE user_id = :id)');
        $stmt->execute([':id'=>$id]);
      }
      $stmt = $conn->prepare('DELETE FROM `carts` WHERE `user_id` = :id');
      $stmt->execute([':id'=>$id]);
    } catch (Throwable $e) { $lastErr = $e->getMessage(); }
  }
  // orders.user_id then order_items by order_id
  if ($tableExists('orders')) {
    try {
      if ($tableExists('order_items')) {
        $stmt = $conn->prepare('DELETE FROM `order_items` WHERE `order_id` IN (SELECT id FROM orders WHERE user_id = :id)');
        $stmt->execute([':id'=>$id]);
      }
      $stmt = $conn->prepare('DELETE FROM `orders` WHERE `user_id` = :id');
      $stmt->execute([':id'=>$id]);
    } catch (Throwable $e) { $lastErr = $e->getMessage(); }
  }
  // shared_recipes.user_id (present in app)
  if ($tableExists('shared_recipes')) {
    try { $stmt = $conn->prepare('DELETE FROM `shared_recipes` WHERE `user_id` = :id'); $stmt->execute([':id'=>$id]); } catch (Throwable $e) { $lastErr = $e->getMessage(); }
  }

  // 3) Dynamic FK-based cleanup: find all tables referencing users(id)
  if ($dbName !== '') {
    $fkSql = "
      SELECT TABLE_NAME, COLUMN_NAME
      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = :db
        AND REFERENCED_TABLE_NAME = 'users'
        AND REFERENCED_COLUMN_NAME = 'id'
    ";
    $fk = $conn->prepare($fkSql);
    $fk->execute([':db' => $dbName]);
    $refs = $fk->fetchAll(PDO::FETCH_ASSOC);
    foreach ($refs as $ref) {
      $t = $ref['TABLE_NAME'];
      $c = $ref['COLUMN_NAME'];
      // Skip known handled tables
      if (in_array($t, ['addresses','addresses2','carts','cart_items','orders','order_items','shared_recipes'], true)) continue;
      try {
        $sql = sprintf('DELETE FROM `%s` WHERE `%s` = :id', str_replace('`','',$t), str_replace('`','',$c));
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
      } catch (Throwable $e) {
        // Log but continue to attempt others
        $code = (string)($e instanceof PDOException ? $e->getCode() : '');
        if ($code !== '42S02') { $lastErr = $e->getMessage(); }
        error_log('force_delete_user: could not purge '.$t.' via FK: '.$e->getMessage());
      }
    }
  }

  // 4) Finally delete the user
  $stmt = $conn->prepare('DELETE FROM users WHERE id = :id');
  $stmt->execute([':id' => $id]);

  $conn->commit();
  $_SESSION['users_feedback'] = ['type' => 'success', 'text' => 'User and related data deleted.'];
} catch (Throwable $e) {
  if ($conn->inTransaction()) { try { $conn->rollBack(); } catch (Throwable $__) {} }
  error_log('force_delete_user failed: ' . $e->getMessage());
  $hint = '';
  if (!empty($lastErr)) {
    // Trim verbose SQL for a short hint
    $hint = ' Details: ' . substr($lastErr, 0, 160);
  }
  $_SESSION['users_feedback'] = ['type' => 'error', 'text' => 'Force delete failed due to a server error.' . $hint];
}

header('Location: ' . BASE_URL . 'admin/users.php');
exit;
