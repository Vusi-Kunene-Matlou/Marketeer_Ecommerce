<?php
session_start();
// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database configuration
include('includes/config.php');

// Check if $con is an active MySQLi connection
if (!isset($con) || $con->connect_error) {
    die("Database connection failed: " . (isset($con) ? $con->connect_error : "Connection object not set."));
}

// Initialize variables to hold validation errors and old input values
$form_validation_errors = [];
$form_input_values = [];

// Retrieve any server-side validation errors or old input values from session
if (isset($_SESSION['form_validation_errors'])) {
    $form_validation_errors = $_SESSION['form_validation_errors'];
    unset($_SESSION['form_validation_errors']); // Clear after retrieving
}
if (isset($_SESSION['form_input_values'])) {
    $form_input_values = $_SESSION['form_input_values'];
    unset($_SESSION['form_input_values']); // Clear after retrieving
}

// Code for User Registration
if (isset($_POST['submit'])) {
    $name = trim($_POST['fullname']);
    $email = trim($_POST['emailid']);
    $contactno = trim($_POST['contactno']);
    $rawPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmpassword'];

    // --- Server-Side Input Validation ---
    // Use $field_errors to map errors to specific input IDs
    $field_errors_on_submit = [];

    // Full Name: Basic check for not empty
    if (empty($name)) {
        $field_errors_on_submit['fullname'] = "Full Name cannot be empty.";
    }

    // Email Validation: Using filter_var for standard email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors_on_submit['emailid'] = "Invalid email format. Please use a valid email address (e.g., user@example.com).";
    }

    // South African Contact Number Validation: Starts with '0', followed by 9 digits
    if (!preg_match('/^0[0-9]{9}$/', $contactno)) {
        $field_errors_on_submit['contactno'] = "Invalid South African contact number. Must start with '0' and be 10 digits (e.g., 0821234567).";
    }

    // Password Validation: Modern standards
    $password_issues = [];
    if (strlen($rawPassword) < 8) {
        $password_issues[] = "Min 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $rawPassword)) {
        $password_issues[] = "One uppercase.";
    }
    if (!preg_match('/[a-z]/', $rawPassword)) {
        $password_issues[] = "One lowercase.";
    }
    if (!preg_match('/[0-9]/', $rawPassword)) {
        $password_issues[] = "One number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $rawPassword)) {
        $password_issues[] = "One special character.";
    }
    if ($rawPassword !== $confirmPassword) {
        $password_issues[] = "Passwords do not match.";
        $field_errors_on_submit['confirmpassword'] = "Passwords do not match."; // Specific error for confirm field
    }

    if (!empty($password_issues)) {
        $field_errors_on_submit['password'] = implode(" ", $password_issues);
    }


    // Check if email already exists using a prepared statement (only if email format is valid)
    if (!isset($field_errors_on_submit['emailid'])) { // Only check availability if format is good
        $stmt_check_email = $con->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt_check_email === false) {
             $field_errors_on_submit['emailid'] = "Database error (email check).";
             error_log("Email check prepare error: " . $con->error);
        } else {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $field_errors_on_submit['emailid'] = "Email address already registered.";
            }
            $stmt_check_email->close();
        }
    }


    if (empty($field_errors_on_submit)) {
        // If no server-side validation errors, proceed with registration
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

        $stmt = $con->prepare("INSERT INTO users(name, email, contactno, password) VALUES(?, ?, ?, ?)");
        if ($stmt === false) {
            $_SESSION['errmsg'] = "Database error (registration). Please try again.";
            error_log("Registration prepare error: " . $con->error);
        } else {
            $stmt->bind_param("ssss", $name, $email, $contactno, $hashedPassword);
            if ($stmt->execute()) {
                // Successful registration
                echo "<script>alert('You are successfully registered!');</script>";
                // Clear any lingering session errors/inputs
                unset($_SESSION['form_validation_errors']);
                unset($_SESSION['form_input_values']);
                // Redirect to login or success page
                // header('location:login.php');
                // exit();
            } else {
                $_SESSION['errmsg'] = "Registration failed: " . htmlspecialchars($stmt->error);
                error_log("Registration execute error: " . $stmt->error);
            }
            $stmt->close();
        }
    } else {
        // Store field-specific errors and input values in session
        $_SESSION['form_validation_errors'] = $field_errors_on_submit;
        $_SESSION['form_input_values'] = $_POST;
        // Redirect back to login page to display errors
        header("location:login.php");
        exit();
    }
}

// Code for User Login (remains largely the same, but still uses $_SESSION['errmsg'] for simplicity here)
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $rawPassword = $_POST['password'];

    $uip = $_SERVER['REMOTE_ADDR'];
    $status = 0; // Default status for failed login

    $stmt = $con->prepare("SELECT id, name, password FROM users WHERE email = ?");
    if ($stmt === false) {
        $_SESSION['errmsg'] = "Database error (login). Please try again.";
        error_log("Login DB Prepare Error: " . $con->error);
        header("location:login.php");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        if (password_verify($rawPassword, $user['password'])) {
            $_SESSION['login'] = $email;
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $status = 1;

            $log_stmt = $con->prepare("INSERT INTO userlog(userEmail, userip, status) VALUES(?, ?, ?)");
            if ($log_stmt === false) {
                 error_log("Login log prepare error: " . $con->error);
            } else {
                $log_stmt->bind_param("ssi", $email, $uip, $status);
                $log_stmt->execute();
                $log_stmt->close();
            }

            header("location:my-cart.php");
            exit();
        } else {
            $_SESSION['errmsg'] = "Invalid email or password.";
            $status = 0;
        }
    } else {
        $_SESSION['errmsg'] = "Invalid email or password.";
        $status = 0;
    }

    $log_stmt = $con->prepare("INSERT INTO userlog(userEmail, userip, status) VALUES(?, ?, ?)");
    if ($log_stmt === false) {
        error_log("Login log prepare error for failed attempt: " . $con->error);
    } else {
        $log_stmt->bind_param("ssi", $email, $uip, $status);
        $log_stmt->execute();
        $log_stmt->close();
    }

    header("location:login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <meta name="keywords" content="MediaCenter, Template, eCommerce">
        <meta name="robots" content="all">

        <title>Marketeer | Sign-in | Sign-up</title>

        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="assets/css/main.css">
        <link rel="stylesheet" href="assets/css/red.css">
        <link rel="stylesheet" href="assets/css/owl.carousel.css">
        <link rel="stylesheet" href="assets/css/owl.transitions.css">
        <link href="assets/css/lightbox.css" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/animate.min.css">
        <link rel="stylesheet" href="assets/css/rateit.css">
        <link rel="stylesheet" href="assets/css/bootstrap-select.min.css">
        <link rel="stylesheet" href="assets/css/config.css">
        <link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
        <link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
        <link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
        <link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
        <link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
        <link rel="stylesheet" href="assets/css/font-awesome.min.css">
        <link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <style>
            /* Basic styling for error messages */
            .error-message {
                color: red;
                font-size: 0.9em;
                margin-top: 5px; /* Adjust as needed for spacing */
                display: block; /* Ensures it takes its own line */
            }
        </style>

        <script type="text/javascript">
        function valid() {
            var isValid = true; // Flag to track overall form validity

            // Function to display error message
            function displayError(id, message) {
                document.getElementById(id + '-error').innerHTML = message;
                document.getElementById(id).classList.add('is-invalid'); // Add class for styling invalid fields
                isValid = false;
            }

            // Function to clear error message
            function clearError(id) {
                document.getElementById(id + '-error').innerHTML = '';
                document.getElementById(id).classList.remove('is-invalid'); // Remove invalid class
            }

            // Clear all previous errors at the beginning
            clearError('fullname');
            clearError('emailid');
            clearError('contactno');
            clearError('password');
            clearError('confirmpassword');


            var fullname = document.register.fullname.value;
            var email = document.register.emailid.value;
            var contactno = document.register.contactno.value;
            var password = document.register.password.value;
            var confirmPassword = document.register.confirmpassword.value;

            // Full Name Validation
            if (fullname.trim() === "") {
                displayError('fullname', 'Full Name cannot be empty.');
            }

            // Email format validation (client-side)
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                displayError('emailid', 'Invalid email format (e.g., user@example.com).');
            }

            // South African Contact Number Validation (Client-side)
            if (!/^0[0-9]{9}$/.test(contactno)) {
                displayError('contactno', 'Invalid SA number. Must be 10 digits starting with 0 (e.g., 0821234567).');
            }

            // Password complexity check
            var passwordErrors = [];
            if (password.length < 8) {
                passwordErrors.push("Min 8 characters.");
            }
            if (!/[A-Z]/.test(password)) {
                passwordErrors.push("One uppercase.");
            }
            if (!/[a-z]/.test(password)) {
                passwordErrors.push("One lowercase.");
            }
            if (!/[0-9]/.test(password)) {
                passwordErrors.push("One number.");
            }
            if (!/[^A-Za-z0-9]/.test(password)) { // Check for at least one special character
                passwordErrors.push("One special character.");
            }

            if (passwordErrors.length > 0) {
                displayError('password', 'Password: ' + passwordErrors.join(" "));
            }

            // Password match check (after complexity check to avoid double messages if complexity fails)
            if (password !== confirmPassword) {
                displayError('confirmpassword', 'Passwords do not match.');
                // Optionally, also highlight the password field if it doesn't match confirm
                if (passwordErrors.length === 0) { // Only if no other password errors
                    document.getElementById('password').classList.add('is-invalid');
                }
            }


            return isValid; // Return the overall validity flag
        }
        </script>
        <script>
        function userAvailability() {
            $("#loaderIcon").show();
            jQuery.ajax({
                url: "check_availability.php", // Assuming this file exists and is in the same directory
                data:'email='+$("#email").val(),
                type: "POST",
                success:function(data){
                    // This updates the span with ID user-availability-status1
                    $("#user-availability-status1").html(data);
                    $("#loaderIcon").hide();
                },
                error:function (){}
            });
        }
        </script>

    </head>
    <body class="cnt-home">

        <header class="header-style-1">
            <?php include('includes/top-header.php');?>
            <?php include('includes/main-header.php');?>
            <?php include('includes/menu-bar.php');?>
        </header>

        <div class="breadcrumb">
            <div class="container">
                <div class="breadcrumb-inner">
                    <ul class="list-inline list-unstyled">
                        <li><a href="home.html">Home</a></li>
                        <li class='active'>Authentication</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="body-content outer-top-bd">
            <div class="container">
                <div class="sign-in-page inner-bottom-sm">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 sign-in">
                            <h4 class="">Sign In</h4>
                            <p class="">Hello, Welcome to your account.</p>
                            <form class="register-form outer-top-xs" method="post">
                                <?php if(isset($_SESSION['errmsg']) && $_SESSION['errmsg'] != '') { ?>
                                    <span style="color:red;" ><?php echo htmlentities($_SESSION['errmsg']); ?></span>
                                    <?php unset($_SESSION['errmsg']); // Unset after displaying ?>
                                <?php } ?>
                                <div class="form-group">
                                    <label class="info-title" for="exampleInputEmail1">Email Address <span>*</span></label>
                                    <input type="email" name="email" class="form-control unicase-form-control text-input" id="exampleInputEmail1" required>
                                </div>
                                <div class="form-group">
                                    <label class="info-title" for="exampleInputPassword1">Password <span>*</span></label>
                                    <input type="password" name="password" class="form-control unicase-form-control text-input" id="exampleInputPassword1" required>
                                </div>
                                <div class="radio outer-xs">
                                    <a href="forgot-password.php" class="forgot-password pull-right">Forgot your Password?</a>
                                </div>
                                <button type="submit" class="btn-upper btn btn-primary checkout-page-button" name="login">Login</button>
                            </form>
                        </div>
                        <div class="col-md-6 col-sm-6 create-new-account">
                            <h4 class="checkout-subtitle">Create a New Account</h4>
                            <p class="text title-tag-line">Create your own Shopping account.</p>
                            <form class="register-form outer-top-xs" role="form" method="post" name="register" onSubmit="return valid();">

                                <div class="form-group">
                                    <label class="info-title" for="fullname">Full Name <span>*</span></label>
                                    <input type="text" class="form-control unicase-form-control text-input" id="fullname" name="fullname" required="required" value="<?php echo htmlspecialchars($form_input_values['fullname'] ?? ''); ?>">
                                    <span class="error-message" id="fullname-error">
                                        <?php echo htmlspecialchars($form_validation_errors['fullname'] ?? ''); ?>
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label class="info-title" for="emailid">Email Address <span>*</span></label>
                                    <input type="email" class="form-control unicase-form-control text-input" id="emailid" onBlur="userAvailability()" name="emailid" required value="<?php echo htmlspecialchars($form_input_values['emailid'] ?? ''); ?>">
                                    <span id="user-availability-status1" style="font-size:12px;">
                                        <?php echo htmlspecialchars($form_validation_errors['emailid'] ?? ''); ?>
                                    </span>
                                    <span class="error-message" id="emailid-error"></span>
                                </div>

                                <div class="form-group">
                                    <label class="info-title" for="contactno">Contact No. <span>*</span></label>
                                    <input type="text" class="form-control unicase-form-control text-input" id="contactno" name="contactno" required value="<?php echo htmlspecialchars($form_input_values['contactno'] ?? ''); ?>">
                                    <span class="error-message" id="contactno-error">
                                        <?php echo htmlspecialchars($form_validation_errors['contactno'] ?? ''); ?>
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label class="info-title" for="password">Password. <span>*</span></label>
                                    <input type="password" class="form-control unicase-form-control text-input" id="password" name="password" required >
                                    <span class="error-message" id="password-error">
                                        <?php echo htmlspecialchars($form_validation_errors['password'] ?? ''); ?>
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label class="info-title" for="confirmpassword">Confirm Password. <span>*</span></label>
                                    <input type="password" class="form-control unicase-form-control text-input" id="confirmpassword" name="confirmpassword" required >
                                    <span class="error-message" id="confirmpassword-error">
                                        <?php echo htmlspecialchars($form_validation_errors['confirmpassword'] ?? ''); ?>
                                    </span>
                                </div>

                                <button type="submit" name="submit" class="btn-upper btn btn-primary checkout-page-button" id="submit">Sign Up</button>
                            </form>

                            <span class="checkout-subtitle outer-top-xs">Sign Up Today And You'll Be Able To : </span>
                            <div class="checkbox">
                                <label class="checkbox">
                                    Speed your way through the checkout.
                                </label>
                                <label class="checkbox">
                                    Track your orders easily.
                                </label>
                                <label class="checkbox">
                                    Keep a record of all your purchases.
                                </label>
                            </div>
                        </div>
                        </div></div>
                <?php include('includes/brands-slider.php');?>
            </div>
        </div>
        <?php include('includes/footer.php');?>
        <script src="assets/js/jquery-1.11.1.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/bootstrap-hover-dropdown.min.js"></script>
        <script src="assets/js/owl.carousel.min.js"></script>
        <script src="assets/js/echo.min.js"></script>
        <script src="assets/js/jquery.easing-1.3.min.js"></script>
        <script src="assets/js/bootstrap-slider.min.js"></script>
        <script src="assets/js/jquery.rateit.min.js"></script>
        <script type="text/javascript" src="assets/js/lightbox.min.js"></script>
        <script src="assets/js/bootstrap-select.min.js"></script>
        <script src="assets/js/wow.min.js"></script>
        <script src="assets/js/scripts.js"></script>
        <script src="switchstylesheet/switchstylesheet.js"></script>

        <script>
            $(document).ready(function(){
                $(".changecolor").switchstylesheet( { seperator:"color"} );
                $('.show-theme-options').click(function(){
                    $(this).parent().toggleClass('open');
                    return false;
                });

                // Add an event listener to clear error messages on input focus
                $('input.text-input').on('focus', function() {
                    var inputId = $(this).attr('id');
                    if (inputId) {
                        $('#' + inputId + '-error').text(''); // Clear the corresponding error span
                        $(this).removeClass('is-invalid'); // Remove validation styling
                    }
                });
            });

            $(window).bind("load", function() {
                $('.show-theme-options').delay(2000).trigger('click');
            });
        </script>
    </body>
</html>