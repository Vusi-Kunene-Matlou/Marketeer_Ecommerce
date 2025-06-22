<?php
session_start();
include("include/config.php");

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables for displaying errors and old input values
$general_message = ''; // For general success/error messages not tied to a specific field
$seller_field_errors = []; // Associative array to hold field-specific errors
$seller_form_input = []; // Associative array to hold old input values

// Retrieve any messages/errors from session on page load
if (isset($_SESSION['general_message'])) {
    $general_message = $_SESSION['general_message'];
    unset($_SESSION['general_message']); // Clear after use
}
if (isset($_SESSION['seller_form_errors'])) {
    $seller_field_errors = $_SESSION['seller_form_errors'];
    unset($_SESSION['seller_form_errors']); // Clear after use
}
if (isset($_SESSION['seller_form_input'])) {
    $seller_form_input = $_SESSION['seller_form_input'];
    unset($_SESSION['seller_form_input']); // Clear after use
}


// Check if $con is an active MySQLi connection
if (!isset($con) || $con->connect_error) {
    die("Database connection failed: " . (isset($con) ? $con->connect_error : "Connection object not set."));
}

if (isset($_POST['register'])) {
    // Collect and sanitize input
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobileNumber = trim($_POST['mobile_number']);
    $rawPassword = $_POST['password'];
    $termsAccepted = isset($_POST['terms']) ? true : false; // Checkbox value
    $privacyAccepted = isset($_POST['privacy']) ? true : false; // Checkbox value

    // Store current POST data to repopulate fields if validation fails
    $seller_form_input = $_POST;

    // --- Server-Side Input Validation ---
    // Initialize error array for this submission
    $current_submission_errors = [];

    // Full Name Validation
    if (empty($fullName)) {
        $current_submission_errors['full_name'] = "Full Name cannot be empty.";
    }

    // Email Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $current_submission_errors['email'] = "Invalid email format. Please use a valid email (e.g., user@example.com).";
    }

    // Mobile Number Validation (South African Standard: +27XXXXXXXXX)
    if (!preg_match('/^\+27[0-9]{9}$/', $mobileNumber)) {
        $current_submission_errors['mobile_number'] = "Invalid South African mobile number (e.g., +27721234567).";
    }

    // Password Validation: Min 8 chars, at least one uppercase, one lowercase, one number, one special character
    $password_issues = [];
    if (strlen($rawPassword) < 8) {
        $password_issues[] = "Minimum 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $rawPassword)) {
        $password_issues[] = "At least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $rawPassword)) {
        $password_issues[] = "At least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $rawPassword)) {
        $password_issues[] = "At least one number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $rawPassword)) { // Checks for anything not alphanumeric
        $password_issues[] = "At least one special character (e.g., !, @, #, $).";
    }
    if (!empty($password_issues)) {
        $current_submission_errors['password'] = "Password needs: " . implode(", ", $password_issues);
    }

    // Checkbox Validation
    if (!$termsAccepted) {
        $current_submission_errors['terms_privacy'] = "You must agree to the Terms of Use.";
    }
    if (!$privacyAccepted) {
        $current_submission_errors['terms_privacy'] = (isset($current_submission_errors['terms_privacy']) ? $current_submission_errors['terms_privacy'] . "<br>" : "") . "You must agree to the Privacy Policy.";
    }


    // Check if email already exists (only if email format is valid and no other email errors)
    if (!isset($current_submission_errors['email'])) {
        $stmt_check_email = $con->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt_check_email === false) {
            $current_submission_errors['email'] = "Database error during email check. Please try again.";
            error_log("Email check prepare error: " . $con->error);
        } else {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $current_submission_errors['email'] = "This email is already registered.";
            }
            $stmt_check_email->close();
        }
    }


    // If there are validation errors, store them in session and redirect
    if (!empty($current_submission_errors)) {
        $_SESSION['seller_form_errors'] = $current_submission_errors;
        $_SESSION['seller_form_input'] = $seller_form_input;
        header("Location: index.php#register-section"); // Redirect to self and scroll to form
        exit();
    }


    // If all validation passes, proceed with database operations
    // Hash the password securely using password_hash()
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    // --- STEP 1: Insert into 'users' table ---
    $userInsertQuery = $con->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    if ($userInsertQuery === false) {
        $general_message = "User registration failed (DB prepare error).";
        error_log("User Insert Prepare Error: " . $con->error);
    } else {
        $userInsertQuery->bind_param("ss", $email, $hashedPassword);
        if ($userInsertQuery->execute()) {
            $user_id = $userInsertQuery->insert_id; // Get the ID of the newly created user

            // --- STEP 2: Insert into 'sellers' table using the new user_id ---
            $sellerInsertQuery = $con->prepare("INSERT INTO sellers (user_id, full_name, email, mobile_number, password) VALUES (?, ?, ?, ?, ?)");
            if ($sellerInsertQuery === false) {
                $general_message = "Seller registration failed (DB prepare error).";
                error_log("Seller Insert Prepare Error: " . $con->error);
                // Consider deleting the user if seller creation is mandatory
                // $con->query("DELETE FROM users WHERE id = $user_id");
            } else {
                $sellerInsertQuery->bind_param("issss", $user_id, $fullName, $email, $mobileNumber, $hashedPassword); // Note: storing hashed password in sellers table too, might be redundant depending on your schema design.
                if ($sellerInsertQuery->execute()) {
                    $_SESSION['registration_step_1_complete'] = true;
                    $_SESSION['seller_id'] = $sellerInsertQuery->insert_id;
                    $_SESSION['user_id'] = $user_id;

                    // Clear session data used for form repopulation/errors on success
                    unset($_SESSION['seller_form_errors']);
                    unset($_SESSION['seller_form_input']);
                    $_SESSION['general_message'] = "Registration successful! Please continue with business details.";
                    header("Location: business_details.php");
                    exit();
                } else {
                    $general_message = "Seller registration failed. Error: " . $sellerInsertQuery->error;
                    error_log("Seller Insert Execute Error: " . $sellerInsertQuery->error);
                    // Critical: Rollback user creation if seller creation is required
                    $con->query("DELETE FROM users WHERE id = $user_id");
                }
                $sellerInsertQuery->close();
            }
        } else {
            // This might happen if email uniqueness constraint is violated, though checked above
            $general_message = "User registration failed. This email might already be registered. Error: " . $userInsertQuery->error;
            error_log("User Insert Execute Error: " . $userInsertQuery->error);
        }
        $userInsertQuery->close();
    }
    // Store general message in session for display after redirect
    if ($general_message !== '') {
        $_SESSION['general_message'] = $general_message;
        header("Location: index.php#register-section");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Marketeer Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Keep all the CSS from the previous register.php answer */
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; background-color: #f4f7f6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header .logo img { height: 40px; margin-left: 20px; }
        .header-actions { margin-right: 20px; }
        .header-actions a { text-decoration: none; color: #333; padding: 8px 15px; border-radius: 5px; margin-left: 10px; }
        .header-actions .login-btn { border: 1px solid #ccc; }
        .header-actions .start-selling-btn { background-color: #ffcc00; color: #333; font-weight: 500; }
        .hero-section { background-color: #003366; color: #fff; padding: 60px 0; text-align: center; }
        .hero-section h1 { font-size: 2.8em; margin-bottom: 15px; line-height: 1.2; }
        .hero-section p { font-size: 1.3em; opacity: 0.9; }
        .stats-grid { display: flex; justify-content: center; gap: 50px; margin-top: 40px; }
        .stat-item { text-align: center; }
        .stat-item .number { font-size: 2.5em; font-weight: 700; color: #ffcc00; }
        .stat-item .label { font-size: 1.1em; margin-top: 5px; opacity: 0.8; }
        .main-content { display: flex; justify-content: space-between; gap: 40px; padding: 50px 0; margin-top: -50px; }
        .registration-card { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex: 1; min-width: 450px; margin-left: 20px; }
        .registration-card h2 { color: #003366; margin-bottom: 25px; display: flex; align-items: center; }
        .registration-card h2 .icon { font-size: 24px; margin-right: 10px; color: #ffcc00; }
        .registration-card .input-group { margin-bottom: 20px; }
        .registration-card .input-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .registration-card .input-group input[type="text"], .registration-card .input-group input[type="email"], .registration-card .input-group input[type="password"] { width: calc(100% - 20px); padding: 12px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; box-sizing: border-box; }
        .registration-card .checkbox-group { margin-bottom: 20px; font-size: 0.9em; }
        .registration-card .checkbox-group input { margin-right: 10px; }
        .registration-card .checkbox-group a { color: #007bff; text-decoration: none; }
        .registration-card button { background-color: #007bff; color: #fff; padding: 12px 25px; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; transition: background-color 0.3s ease; }
        .registration-card button:hover { background-color: #0056b3; }
        .message { margin-top: 20px; padding: 15px; border-radius: 5px; font-weight: 500; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .faq-section { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex: 1; min-width: 350px; margin-right: 20px; }
        .faq-section h3 { color: #003366; margin-bottom: 20px; }
        .faq-item { margin-bottom: 15px; }
        .faq-item h4 { margin: 0; cursor: pointer; color: #555; display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #eee; }
        .faq-item h4:hover { color: #007bff; }
        .faq-item .answer { display: none; padding: 10px 0; color: #666; font-size: 0.9em; }
        .faq-item h4 span.arrow { transform: rotate(0deg); transition: transform 0.3s ease; }
        .faq-item.active h4 span.arrow { transform: rotate(180deg); }
        .faq-item.active .answer { display: block; }
        .seller-journey-section { background-color: #f0f0f0; padding: 60px 0; text-align: center; }
        .seller-journey-section h2 { color: #003366; font-size: 2.2em; margin-bottom: 10px; }
        .seller-journey-section p.subtitle { font-size: 1.1em; color: #555; margin-bottom: 50px; }
        .journey-steps { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
        .step-item { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 250px; text-align: center; transition: transform 0.3s ease; }
        .step-item:hover { transform: translateY(-5px); }
        .step-item .icon { font-size: 4em; color: #ffcc00; margin-bottom: 15px; }
        .step-item h3 { color: #003366; margin-bottom: 10px; font-size: 1.3em; }
        .step-item p { color: #666; font-size: 0.95em; line-height: 1.5; }
        .cta-section { text-align: center; padding: 60px 0; background-color: #003366; color: #fff; }
        .cta-section h2 { font-size: 2.5em; margin-bottom: 20px; }
        .cta-section .btn { background-color: #ffcc00; color: #333; padding: 15px 30px; border-radius: 5px; text-decoration: none; font-weight: 700; font-size: 1.2em; transition: background-color 0.3s ease; }
        .cta-section .btn:hover { background-color: #e6b800; }
        .footer { background-color: #333; color: #fff; padding: 30px 0; text-align: center; font-size: 0.9em; }
        .footer a { color: #ffcc00; text-decoration: none; margin: 0 10px; }
        .footer a:hover { text-decoration: underline; }
        .icon-user::before { content: '👤'; }
        .icon-building::before { content: '🏢'; }
        .icon-clipboard::before { content: '📋'; }
        .icon-truck::before { content: '🚚'; }
        .icon-chart::before { content: '📈'; }
        .icon-info::before { content: 'ℹ️'; }

        /* Custom Styles for Inline Errors */
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: block; /* Ensures it takes its own line */
        }
        .input-group input.is-invalid {
            border-color: red;
            box-shadow: 0 0 0 0.2rem rgba(255, 0, 0, 0.25);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
        <a href="../land_page_index.php" class="login-btn">Back to Marketeer</a>
        </div>
        <div class="header-actions">
            <a href="login_approved.php" class="login-btn">Login</a> <a href="#register-section" class="start-selling-btn">Start Selling</a>
        </div>
    </header>

    <section class="hero-section">
        <div class="container">
            <h1>Welcome to our new platform!</h1>
            <p>Expand your business with South Africa's leading omni-channel retailer.</p>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="number">35,000,000+</div>
                    <div class="label">Customer visits per year</div>
                </div>
                <div class="stat-item">
                    <div class="number">11,000,000+</div>
                    <div class="label">Searches on the site per year</div>
                </div>
                <div class="stat-item">
                    <div class="number">100+</div>
                    <div class="label">Different product categories</div>
                </div>
            </div>
        </div>
    </section>

    <div class="container main-content">
        <div class="registration-card" id="register-section">
            <div class="breadcrumbs" style="margin-bottom: 20px; font-size: 0.9em;">
                <a href="#" style="color:#007bff; text-decoration:none;">Personal Details</a> &gt;
                <span style="color:#666;">Business Details</span>
            </div>
            <h2><span class="icon-user"></span> Profile Information</h2>
            <?php if (!empty($general_message)) { ?>
                <div class="message <?php echo strpos($general_message, 'successful') !== false ? 'success' : 'error'; ?>"><?php echo $general_message; ?></div>
            <?php } ?>

            <form method="post" onsubmit="return validateSellerRegistration();">
                <div class="input-group">
                    <label for="full_name">Full Name <span style="color:red;">*</span></label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($seller_form_input['full_name'] ?? ''); ?>">
                    <span class="error-message" id="full_name-error">
                        <?php echo htmlspecialchars($seller_field_errors['full_name'] ?? ''); ?>
                    </span>
                </div>
                <div class="input-group">
                    <label for="email">Email <span style="color:red;">*</span></label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($seller_form_input['email'] ?? ''); ?>">
                    <span class="error-message" id="email-error">
                        <?php echo htmlspecialchars($seller_field_errors['email'] ?? ''); ?>
                    </span>
                </div>
                <div class="input-group">
                    <label for="mobile_number">Mobile Number <span style="color:red;">*</span></label>
                    <input type="text" id="mobile_number" name="mobile_number" placeholder="+27XXXXXXXXX" value="<?php echo htmlspecialchars($seller_form_input['mobile_number'] ?? ''); ?>" required>
                    <span class="error-message" id="mobile_number-error">
                        <?php echo htmlspecialchars($seller_field_errors['mobile_number'] ?? ''); ?>
                    </span>
                </div>
                <div class="input-group">
                    <label for="password">Create Password <span style="color:red;">*</span></label>
                    <input type="password" id="password" name="password" placeholder="Create your password" required>
                    <span class="error-message" id="password-error">
                        <?php echo htmlspecialchars($seller_field_errors['password'] ?? ''); ?>
                    </span>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" <?php echo (isset($seller_form_input['terms']) && $seller_form_input['terms'] == 'on') ? 'checked' : ''; ?>>
                    <label for="terms">By submitting this form you agree to our <a href="#" target="_blank">Terms of Use</a></label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="privacy" name="privacy" <?php echo (isset($seller_form_input['privacy']) && $seller_form_input['privacy'] == 'on') ? 'checked' : ''; ?>>
                    <label for="privacy">By submitting this form you agree to our <a href="#" target="_blank">Privacy Policy</a></label>
                </div>
                <span class="error-message" id="terms_privacy-error">
                    <?php echo htmlspecialchars($seller_field_errors['terms_privacy'] ?? ''); ?>
                </span>

                <button type="submit" name="register">Continue</button>
            </form>
        </div>

        <div class="faq-section">
            <h3>Frequently Asked Questions</h3>
            <div class="faq-item">
                <h4>Who can register to be a seller of Makro Marketplace? <span class="arrow">&#9660;</span></h4>
                <div class="answer">Any registered business with a valid South African business registration number and tax compliance.</div>
            </div>
            <div class="faq-item">
                <h4>How do I register on Makro Marketplace? <span class="arrow">&#9660;</span></h4>
                <div class="answer">Click on "Start Selling", fill out your profile and business details, and submit for approval.</div>
            </div>
            <div class="faq-item">
                <h4>Do I need to be a registered company to register? <span class="arrow">&#9660;</span></h4>
                <div class="answer">Yes, you must be a registered legal entity in South Africa.</div>
            </div>
            <div class="faq-item">
                <h4>What bank documents are required to verify my account? <span class="arrow">&#9660;</span></h4>
                <div class="answer">Proof of bank account (e.g., bank statement) in the name of your registered business.</div>
            </div>
            <div class="faq-item">
                <h4>What are the next steps after registration? <span class="arrow">&#9660;</span></h4>
                <div class="answer">Once approved, you can complete your business profile, list products, and start selling.</div>
            </div>
            <div class="faq-item">
                <h4>What happens if my registration documents are rejected? <span class="arrow">&#9660;</span></h4>
                <div class="answer">You will receive an email detailing the reasons for rejection and steps to rectify them.</div>
            </div>
            <div class="faq-item">
                <h4>How long does it take? <span class="arrow">&#9660;</span></h4>
                <div class="answer">The registration process typically takes 10-15 minutes, with approval taking 3-5 business days.</div>
            </div>
        </div>
    </div>

    <section class="seller-journey-section">
        <div class="container">
            <h2>Your Makro Marketplace journey</h2>
            <p class="subtitle">Sell in just a few simple steps on Makro Marketplace</p>
            <div class="journey-steps">
                <div class="step-item">
                    <div class="icon icon-clipboard"></div>
                    <h3>Create</h3>
                    <p>Register in just 10 mins with valid Business information, address, ID & bank details</p>
                </div>
                <div class="step-item">
                    <div class="icon icon-user"></div>
                    <h3>Manage Your Profile</h3>
                    <p>Ensure that your contact details, shipping address, and any additional relevant details are filled out in all profile areas.</p>
                </div>
                <div class="step-item">
                    <div class="icon icon-building"></div>
                    <h3>List</h3>
                    <p>Find out what you need to start listing, such as latching and how to create a single listing.</p>
                </div>
                <div class="step-item">
                    <div class="icon icon-truck"></div>
                    <h3>Start selling</h3>
                    <p>Activate your products and start selling.</p>
                </div>
                <div class="step-item">
                    <div class="icon icon-chart"></div>
                    <h3>Growth</h3>
                    <p>Use promotions to increase your online sales</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Ready to expand your business?</h2>
            <a href="#register-section" class="btn">Start Selling Now</a>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Massmart. All Rights Reserved</p>
            <p>
                <a href="#">Terms of Use</a>
                <a href="#">Privacy Policy</a>
            </p>
        </div>
    </footer>

    <script>
        // JavaScript for FAQ dropdowns
        document.querySelectorAll('.faq-item h4').forEach(item => {
            item.addEventListener('click', () => {
                const parent = item.closest('.faq-item');
                parent.classList.toggle('active');
            });
        });

        // Client-side validation for seller registration form
        function validateSellerRegistration() {
            let isValid = true;

            // Helper function to display error messages and mark fields invalid
            function displayError(fieldId, message) {
                const errorSpan = document.getElementById(fieldId + '-error');
                const inputField = document.getElementById(fieldId);
                if (errorSpan) {
                    errorSpan.innerHTML = message;
                }
                if (inputField) {
                    inputField.classList.add('is-invalid');
                }
                isValid = false;
            }

            // Helper function to clear error messages and valid fields
            function clearError(fieldId) {
                const errorSpan = document.getElementById(fieldId + '-error');
                const inputField = document.getElementById(fieldId);
                if (errorSpan) {
                    errorSpan.innerHTML = '';
                }
                if (inputField) {
                    inputField.classList.remove('is-invalid');
                }
            }

            // Clear all previous errors
            clearError('full_name');
            clearError('email');
            clearError('mobile_number');
            clearError('password');
            clearError('terms_privacy'); // For combined checkbox errors

            // Get form inputs
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const mobileNumber = document.getElementById('mobile_number').value.trim();
            const password = document.getElementById('password').value;
            const termsChecked = document.getElementById('terms').checked;
            const privacyChecked = document.getElementById('privacy').checked;


            // Full Name Validation
            if (fullName === "") {
                displayError('full_name', "Full Name cannot be empty.");
            }

            // Email Validation
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                displayError('email', "Invalid email format (e.g., user@example.com).");
            }

            // Mobile Number Validation (South African Standard)
            // Pattern: +27 followed by 9 digits
            if (!/^\+27[0-9]{9}$/.test(mobileNumber)) {
                displayError('mobile_number', "Invalid SA mobile number (e.g., +27721234567).");
            }

            // Password Validation (Complexity)
            let passwordErrors = [];
            if (password.length < 8) {
                passwordErrors.push("Minimum 8 characters.");
            }
            if (!/[A-Z]/.test(password)) {
                passwordErrors.push("At least one uppercase letter.");
            }
            if (!/[a-z]/.test(password)) {
                passwordErrors.push("At least one lowercase letter.");
            }
            if (!/[0-9]/.test(password)) {
                passwordErrors.push("At least one number.");
            }
            if (!/[^A-Za-z0-9]/.test(password)) {
                passwordErrors.push("At least one special character.");
            }
            if (passwordErrors.length > 0) {
                displayError('password', "Password needs: " + passwordErrors.join(" "));
            }

            // Checkbox Validation
            if (!termsChecked) {
                displayError('terms_privacy', "You must agree to the Terms of Use.");
            }
            if (!privacyChecked) {
                 // If terms also has an error, append, otherwise set
                const existingError = document.getElementById('terms_privacy-error').innerHTML;
                if(existingError && existingError.includes("Terms of Use")) {
                     document.getElementById('terms_privacy-error').innerHTML += "<br>You must agree to the Privacy Policy.";
                } else {
                    displayError('terms_privacy', "You must agree to the Privacy Policy.");
                }
                // Don't set isValid = false here again if it's already false from termsChecked
                if (termsChecked) { // Only set isValid=false if privacy is the only un-checked box
                    isValid = false;
                }
            }


            // Scroll to the registration card if there are errors
            if (!isValid) {
                document.getElementById('register-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            return isValid;
        }

        // Add event listeners for instant feedback on input change/blur
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['full_name', 'email', 'mobile_number', 'password'];
            inputs.forEach(id => {
                const inputElement = document.getElementById(id);
                if (inputElement) {
                    inputElement.addEventListener('blur', function() {
                        // Re-validate only this field on blur, or run full validation
                        // For simplicity, let's just clear its own error on focus and re-validate onSubmit.
                        // Or you can call a specific validation function for each field.
                        // For now, on blur, we will just clear the error.
                         document.getElementById(id + '-error').innerHTML = '';
                         inputElement.classList.remove('is-invalid');
                         // For password, you might want to re-evaluate it immediately after typing
                         if (id === 'password') {
                             // Minimal password check on blur
                             const pass = inputElement.value;
                             let msg = [];
                             if (pass.length < 8) msg.push("Min 8 chars.");
                             if (!/[A-Z]/.test(pass)) msg.push("One uppercase.");
                             if (!/[a-z]/.test(pass)) msg.push("One lowercase.");
                             if (!/[0-9]/.test(pass)) msg.push("One number.");
                             if (!/[^A-Za-z0-9]/.test(pass)) msg.push("One special char.");
                             if (msg.length > 0) {
                                 document.getElementById(id + '-error').innerHTML = "Password needs: " + msg.join(" ");
                                 inputElement.classList.add('is-invalid');
                             }
                         }
                    });
                    inputElement.addEventListener('focus', function() {
                         document.getElementById(id + '-error').innerHTML = '';
                         inputElement.classList.remove('is-invalid');
                    });
                }
            });

            // Event listeners for checkboxes to clear/show errors
            const termsCheckbox = document.getElementById('terms');
            const privacyCheckbox = document.getElementById('privacy');
            const termsPrivacyErrorSpan = document.getElementById('terms_privacy-error');

            const updateCheckboxError = () => {
                let errorMsgs = [];
                if (!termsCheckbox.checked) {
                    errorMsgs.push("You must agree to the Terms of Use.");
                }
                if (!privacyCheckbox.checked) {
                    errorMsgs.push("You must agree to the Privacy Policy.");
                }
                termsPrivacyErrorSpan.innerHTML = errorMsgs.join("<br>");
            };

            if (termsCheckbox && privacyCheckbox && termsPrivacyErrorSpan) {
                termsCheckbox.addEventListener('change', updateCheckboxError);
                privacyCheckbox.addEventListener('change', updateCheckboxError);
                // Initial check on page load if values are pre-filled (e.g. after server-side validation error)
                updateCheckboxError();
            }
        });
    </script>
</body>
</html>