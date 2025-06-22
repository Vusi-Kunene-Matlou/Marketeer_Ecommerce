<?php
session_start();
include("include/config.php");

// Check if step 1 was completed (optional but recommended for multi-step forms)
if (!isset($_SESSION['registration_step_1_complete']) || !$_SESSION['registration_step_1_complete']) {
    header("Location: register.php");
    exit();
}

$message = ''; // Initialize message for potential errors

if (isset($_POST['submit_business_details'])) {
    $seller_id = $_SESSION['seller_id'];
    // $user_id = $_SESSION['user_id']; // Assuming you passed user_id if you use Option 1 setup

    // Capture all form data
    $businessType = $_POST['business_type'];
    $ownerFirstName = $_POST['owner_first_name'];
    $ownerLastName = $_POST['owner_last_name'];
    $ownerEmail = $_POST['owner_email'];
    $ownerMobile = $_POST['owner_mobile_number'];
    $companyLegalName = $_POST['company_legal_name'];
    $saBusinessRegNumber = isset($_POST['sa_business_reg_number']) ? $_POST['sa_business_reg_number'] : NULL;
    $nonSaBusinessRegNumber = isset($_POST['non_sa_business_reg_number']) ? $_POST['non_sa_business_reg_number'] : NULL;
    $saIdNumber = isset($_POST['sa_id_number']) ? $_POST['sa_id_number'] : NULL;
    $passportNumber = isset($_POST['passport_number']) ? $_POST['passport_number'] : NULL;
    $country = $_POST['country'];

    // Update the sellers table with business details and set status to 'pending'
    $stmt = $con->prepare("UPDATE sellers SET
        business_type = ?,
        owner_first_name = ?,
        owner_last_name = ?,
        owner_email = ?,
        owner_mobile_number = ?,
        company_legal_name = ?,
        sa_business_reg_number = ?,
        non_sa_business_reg_number = ?,
        sa_id_number = ?,
        passport_number = ?,
        country = ?,
        application_status = 'pending' -- Set status to pending here
        WHERE id = ?");

    $stmt->bind_param("sssssssssssi",
        $businessType,
        $ownerFirstName,
        $ownerLastName,
        $ownerEmail,
        $ownerMobile,
        $companyLegalName,
        $saBusinessRegNumber,
        $nonSaBusinessRegNumber,
        $saIdNumber,
        $passportNumber,
        $country,
        $seller_id
    );

    if ($stmt->execute()) {
        // Store success message in session for the next page
        $_SESSION['application_status_message'] = "Your application has been submitted successfully and is now awaiting admin approval.";

        // Clear other registration session data
        unset($_SESSION['registration_step_1_complete']);
        unset($_SESSION['seller_id']);
        // unset($_SESSION['user_id']); // If you stored user_id in session for this flow

        // Redirect to a confirmation page
        header("Location: application_submitted.php");
        exit();
    } else {
        $message = "Failed to submit business details. Please try again. Error: " . $stmt->error;
    }
    $stmt->close();
}
// ... (rest of your HTML and JavaScript for business_details.php) ...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Details | Makro Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Re-use general styles from register.php for consistency */
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; background-color: #f4f7f6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header .logo img { height: 40px; margin-left: 20px; }
        .header-actions { margin-right: 20px; }
        .header-actions a { text-decoration: none; color: #333; padding: 8px 15px; border-radius: 5px; margin-left: 10px; }
        .header-actions .login-btn { border: 1px solid #ccc; }
        .header-actions .start-selling-btn { background-color: #ffcc00; color: #333; font-weight: 500; }

        .main-content {
            display: flex;
            justify-content: center; /* Center the single card */
            padding: 50px 0;
        }
        .business-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 700px; /* Adjust width as needed */
            max-width: 90%;
        }
        .business-card h2 {
            color: #003366;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        .business-card h2 .icon {
            font-size: 24px;
            margin-right: 10px;
            color: #ffcc00;
        }
        .business-card .input-group {
            margin-bottom: 20px;
        }
        .business-card .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .business-card .input-group input[type="text"],
        .business-card .input-group input[type="email"],
        .business-card .input-group select,
        .business-card .input-group .half-width-input {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .business-card .input-group .half-width-input {
            width: calc(50% - 15px); /* Adjusted for spacing */
            display: inline-block;
            margin-right: 10px;
        }
        .business-card .input-group .half-width-input:last-child {
            margin-right: 0;
        }
        .business-card .radio-group {
            margin-bottom: 25px;
        }
        .business-card .radio-group label {
            display: block;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .business-card .radio-group label:hover {
            background-color: #f0f0f0;
        }
        .business-card .radio-group input[type="radio"] {
            margin-right: 10px;
        }
        .business-card .radio-group input[type="radio"]:checked + label {
            background-color: #e6f7ff;
            border-color: #007bff;
        }

        .business-card .section-heading {
            font-size: 1.1em;
            font-weight: 700;
            color: #003366;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .business-card button {
            background-color: #007bff;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .business-card button:hover {
            background-color: #0056b3;
        }
        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .breadcrumbs {
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .breadcrumbs a {
            color: #007bff;
            text-decoration: none;
        }
        .breadcrumbs span {
            color: #666;
            font-weight: 500;
        }
        /* Icon placeholders for consistency */
        .icon-briefcase::before { content: '💼'; }
        .icon-flag::before { content: '🇿🇦'; } /* South Africa flag placeholder */

        .hidden-field {
            display: none;
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

    <div class="container main-content">
        <div class="business-card">
            <div class="breadcrumbs">
                <a href="register.php">Personal Details</a> &gt;
                <span>Business Details</span>
            </div>
            <h2><span class="icon-briefcase"></span> Business Details</h2>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo strpos($message, 'successful') !== false ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
            <?php } ?>

            <form method="post">
                <div class="radio-group">
                    <p class="section-heading">What type of business are you <span style="color:red;">*</span></p>
                    <input type="radio" id="sole_proprietor" name="business_type" value="Individual (Sole Proprietor)" required onchange="toggleBusinessFields()">
                    <label for="sole_proprietor">An individual (Sole Proprietor)</label>

                    <input type="radio" id="sa_registered" name="business_type" value="A South African Registered Business" onchange="toggleBusinessFields()">
                    <label for="sa_registered">A South African Registered Business</label>

                    <input type="radio" id="international_seller" name="business_type" value="An International Seller (Non-SA Business)" onchange="toggleBusinessFields()">
                    <label for="international_seller">An International Seller (Non-SA Business)</label>
                </div>

                <p class="section-heading">Directors details required for account creation</p>
                <div class="input-group">
                    <label>Business owner name<span style="color:red;">*</span></label>
                    <input type="text" name="owner_first_name" class="half-width-input" placeholder="First Name" required>
                    <input type="text" name="owner_last_name" class="half-width-input" placeholder="Last Name" required>
                </div>
                <div class="input-group">
                    <label for="owner_email">Business owner email<span style="color:red;">*</span></label>
                    <input type="email" id="owner_email" name="owner_email" placeholder="If successful, will be used for creating your Takealot Seller Account" required>
                </div>
                <div class="input-group">
                    <label for="owner_mobile_number">Business owner mobile number<span style="color:red;">*</span></label>
                    <input type="text" id="owner_mobile_number" name="owner_mobile_number" placeholder="If successful, mobile number is used to verify details and access your Takealot Seller Account" required>
                </div>

                <p class="section-heading">Tell us about your business</p>
                <div class="input-group">
                    <label for="company_legal_name">Company legal name<span style="color:red;">*</span></label>
                    <input type="text" id="company_legal_name" name="company_legal_name" required>
                </div>

                <div class="input-group" id="sa_reg_number_field">
                    <label for="sa_business_reg_number">South African business registration number<span style="color:red;">*</span></label>
                    <input type="text" id="sa_business_reg_number" name="sa_business_reg_number" placeholder="Example 2024/123456/07">
                </div>

                <div class="input-group hidden-field" id="non_sa_reg_number_field">
                    <label for="non_sa_business_reg_number">Non-SA business registration number<span style="color:red;">*</span></label>
                    <input type="text" id="non_sa_business_reg_number" name="non_sa_business_reg_number">
                </div>

                <div class="input-group hidden-field" id="sa_id_number_field">
                    <label for="sa_id_number">SA ID number<span style="color:red;">*</span></label>
                    <small>Required if applying as Sole Proprietor</small>
                    <input type="text" id="sa_id_number" name="sa_id_number">
                </div>

                <div class="input-group hidden-field" id="passport_number_field">
                    <label for="passport_number">Passport number<span style="color:red;">*</span></label>
                    <small>Required if applying as Sole Proprietor</small>
                    <input type="text" id="passport_number" name="passport_number">
                </div>

                <div class="input-group">
                    <label for="country">Country<span style="color:red;">*</span></label>
                    <small>Country of business registration OR Nationality</small>
                    <select id="country" name="country" required>
                        <option value="">Please select</option>
                        <option value="South Africa">South Africa</option>
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Australia">Australia</option>
                        </select>
                </div>

                <button type="submit" name="submit_business_details">Submit Application</button>
            </form>
        </div>
    </div>

    <section class="footer">
        <div class="container">
            <p>&copy; 2024 Massmart. All Rights Reserved</p>
            <p>
                <a href="#">Terms of Use</a>
                <a href="#">Privacy Policy</a>
            </p>
        </div>
    </section>

    <script>
        // JavaScript for conditional display of fields based on business type
        function toggleBusinessFields() {
            const soleProprietor = document.getElementById('sole_proprietor');
            const saRegistered = document.getElementById('sa_registered');
            const internationalSeller = document.getElementById('international_seller');

            const saRegNumberField = document.getElementById('sa_reg_number_field');
            const nonSaRegNumberField = document.getElementById('non_sa_reg_number_field');
            const saIdNumberField = document.getElementById('sa_id_number_field');
            const passportNumberField = document.getElementById('passport_number_field');

            // Hide all by default
            saRegNumberField.classList.add('hidden-field');
            nonSaRegNumberField.classList.add('hidden-field');
            saIdNumberField.classList.add('hidden-field');
            passportNumberField.classList.add('hidden-field');

            // Remove 'required' attribute when hidden
            saRegNumberField.querySelector('input').removeAttribute('required');
            nonSaRegNumberField.querySelector('input').removeAttribute('required');
            saIdNumberField.querySelector('input').removeAttribute('required');
            passportNumberField.querySelector('input').removeAttribute('required');

            if (saRegistered.checked) {
                saRegNumberField.classList.remove('hidden-field');
                saRegNumberField.querySelector('input').setAttribute('required', 'required');
            } else if (internationalSeller.checked) {
                nonSaRegNumberField.classList.remove('hidden-field');
                nonSaRegNumberField.querySelector('input').setAttribute('required', 'required');
            } else if (soleProprietor.checked) {
                saIdNumberField.classList.remove('hidden-field');
                passportNumberField.classList.remove('hidden-field');
                // A sole proprietor might have either SA ID or Passport, not both required.
                // You might add logic here to make at least one of them required.
                // For now, let's keep them optional unless user selects one.
                saIdNumberField.querySelector('input').setAttribute('required', 'required');
                passportNumberField.querySelector('input').setAttribute('required', 'required');
            }
        }

        // Run on page load to set initial state
        document.addEventListener('DOMContentLoaded', toggleBusinessFields);
    </script>
</body>
</html>