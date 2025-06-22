<?php
session_start();
require_once 'includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['login']) || strlen($_SESSION['login']) == 0) {
    header('Location: login.php');
    exit();
}

// Fetch user info
$userId = $_SESSION['id'];
$stmt = $con->prepare("SELECT name, email, contactno, password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if (isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $contactno = trim($_POST['contactno']);

    $stmt = $con->prepare("UPDATE users SET name = ?, contactno = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $contactno, $userId);
    if ($stmt->execute()) {
        echo "<script>alert('Your info has been updated');</script>";
        $user['name'] = $name;
        $user['contactno'] = $contactno;
    }
    $stmt->close();
}

// Handle password change
if (isset($_POST['submit'])) {
    $currentPass = $_POST['cpass'];
    $newPass = $_POST['newpass'];
    $confirmPass = $_POST['cnfpass'];

    if (!password_verify($currentPass, $user['password'])) {
        echo "<script>alert('Current password does not match.');</script>";
    } elseif ($newPass !== $confirmPass) {
        echo "<script>alert('New password and confirm password do not match.');</script>";
    } else {
        $newHashedPass = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $con->prepare("UPDATE users SET password = ?, updationDate = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newHashedPass, $userId);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Password changed successfully.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSS dependencies -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="cnt-home">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>

<div class="breadcrumb">
    <div class="container">
        <ul class="list-inline list-unstyled">
            <li><a href="#">Home</a></li>
            <li class="active">My Account</li>
        </ul>
    </div>
</div>

<div class="body-content outer-top-bd">
    <div class="container">
        <div class="checkout-box inner-bottom-sm">
            <div class="row">
                <div class="col-md-8">
                    <div class="panel-group checkout-steps" id="accordion">
                        <!-- Profile Info -->
                        <div class="panel panel-default checkout-step-01">
                            <div class="panel-heading">
                                <h4 class="unicase-checkout-title">
                                    <a data-toggle="collapse" class="" data-parent="#accordion" href="#collapseOne">
                                        <span>1</span>My Profile
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseOne" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label>Name <span>*</span></label>
                                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Email <span>*</span></label>
                                            <input type="email" class="form-control" readonly value="<?= htmlspecialchars($user['email']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Contact No. <span>*</span></label>
                                            <input type="text" name="contactno" class="form-control" required maxlength="10" value="<?= htmlspecialchars($user['contactno']) ?>">
                                        </div>
                                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="panel panel-default checkout-step-02">
                            <div class="panel-heading">
                                <h4 class="unicase-checkout-title">
                                    <a data-toggle="collapse" class="collapsed" data-parent="#accordion" href="#collapseTwo">
                                        <span>2</span>Change Password
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseTwo" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <form method="post" name="chngpwd" onsubmit="return validatePasswordForm();">
                                        <div class="form-group">
                                            <label>Current Password <span>*</span></label>
                                            <input type="password" name="cpass" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>New Password <span>*</span></label>
                                            <input type="password" name="newpass" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Confirm Password <span>*</span></label>
                                            <input type="password" name="cnfpass" class="form-control" required>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include('includes/myaccount-sidebar.php'); ?>
            </div>
        </div>
        <?php include('includes/brands-slider.php'); ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script>
function validatePasswordForm() {
    const newpass = document.chngpwd.newpass.value;
    const cnfpass = document.chngpwd.cnfpass.value;
    if (newpass !== cnfpass) {
        alert("Password and confirm password do not match!");
        return false;
    }
    return true;
}
</script>
</body>
</html>
