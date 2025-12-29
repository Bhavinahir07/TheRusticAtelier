<?php
// session_start();
// require_once 'db.php'; // Assumes this connects to your DB
require_once __DIR__ . "/config/init.php";

// ✅ Access control
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Access denied. Only admin can view this page.");
}

$username = htmlspecialchars($_SESSION['user']['username']);

// ✅ Fetch users
$stmt = $conn->prepare("SELECT id, username, email, role, date_joined FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users | Admin - MyRecipe</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/view_users.css">
</head>

<body>

    <!-- Header -->
    <header>
        <h1>Manage Recipes</h1>
        <div class="auth-buttons">
            <span class="admin-user-label">Logged in as: <?= $username ?></span>
            <a href="logout.php">
                <button class="btn">Logout</button>
            </a>
        </div>
    </header>

    <div class="buttons">
        <a class="back-link" href="admin.php">&larr; Back to admin panel</a>
    </div>

    <section class="table-section">
        <h2>Registered Users</h2>

        <table class="admin-table">
            <!-- <table> -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['date_joined']) ?></td>
                            <td>
                                <form method="POST" action="delete_user.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</body>

</html>