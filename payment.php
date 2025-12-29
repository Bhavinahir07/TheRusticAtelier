<?php
// product.php
require_once __DIR__ . "/config/init.php";
require_once __DIR__ . "/mailer.php"; // <--- ADD THIS LINE


// --- User Authentication Check ---
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$user_email = $_SESSION['user']['email'];

// --- Fetch User's Shipping Address ---
// Try to find the default address first
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
$stmt->execute([$user_id]);
$address = $stmt->fetch(PDO::FETCH_ASSOC);

// If no default, get the first address available
if (!$address) {
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$user_id]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If still no address, redirect user to add one
if (!$address) {
    // Set a session message to inform the user
    $_SESSION['message'] = ['type' => 'info', 'text' => 'Please add a shipping address before proceeding to payment.'];
    header("Location: add_address.php");
    exit;
}

// --- Handle Order Confirmation (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch cart items to process the order
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, p.id as product_id, p.name, p.price, ci.quantity 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        JOIN carts c ON ci.cart_id = c.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        echo "Your cart is empty. Cannot proceed with the order.";
        exit;
    }

    // --- Calculate Total Price ---
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // --- Determine Payment Method ---
    $payment_method = 'Cash on Delivery'; // Default value
    if (isset($_POST['payment_method']) && !empty($_POST['payment_method'])) {
        // Sanitize the input for security
        $allowed_methods = ['cod', 'card', 'upi', 'netbanking'];
        $selected_method = htmlspecialchars($_POST['payment_method']);
        if (in_array($selected_method, $allowed_methods)) {
            // Convert short code to readable format
            switch ($selected_method) {
                case 'card':
                    $payment_method = 'Credit/Debit Card';
                    break;
                case 'upi':
                    $payment_method = 'UPI';
                    break;
                case 'netbanking':
                    $payment_method = 'Net Banking';
                    break;
                default:
                    $payment_method = 'Cash on Delivery';
            }
        }
    }


    // --- Database Operations within a Transaction ---
    try {
        $conn->beginTransaction();

        // 1. Insert the main order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, payment_method) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $total, $payment_method]);
        $order_id = $conn->lastInsertId();

        // 2. Insert individual order items
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity']]);
        }

        // 3. Clear the user's cart
        $stmt_clear_cart = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt_clear_cart->execute([$items[0]['cart_id']]);

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        // Log the error and show a user-friendly message
        error_log("Order processing failed: " . $e->getMessage());
        echo "Something went wrong. Please try again later.";
        exit;
    }


    // --- Prepare and Send Email Notification ---
$product_lines = "";
foreach ($items as $item) {
    $product_lines .= "- " . $item['name'] . " (x" . $item['quantity'] . ") @ ₹" . number_format($item['price'], 2) . "\n";
}

// Corrected: Use 'receiver_name' from the address table
$address_text = "Receiver: " . ($address['receiver_name'] ?? 'N/A') . "\n"
    . "Address: " . ($address['building_name'] ?? 'N/A') . "\n"
    . "Landmark: " . ($address['landmark'] ?? '-') . "\n"
    . "Phone: " . ($address['phone'] ?? '-') . "\n"
    . "Alt Phone: " . ($address['alternate_phone'] ?? '-') . "\n";

// --- 1. Send Email to ADMIN ---
$admin_email = "bhavinmeta009@gmail.com";
$admin_subject = "New Order (#$order_id) from $username";

// This is the full message for you, the owner
$admin_message = "New Order (#$order_id) from: $username ($user_email)\n\n";
$admin_message .= "SHIPPING ADDRESS:\n$address_text\n";
$admin_message .= "ORDER ITEMS:\n$product_lines\n";
$admin_message .= "Total Amount: ₹" . number_format($total, 2) . "\n";
$admin_message .= "Payment Method: $payment_method";

sendCustomMail($admin_email, $admin_subject, $admin_message);


// --- 2. Send Email to CUSTOMER ---
$customer_email = $user_email; // From top of the file
$customer_subject = "Your Order is Confirmed! (ID: #$order_id)";

// This is a simpler confirmation for the user
$customer_message = "Hi $username,\n\n";
$customer_message .= "Thank you for your order! We have received it and will process it soon.\n\n";
$customer_message .= "ORDER SUMMARY:\n$product_lines\n";
$customer_message .= "Total: ₹" . number_format($total, 2) . "\n\n";
$customer_message .= "SHIPPING TO:\n$address_text";

sendCustomMail($customer_email, $customer_subject, $customer_message);


// --- Redirect to Success Page ---
header("Location: order_success.php?order_id=" . $order_id);
exit;
}

// --- Fetch Cart Data for Display (GET Request) ---
$stmt = $conn->prepare("
    SELECT p.name, p.price, p.image, ci.quantity 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    JOIN carts c ON ci.cart_id = c.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If cart is empty, redirect them back to the cart page
if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }

        /* Force native form controls to use orange accent */
        input[type="radio"],
        input[type="checkbox"] {
            accent-color: #f59e0b; /* orange-500 */
        }

        .payment-option {
            transition: all 0.2s ease-in-out;
        }

        .payment-option.selected {
            border-color: #f59e0b; /* orange-500 */
            background-color: #fff7ed; /* orange-50 */
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
        }

        #confirmBtn {
            transition: background-color 0.3s;
        }

        #confirmBtn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .validation-message {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        footer {
            text-align: center;
            padding: 30px 20px;
            background: #222;
            color: white;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        footer a {
            color: #ff6347;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: #fff;
            text-decoration: underline;
        }

        .footer-links a {
            margin-right: 20px;
        }

        .footer-social {
            margin-top: 20px;
        }

        .footer-social .social-icon {
            font-size: 1.5rem;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .footer-social .social-icon:hover {
            color: #fff;
        }

        .footer-social .facebook {
            color: #3b5998;
        }

        .footer-social .twitter {
            color: #00acee;
        }

        .footer-social .instagram {
            color: #e4405f;
        }
    </style>
</head>

<body>
    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Checkout</h1>
            <a href="index.php" class="inline-flex items-center bg-gray-900 hover:bg-black text-white px-4 py-2 rounded-md font-semibold">
                <i class="fas fa-home mr-2"></i> Back to Home
            </a>
        </div>

        <form method="post" id="paymentForm" onsubmit="return disableButton(this)">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4">Choose Payment Method</h2>

                    <div class="space-y-4">
                        <label for="cod"
                            class="payment-option selected block p-4 border-2 border-orange-500 rounded-lg cursor-pointer"
                            onclick="selectPayment('cod')">
                            <div class="flex items-center">
                                <input type="radio" id="cod" name="payment_method" value="cod"
                                    class="h-5 w-5 text-orange-600" checked>
                                <div class="ml-4">
                                    <div class="flex items-center text-lg font-semibold">
                                        <i class="fas fa-hand-holding-usd mr-3 text-gray-600"></i>
                                        Cash on Delivery
                                    </div>
                                    <p class="text-gray-500 text-sm mt-1">Pay with cash upon delivery of your order.</p>
                                </div>
                            </div>
                        </label>
                        <div id="cod-content" class="form-content active p-4">
                            <p class="text-gray-600 text-center"><i class="fas fa-info-circle mr-2"></i>You can pay at
                                your doorstep.</p>
                        </div>

                        <label for="card"
                            class="payment-option block p-4 border border-gray-200 rounded-lg cursor-pointer"
                            onclick="selectPayment('card')">
                            <div class="flex items-center">
                                <input type="radio" id="card" name="payment_method" value="card"
                                    class="h-5 w-5 text-orange-600">
                                <div class="ml-4">
                                    <div class="flex items-center text-lg font-semibold">
                                        <i class="fas fa-credit-card mr-3 text-gray-600"></i>
                                        Credit/Debit Card
                                    </div>
                                    <p class="text-gray-500 text-sm mt-1">Secure payment with Visa, MasterCard, etc.</p>
                                </div>
                            </div>
                        </label>
                        <div id="card-content" class="form-content border-t pt-4 mt-2">
                            <div class="space-y-4">
                                <div>
                                    <label for="card_number" class="block text-sm font-medium text-gray-700">Card
                                        Number</label>
                                    <div class="relative mt-1">
                                        <input type="text" id="card_number" placeholder="0000 0000 0000 0000"
                                            class="w-full p-2 pr-24 border rounded-md" maxlength="19"
                                            inputmode="numeric">
                                        <div
                                            class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center space-x-1">
                                            <i class="fab fa-cc-visa text-gray-400 text-xl"></i>
                                            <i class="fab fa-cc-mastercard text-gray-400 text-xl"></i>
                                            <i class="fab fa-cc-amex text-gray-400 text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry
                                            Date</label>
                                        <input type="text" id="expiry_date" placeholder="MM/YY"
                                            class="w-full mt-1 p-2 border rounded-md" maxlength="5" inputmode="numeric">
                                    </div>
                                    <div>
                                        <label for="cvv" class="block text-sm font-medium text-gray-700">CVV</label>
                                        <input type="text" id="cvv" placeholder="123"
                                            class="w-full mt-1 p-2 border rounded-md" maxlength="3" inputmode="numeric">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <label for="upi"
                            class="payment-option block p-4 border border-gray-200 rounded-lg cursor-pointer"
                            onclick="selectPayment('upi')">
                            <div class="flex items-center">
                                <input type="radio" id="upi" name="payment_method" value="upi"
                                    class="h-5 w-5 text-orange-600">
                                <div class="ml-4">
                                    <div class="flex items-center text-lg font-semibold">
                                        <i class="fas fa-mobile-alt mr-3 text-gray-600"></i>
                                        UPI
                                    </div>
                                    <p class="text-gray-500 text-sm mt-1">Pay with any UPI app like GPay, PhonePe.</p>
                                </div>
                            </div>
                        </label>
                        <div id="upi-content" class="form-content border-t pt-4 mt-2">
                            <div>
                                <label for="upi_id" class="block text-sm font-medium text-gray-700">Enter UPI ID</label>
                                <div class="flex mt-1">
                                    <input type="text" id="upi_id" placeholder="yourname@bank"
                                        class="flex-grow p-2 border rounded-l-md">
                                    <button type="button" id="verifyUpiBtn"
                                        class="bg-black text-white hover:bg-black/90 px-4 rounded-r-md font-semibold text-sm">Verify</button>
                                </div>
                                <div id="upiValidationMessage" class="validation-message"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md self-start">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-3">Order Summary</h2>

                    <div class="space-y-3 mb-4">
                        <?php foreach ($items as $item): ?>
                            <div class="flex justify-between items-center text-gray-700">
                                <div class="flex items-center">
                                    <img src="<?= htmlspecialchars($item['image'] ?? 'https://placehold.co/64x64/e2e8f0/adb5bd?text=Image') ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                        class="w-12 h-12 object-cover rounded-md mr-4">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($item['name']) ?></p>
                                        <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></p>
                                    </div>
                                </div>
                                <p>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <p>Subtotal</p>
                            <p>₹<?= number_format($total, 2) ?></p>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <p>Shipping Fee</p>
                            <p class="text-green-500 font-medium">Free</p>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-gray-800 border-t pt-3 mt-3">
                            <p>Total</p>
                            <p>₹<?= number_format($total, 2) ?></p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="font-semibold mb-2">Shipping to:</h3>
                        <div class="text-gray-600 bg-gray-50 p-3 rounded-md border">
                            <p class="font-bold"><?= htmlspecialchars($address['receiver_name']) ?></p>
                            <p><?= htmlspecialchars($address['building_name']) ?></p>
                            <p><?= htmlspecialchars($address['landmark']) ?></p>
                            <p>Phone: <?= htmlspecialchars($address['phone']) ?></p>
                        </div>
                        <a href="show_address.php?redirect_to=payment.php"
                            class="text-sm font-medium text-orange-600 hover:text-orange-700">
                            Change address
                        </a>
                    </div>

                    <div class="mt-6">
                        <button type="submit" id="confirmBtn"
                            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold py-3 px-4 rounded-lg hover:from-orange-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            Confirm and Pay ₹<?= number_format($total, 2) ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // --- Get all the important elements ---
        const confirmBtn = document.getElementById('confirmBtn');
        const paymentForm = document.getElementById('paymentForm');

        const cardNumberInput = document.getElementById('card_number');
        const expiryDateInput = document.getElementById('expiry_date');
        const cvvInput = document.getElementById('cvv');
        
        const upiInput = document.getElementById('upi_id');
        const verifyUpiBtn = document.getElementById('verifyUpiBtn');
        const upiMessage = document.getElementById('upiValidationMessage');
        
        // This flag tracks if UPI is verified
        let isUpiVerified = false;

        /**
         * This is the new central validation function.
         * It checks the state of the form and enables/disables the pay button.
         */
        function validatePaymentState() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;

            if (selectedMethod === 'cod') {
                // Cash on Delivery: Always valid
                confirmBtn.disabled = false;
                
                // Disable 'required' on other inputs so the form can submit
                cardNumberInput.required = false;
                expiryDateInput.required = false;
                cvvInput.required = false;
                upiInput.required = false;
            } 
            else if (selectedMethod === 'card') {
                // Credit Card: Check if all card fields are filled
                const isCardNumValid = cardNumberInput.value.replace(/\D/g, '').length >= 15; // 15 for Amex, 16 for others
                const isExpiryValid = expiryDateInput.value.match(/^(0[1-9]|1[0-2])\/\d{2}$/);
                const isCvvValid = cvvInput.value.replace(/\D/g, '').length >= 3;

                // Enable 'required' for card, disable for UPI
                cardNumberInput.required = true;
                expiryDateInput.required = true;
                cvvInput.required = true;
                upiInput.required = false;

                if (isCardNumValid && isExpiryValid && isCvvValid) {
                    confirmBtn.disabled = false; // Enable button
                } else {
                    confirmBtn.disabled = true; // Disable button
                }
            } 
            else if (selectedMethod === 'upi') {
                // UPI: Check our verification flag
                
                // Enable 'required' for UPI, disable for card
                cardNumberInput.required = false;
                expiryDateInput.required = false;
                cvvInput.required = false;
                upiInput.required = true;

                // Button is only enabled if the 'isUpiVerified' flag is true
                confirmBtn.disabled = !isUpiVerified;
            }
        }

        /**
         * This function runs when a payment option is clicked.
         */
        function selectPayment(method) {
            // Reset verification status when switching methods
            isUpiVerified = false; 
            
            // Remove 'selected' from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected', 'border-orange-500');
                option.classList.add('border-gray-200');
            });

            // Add 'selected' to the clicked option
            const selectedOption = document.querySelector(`label[for="${method}"]`);
            selectedOption.classList.add('selected', 'border-orange-500');
            selectedOption.classList.remove('border-gray-200');

            // Hide all content blocks
            document.querySelectorAll('.form-content').forEach(content => {
                content.classList.remove('active');
            });

            // Show the relevant content block
            const activeContent = document.getElementById(`${method}-content`);
            if (activeContent) {
                activeContent.classList.add('active');
            }

            // --- THIS IS THE KEY CHANGE ---
            // Instead of simple logic, call our powerful validator function
            validatePaymentState();
        }

        /**
         * This function runs when the form is submitted.
         */
        function disableButton(form) {
            // Re-run validation one last time before submitting
            validatePaymentState();

            // If the button is disabled, stop the form submission
            if (confirmBtn.disabled) {
                // Optionally show an error
                alert("Please fill in all required payment details.");
                return false; // Stop submission
            }

            // If valid, show spinner and allow submission
            const btn = form.querySelector("#confirmBtn");
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            return true; // Allow form to submit
        }

        // Initialize the first option and add input formatters on page load
        document.addEventListener('DOMContentLoaded', () => {
            
            // --- ADD EVENT LISTENERS ---
            // Add listeners to all payment fields to validate as the user types
            cardNumberInput.addEventListener('input', validatePaymentState);
            expiryDateInput.addEventListener('input', validatePaymentState);
            cvvInput.addEventListener('input', validatePaymentState);
            upiInput.addEventListener('input', () => {
                isUpiVerified = false; // If they type, they must re-verify
                upiMessage.innerHTML = ''; // Clear message
                validatePaymentState();
            });

            // Format card number with spaces
            cardNumberInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                value = value.replace(/(\d{4})(?=\d)/g, '$1 '); // Add space every 4 digits
                e.target.value = value;
            });

            // Format expiry date with a slash
            expiryDateInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                if (value.length >= 2) {
                    let month = parseInt(value.slice(0, 2));
                    if (month > 12) value = '12' + value.slice(2);
                    if (month === 0) value = '01' + value.slice(2);
                }
                e.target.value = value;
            });

            // Restrict CVV to only numbers
            cvvInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            // --- UPDATED UPI Logic ---
            verifyUpiBtn.addEventListener('click', () => {
                const upiId = upiInput.value;
                const upiRegex = /^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/;

                if (upiRegex.test(upiId)) {
                    upiMessage.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1"></i> UPI ID verified successfully.';
                    isUpiVerified = true; // Set flag to true
                } else {
                    upiMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 mr-1"></i> Invalid format. Use name@bank.';
                    isUpiVerified = false; // Set flag to false
                }
                
                // Re-run validation after check
                validatePaymentState();
            });
            
            // Set the initial state when the page loads
            selectPayment('cod');
        });
    </script>

    <footer>
        <p>&copy; 2025 MyRecipe. All rights reserved.</p>
        <a href="index.php">Home Page</a>
        <a href="about_us.php">About Us</a>
        <a href="#">Our Products</a>
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <div class="footer-social">
            <a href="https://facebook.com" target="_blank" class="social-icon facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://twitter.com" target="_blank" class="social-icon twitter">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="https://instagram.com" target="_blank" class="social-icon instagram">
                <i class="fab fa-instagram"></i>
            </a>
        </div>
    </footer>
</body>

</html>