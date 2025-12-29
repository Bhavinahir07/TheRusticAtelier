<?php

// session_start();
require_once __DIR__ . "/config/init.php";

// Message for user
$error = ($_GET['message'] ?? '') === 'account_deleted' ? 'Your account has been successfully deleted.' : '';

// session_start();
require_once __DIR__ . "/config/init.php";

// Securely verify Django PBKDF2_SHA256 password
function verifyDjangoPassword($password, $djangoHash)
{
    $parts = explode('$', $djangoHash);
    if (count($parts) !== 4 || $parts[0] !== 'pbkdf2_sha256') {
        return false;
    }

    list(, $iterations, $salt, $hash) = $parts;
    $calculated = base64_encode(
        hash_pbkdf2("sha256", $password, $salt, (int)$iterations, 32, true)
    );

    return hash_equals($hash, $calculated);
}

// ✅ Handle login submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ✅ DB connection
    // $mysqli = new mysqli("localhost", "root", "", "MyRecipe");
    // if ($mysqli->connect_error) {
    //     die("Database connection failed: " . $mysqli->connect_error);
    // }

    // ✅ Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    // ✅ Verify password
    // ✅ Verify password
    if ($user && verifyDjangoPassword($password, $user['password'])) {

        // ✅ NEW: Check for soft-delete status
        $accountStatus = $user['account_status'] ?? 'active';

        if ($accountStatus === 'deactivated_by_user') {
            $error = "This account was previously deleted at your request.";
        } elseif ($accountStatus === 'deactivated_by_admin') {
            $error = "This account has been removed by an administrator.";
        } elseif ($accountStatus === 'active') {

            // --- Login Success ---
            // ✅ Save user info + role in session
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role']
            ];

            // After successful login
            $userId = $user['id'];

            // ✅ Update visit count and last visit
            $update = $conn->prepare("UPDATE users 
                    SET visit_count = visit_count + 1, last_visit = NOW() 
                    WHERE id = ?");
            $update->execute([$userId]);

            // ✅ Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
            // --- End of Login Success ---

        } else {
            // Catch-all for any other inactive status
            $error = "This account is currently inactive.";
        }
    } else {
        $error = "❌ Invalid email or password.";
    }
}
?>


<!-- ✅ Login Form HTML -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- Bootstrap link for site icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Font styles for testing -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>css/login_signup.css">
</head>

<body>
    <div class="container">
        <div class="form-box" id="login-box">
            <!-- <h2>Login</h2> -->
            <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier</h1>


            <?php if (!empty($error)): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="email" name="email" placeholder="Email" required><br><br>
                <input type="password" name="password" placeholder="Password" required><br><br>
                <button type="submit">Login</button>
            </form>

            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>
</body>

</html>