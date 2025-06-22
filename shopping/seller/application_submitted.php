<?php
session_start();
$message = "";
if (isset($_SESSION['application_status_message'])) {
    $message = $_SESSION['application_status_message'];
    unset($_SESSION['application_status_message']); // Clear message after displaying
} else {
    // If user somehow lands here without a session message, redirect home or to login
    header("Location: register.php"); // Or login.php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted | Makro Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; background-color: #f4f7f6; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header .logo img { height: 40px; margin-left: 20px; }
        .header-actions { margin-right: 20px; }
        .header-actions a { text-decoration: none; color: #333; padding: 8px 15px; border-radius: 5px; margin-left: 10px; }
        .header-actions .login-btn { border: 1px solid #ccc; }
        .header-actions .start-selling-btn { background-color: #ffcc00; color: #333; font-weight: 500; }

        .content-wrapper {
            flex-grow: 1; /* Allows content to take up available space */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .confirmation-card {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .confirmation-card h2 {
            color: #003366;
            margin-bottom: 20px;
            font-size: 2em;
        }
        .confirmation-card p {
            font-size: 1.1em;
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .confirmation-card .icon {
            font-size: 60px;
            color: #28a745; /* Green for success */
            margin-bottom: 25px;
        }
        .confirmation-card a.button {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .confirmation-card a.button:hover {
            background-color: #0056b3;
        }

        .footer {
            background-color: #333;
            color: #fff;
            padding: 20px 0;
            text-align: center;
            font-size: 0.8em;
            margin-top: auto; /* Pushes footer to the bottom */
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
            <a href="login.php" class="login-btn">Login</a>
            <a href="register.php" class="start-selling-btn">Start Selling</a>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="confirmation-card">
            <div class="icon">&#10003;</div> <h2>Application Submitted!</h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <p>We will review your application and get back to you soon. Please check your email for updates.</p>
            <a href="login_approved.php" class="button">Go to Login Page</a>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Marketeer. All Rights Reserved</p>
            <p>
                <a href="#">Terms of Use</a>
                <a href="#">Privacy Policy</a>
            </p>
        </div>
    </footer>
</body>
</html>