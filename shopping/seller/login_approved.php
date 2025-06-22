<?php
session_start();
include("include/config.php"); // Path to your main config.php

$login_message = '';
$message_type = ''; // To control styling (e.g., success, error, info)

// Check for messages passed from application_submitted.php
if (isset($_SESSION['application_status_message'])) {
    $login_message = $_SESSION['application_status_message'];
    $message_type = 'info'; // Or 'success'
    unset($_SESSION['application_status_message']); // Clear the message after displaying
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $enteredPassword = $_POST['password']; // Get the plain text password entered by the user

    // 1. Query the sellers table based ONLY on email
    // We need to retrieve the stored password hash and application_status
    $stmt = $con->prepare("SELECT id, full_name, email, password, application_status, rejection_reason FROM sellers WHERE email = ?");
    if ($stmt === false) {
        $login_message = "Database error: Unable to prepare statement.";
        $message_type = 'error';
    } else {
        $stmt->bind_param("s", $email); // Bind only the email
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $seller = $result->fetch_assoc();
            $storedHashedPassword = $seller['password']; // Get the securely hashed password from the DB

            // 2. Verify the entered plain text password against the stored hash
            if (password_verify($enteredPassword, $storedHashedPassword)) {
                // Password matches, now check application status
                if ($seller['application_status'] === 'approved') {
                    // Login successful for an approved seller
                    $_SESSION['seller_login_status'] = true;
                    $_SESSION['seller_id'] = $seller['id'];
                    $_SESSION['seller_name'] = $seller['full_name'];

                    // --- MODIFIED SECTION START ---
                    // Since login_approved.php and seller_dashboard.php are in the same 'seller' folder,
                    // a simple relative path is enough for the redirect.
                    header('Location: change-password.php');
                    exit();
                    // --- MODIFIED SECTION END ---

                } elseif ($seller['application_status'] === 'pending') {
                    $login_message = "Your application is still pending review by the admin. Please wait for approval.";
                    $message_type = 'warning';
                } elseif ($seller['application_status'] === 'rejected') {
                    $login_message = "Your application has been rejected. You may need to re-apply or contact support.";
                    $message_type = 'error';
                    if (!empty($seller['rejection_reason'])) {
                        $login_message .= " Reason: " . htmlspecialchars($seller['rejection_reason']);
                    }
                    $login_message .= " <a href='index.php'>Click here to re-apply</a>."; // Changed to index.php
                }
            } else {
                // Password does NOT match
                $login_message = "Invalid email or password."; // Generic message for security
                $message_type = 'error';
            }
        } else {
            // Email not found
            $login_message = "Invalid email or password."; // Generic message for security
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login | Marketeer Marketplace</title>
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
            background-color: #ffcc00; /* Highlight login as it's the current page's action */
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

        .login-card {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .login-card h2 {
            color: #003366;
            margin-bottom: 30px;
            font-size: 2em;
        }
        .login-card input[type="email"],
        .login-card input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .login-card button {
            background-color: #003366;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .login-card button:hover {
            background-color: #0056b3;
        }
        .login-card p {
            margin-top: 20px;
            font-size: 0.95em;
        }
        .login-card p a {
            color: #007bff;
            text-decoration: none;
        }
        .login-card p a:hover {
            text-decoration: underline;
        }

        /* Message styling */
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
            border: 1px solid #721c24;
            border: 1px solid #f5c6cb;
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
        <a href="index.php" class="login-btn">Back to Profile</a>

        </div>
        <div class="header-actions">
            <a href="login_approved.php" class="login-btn">Login</a>
            <a href="index.php" class="start-selling-btn">Start Selling</a>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="login-card">
            <h2>Seller Login</h2>

            <?php if (!empty($login_message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $login_message; ?>
                </div>
            <?php } ?>

            <form method="post">
                <input type="email" name="email" placeholder="Email Address" required autocomplete="email">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                <button type="submit" name="login">Login</button>
            </form>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
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
</body>
</html>