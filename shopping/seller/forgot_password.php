<?php
session_start();
include("include/config.php"); // Ensure this path is correct

$message = ''; // For displaying general status messages
$message_type = ''; // To control styling (e.g., success, error)

// Initialize input variables and field-specific error messages
$entered_email = '';
$field_errors = []; // Array to hold validation errors for specific fields

if (isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $entered_email = $email; // Store for repopulating the email field on validation failure

    // --- Server-Side Validation ---
    $errors = []; // Temporary array to collect all errors

    // Email Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    // Password Validation: Min 8 chars, at least one uppercase, one lowercase, one number, one special character
    $password_issues = [];
    if (strlen($newPassword) < 8) {
        $password_issues[] = "Minimum 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $newPassword)) {
        $password_issues[] = "At least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $newPassword)) {
        $password_issues[] = "At least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $newPassword)) {
        $password_issues[] = "At least one number.";
    }
    // Regex for at least one special character (not alphanumeric)
    if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        $password_issues[] = "At least one special character (e.g., !, @, #, $).";
    }
    if (!empty($password_issues)) {
        $errors['new_password'] = "Password needs: " . implode(", ", $password_issues);
    }

    // Confirm Password Validation
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // If preliminary validations pass, check if email exists in the database
    if (empty($errors)) {
        $stmt_check_email = $con->prepare("SELECT id FROM sellers WHERE email = ?");
        if ($stmt_check_email === false) {
            $message = '<div class="message error">Database error during email check. Please try again later.</div>';
            $message_type = 'error';
            error_log("Forgot Password Email Check Prepare Error: " . $con->error);
        } else {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();

            if ($result_check_email->num_rows === 0) {
                // If email not found, add an error
                $errors['email'] = "No account found with that email address.";
            }
            $stmt_check_email->close();
        }
    }

    // If there are any validation or database errors
    if (!empty($errors)) {
        // Prepare a combined error message for the top of the form
        $message = '<div class="message error">Please correct the following errors:<ul>';
        foreach ($errors as $field => $error_msg) {
            $message .= '<li>' . htmlspecialchars($error_msg) . '</li>';
            // Store individual field errors for displaying next to the input fields
            $field_errors[$field] = $error_msg;
        }
        $message .= '</ul></div>';
        $message_type = 'error';

        // Use session to persist messages and entered email across POST-redirect-GET
        $_SESSION['forgot_password_message'] = $message;
        $_SESSION['forgot_password_message_type'] = $message_type;
        $_SESSION['forgot_password_field_errors'] = $field_errors;
        $_SESSION['forgot_password_input']['email'] = $entered_email;

        header("Location: forgot_password.php");
        exit();

    } else {
        // If all validation passes and email exists, update the password
        // Use password_hash() for secure password storage
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password in the 'sellers' table
        $stmt_update_password = $con->prepare("UPDATE sellers SET password = ? WHERE email = ?");
        if ($stmt_update_password === false) {
            $message = '<div class="message error">Database error: Could not update password. Please try again.</div>';
            $message_type = 'error';
            error_log("Forgot Password Update Prepare Error: " . $con->error);
        } else {
            $stmt_update_password->bind_param("ss", $hashedPassword, $email);
            if ($stmt_update_password->execute()) {
                $message = '<div class="message success">Your password has been successfully reset. You can now <a href="login_approved.php">login</a>.</div>';
                $message_type = 'success';
                // Clear any leftover session data related to errors/inputs
                unset($_SESSION['forgot_password_field_errors']);
                unset($_SESSION['forgot_password_input']);
            } else {
                $message = '<div class="message error">Failed to reset password. Error: ' . $stmt_update_password->error . '</div>';
                $message_type = 'error';
                error_log("Forgot Password Update Execute Error: " . $stmt_update_password->error);
            }
            $stmt_update_password->close();
        }
    }
}

// Retrieve messages, message type, input, and field errors from session on page load (after redirect)
if (isset($_SESSION['forgot_password_message'])) {
    $message = $_SESSION['forgot_password_message'];
    $message_type = $_SESSION['forgot_password_message_type'] ?? 'info';
    unset($_SESSION['forgot_password_message']);
    unset($_SESSION['forgot_password_message_type']);
}
if (isset($_SESSION['forgot_password_field_errors'])) {
    $field_errors = $_SESSION['forgot_password_field_errors'];
    unset($_SESSION['forgot_password_field_errors']);
}
if (isset($_SESSION['forgot_password_input'])) {
    $entered_email = $_SESSION['forgot_password_input']['email'] ?? '';
    unset($_SESSION['forgot_password_input']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Marketeer Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .header .logo img {
            height: 40px;
            margin-left: 20px;
        }
        .header-actions {
            margin-right: 20px;
        }
        .header-actions a {
            text-decoration: none;
            color: #333;
            padding: 8px 15px;
            border-radius: 5px;
            margin-left: 10px;
        }
        .header-actions .login-btn {
            border: 1px solid #ccc;
            background-color: #ffcc00; /* Highlight login */
            color: #333;
            font-weight: 500;
        }
        .header-actions .start-selling-btn {
            background-color: #003366;
            color: #fff;
        }

        .content-wrapper {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .forgot-password-card {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 450px;
            width: 100%;
        }
        .forgot-password-card h2 {
            color: #003366;
            margin-bottom: 20px;
            font-size: 2em;
        }
        .forgot-password-card p {
            margin-bottom: 25px;
            font-size: 1em;
            line-height: 1.5;
            color: #555;
        }
        .forgot-password-card input[type="email"],
        .forgot-password-card input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px 10px;
            margin-bottom: 15px; /* Adjusted margin to make space for error messages */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            display: block; /* Make them block elements for better error display */
            margin-left: auto;
            margin-right: auto;
        }
        .forgot-password-card button {
            background-color: #003366;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
            margin-top: 10px; /* Space after last input/error */
        }
        .forgot-password-card button:hover {
            background-color: #0056b3;
        }
        .forgot-password-card .back-to-login {
            margin-top: 25px;
            font-size: 0.95em;
        }
        .forgot-password-card .back-to-login a {
            color: #007bff;
            text-decoration: none;
        }
        .forgot-password-card .back-to-login a:hover {
            text-decoration: underline;
        }

        /* Message styling (copied from login_approved.php for consistency) */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
            text-align: left;
        }
        .message.info {
            background-color: #e0f2f7; /* Light blue */
            color: #01579b;
            border: 1px solid #81d4fa;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Specific style for inline error messages */
        .error-message {
            color: #dc3545; /* Red for errors */
            font-size: 0.85em;
            margin-top: -10px; /* Pull it closer to the input field */
            margin-bottom: 10px; /* Space it from the next input */
            display: block; /* Ensure it takes its own line */
            text-align: left;
            padding-left: 5px; /* Small indent */
        }

        .footer {
            background-color: #333;
            color: #fff;
            padding: 20px 0;
            text-align: center;
            font-size: 0.8em;
            margin-top: auto;
        }
        .footer a {
            color: #ffcc00;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="https://www.makro.co.za/medias/sys_master/images/images/hbb/h57/9059530262558/makro-logo-new.png" alt="Makro Logo">
        </div>
        <div class="header-actions">
            <a href="login_approved.php" class="login-btn">Login</a>
            <a href="index.php" class="start-selling-btn">Start Selling</a>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="forgot-password-card">
            <h2>Reset Your Password</h2>
            <p>Please enter your registered email address and your new password.</p>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <form method="post" onsubmit="return validateResetPassword();">
                <input type="email" id="email" name="email" placeholder="Your Registered Email Address" required autocomplete="email" value="<?php echo htmlspecialchars($entered_email); ?>">
                <span class="error-message" id="email-error"><?php echo htmlspecialchars($field_errors['email'] ?? ''); ?></span>

                <input type="password" id="new_password" name="new_password" placeholder="New Password" required autocomplete="new-password">
                <span class="error-message" id="new_password-error"><?php echo htmlspecialchars($field_errors['new_password'] ?? ''); ?></span>

                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required autocomplete="new-password">
                <span class="error-message" id="confirm_password-error"><?php echo htmlspecialchars($field_errors['confirm_password'] ?? ''); ?></span>

                <button type="submit" name="reset_password">Reset Password</button>
            </form>
            <p class="back-to-login">Remembered your password? <a href="login_approved.php">Back to Login</a></p>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Marketeer . All Rights Reserved</p>
            <p>
                <a href="#">Terms of Use</a>
                <a href="#">Privacy Policy</a>
            </p>
        </div>
    </footer>

    <script>
        function validateResetPassword() {
            let isValid = true;

            // Helper function to display errors
            function displayError(fieldId, message) {
                const errorSpan = document.getElementById(fieldId + '-error');
                if (errorSpan) {
                    errorSpan.textContent = message;
                    errorSpan.style.display = 'block'; // Ensure it's visible
                }
                isValid = false;
            }

            // Helper function to clear errors
            function clearError(fieldId) {
                const errorSpan = document.getElementById(fieldId + '-error');
                if (errorSpan) {
                    errorSpan.textContent = '';
                    errorSpan.style.display = 'none'; // Hide if no error
                }
            }

            // Clear previous errors
            clearError('email');
            clearError('new_password');
            clearError('confirm_password');

            // Get input values
            const email = document.getElementById('email').value.trim();
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // --- Client-Side Email Validation ---
            if (email === "") {
                displayError('email', "Email address cannot be empty.");
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                displayError('email', "Please enter a valid email address.");
            }

            // --- Client-Side New Password Validation (Matching Server-Side Standards) ---
            let passwordErrors = [];
            if (newPassword.length < 8) {
                passwordErrors.push("Minimum 8 characters.");
            }
            if (!/[A-Z]/.test(newPassword)) {
                passwordErrors.push("At least one uppercase letter.");
            }
            if (!/[a-z]/.test(newPassword)) {
                passwordErrors.push("At least one lowercase letter.");
            }
            if (!/[0-9]/.test(newPassword)) {
                passwordErrors.push("At least one number.");
            }
            // Check for at least one special character
            if (!/[^A-Za-z0-9]/.test(newPassword)) {
                passwordErrors.push("At least one special character (e.g., !, @, #, $).");
            }

            if (passwordErrors.length > 0) {
                displayError('new_password', "Password needs: " + passwordErrors.join(" "));
            }

            // --- Client-Side Confirm Password Validation ---
            if (newPassword !== confirmPassword) {
                displayError('confirm_password', "Passwords do not match.");
            }

            return isValid; // Prevent form submission if validation failed
        }
    </script>
</body>
</html>