<?php
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
    require_once __DIR__ . "/config/init.php";
}

require 'db.php'; // DB connection file

// Prevent direct access
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_action'])) {
    $_SESSION['error'] = "Direct access not allowed.";
    header("Location: login.php");
    exit();
}

// If POST → handle OTP check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');
    $actual_otp = trim($_SESSION['otp'] ?? '');
    $action = $_SESSION['otp_action'] ?? '';

    // OTP comparison
    if ($entered_otp !== $actual_otp) {
        $_SESSION['error'] = "Invalid OTP.";
        header("Location: verify_otp.php");
        exit();
    }

    // === Handle Signup (✅ MODIFIED SECTION)
    if ($action === 'signup') {
        $temp_data = $_SESSION['temp_user_data'] ?? null;
        if (!$temp_data) {
            $_SESSION['error'] = "Session expired. Please signup again.";
            header("Location: signup.php");
            exit();
        }

        // Hash password using Django-compatible hasher
        require_once 'django_hasher.php';
        $plain_password = $temp_data['password'];
        $hashed_password = django_pbkdf2_sha256($plain_password);

        // --- ✅ Retrieve First and Last Name from Session ---
        $first_name = $temp_data['first_name'];
        $last_name  = $temp_data['last_name'];
        // --------------------------------------------------------
        
        $username = $temp_data['username'];
        $email= $temp_data['email'];

        $dateJoined = date("Y-m-d H:i:s"); // Get current timestamp

        // --- ✅ Update INSERT query to include first_name and last_name ---
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, username, email, password, is_active, date_joined)
            VALUES (:first_name, :last_name, :username, :email, :password, 1, :date_joined)
        ");

        // --- ✅ Bind new parameters ---
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        // ------------------------------------
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password); // already hashed securely
        $stmt->bindParam(':date_joined', $dateJoined);
        $stmt->execute();

        // -------------------------------------------------------
        // ✅ START NEW/MODIFIED AUTO-LOGIN LOGIC
        // -------------------------------------------------------
        $user_id = $conn->lastInsertId();
        
        // 1. Fetch the complete user record
        $ustmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $ustmt->execute([$user_id]);
        $user = $ustmt->fetch(PDO::FETCH_ASSOC);

        // 2. Set ALL required session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user'] = $user; // <-- THIS IS THE KEY FIX

        // -------------------------------------------------------
        // ✅ END NEW/MODIFIED AUTO-LOGIN LOGIC
        // -------------------------------------------------------


        // Redirect to index page for auto-login completion (REQUESTED CHANGE)
        unset($_SESSION['otp'], $_SESSION['otp_action'], $_SESSION['temp_user_data']);
        header("Location: index.php"); // <--- MODIFIED
        exit();
    }
    // === Handle Login (No changes needed)
    elseif ($action === 'login') {
        $email = $_SESSION['email'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "No account with that email.";
            header("Location: login.php");
            exit();
        }

        // ✅ OTP verified and session started
        $_SESSION['user_email'] = $email;
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];

        // Clear OTP-related session variables
        unset($_SESSION['otp'], $_SESSION['otp_action'], $_SESSION['email'], $_SESSION['temp_user_data']);

        // ✅ Redirect based on role
        session_regenerate_id(true);
        if (isset($user['role']) && $user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: loginDone.php");
        }
        exit();
    }

    // === Handle Password Reset (No changes needed)
    elseif ($action === 'reset') {
        $email = $_SESSION['email'] ?? '';

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "User does not exist.";
            header("Location: login.php");
            exit();
        }

        $_SESSION['otp_verified'] = true;
        $_SESSION['user_id'] = $user['id'];

        // Clear session and go to reset page
        unset($_SESSION['otp'], $_SESSION['otp_action'], $_SESSION['email'], $_SESSION['temp_user_data']);
        header("Location: reset_password.php");
        exit();
    }

    // Final fallback redirect
    session_regenerate_id(true);
    unset($_SESSION['otp'], $_SESSION['otp_action'], $_SESSION['email'], $_SESSION['temp_user_data']);
    header("Location: " . $action . "Done.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify OTP</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/verify_otp.css">

</head>

<body>

    <div class="otp-container">
        <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier</h1>

        <h2>
            <?php
            if ($_SESSION['otp_action'] === 'signup') echo "Confirm Your Email";
            elseif ($_SESSION['otp_action'] === 'login') echo "Verify Login";
            elseif ($_SESSION['otp_action'] === 'reset') echo "Reset Password";
            else echo "Verify OTP";
            ?>
        </h2>

        <?php if ($error): ?>
            <div class="message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="otpForm" action="verify_otp.php">
            <div class="otp-inputs">
                <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" required />
                <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" required />
                <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" required />
                <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" required />
                <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" required />
                <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" required />
            </div>
            <input type="hidden" name="otp" id="otp_hidden">
            <button type="submit">Verify OTP</button>
        </form>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-inputs input');
        const hiddenInput = document.getElementById('otp_hidden');
        const form = document.getElementById('otpForm');

        inputs[0].focus();

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const otp = Array.from(inputs).map(input => input.value.trim()).join('');
            hiddenInput.value = otp;

            if (otp.length === 6) {
                setTimeout(() => form.submit(), 50);
            } else {
                alert("Please enter all 6 digits of the OTP.");
            }
        });
    </script>

</body>

</html>