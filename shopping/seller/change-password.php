<?php
session_start();
include('include/config.php'); // Assuming this path is correct from seller folder

// Check if seller is logged in
if (strlen($_SESSION['seller_login_status']) == 0 || $_SESSION['seller_login_status'] !== true) {
    // If not logged in, redirect to the seller login page
    header('location: login_approved.php'); // Assuming login_approved.php is your seller login page
    exit();
} else {
    // Seller is logged in, fetch their details for the dashboard
    $seller_id = $_SESSION['seller_id'];
    $seller_name = $_SESSION['seller_name'];
    $currentTime = date( 'Y-m-d H:i:s', time () ); // For any timestamp needs on dashboard

    // You can add logic here to fetch dashboard data (e.g., total products, pending orders)
    // For now, let's just display basic info.

    // Example: Fetch some data for the dashboard
    // You might want to fetch total products, total orders, etc.
    $total_products = 0;
    $total_orders = 0;

    // Example query (uncomment and adapt if you have product/order tables linked to seller_id)
    /*
    $stmt_products = $con->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_id = ?");
    if ($stmt_products) {
        $stmt_products->bind_param("i", $seller_id);
        $stmt_products->execute();
        $result_products = $stmt_products->get_result();
        $data_products = $result_products->fetch_assoc();
        $total_products = $data_products['total'];
        $stmt_products->close();
    }

    $stmt_orders = $con->prepare("SELECT COUNT(*) AS total FROM orders WHERE seller_id = ?"); // Assuming orders table links to seller_id
    if ($stmt_orders) {
        $stmt_orders->bind_param("i", $seller_id);
        $stmt_orders->execute();
        $result_orders = $stmt_orders->get_result();
        $data_orders = $result_orders->fetch_assoc();
        $total_orders = $data_orders['total'];
        $stmt_orders->close();
    }
    */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | Marketeer</title>
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
    <style>
        .dashboard-stats .module-body {
            display: flex;
            justify-content: space-around;
            padding: 20px;
        }
        .dashboard-stat-item {
            text-align: center;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            flex: 1;
            margin: 0 10px;
            background-color: #f9f9f9;
        }
        .dashboard-stat-item h4 {
            margin-bottom: 10px;
            color: #003366;
        }
        .dashboard-stat-item p {
            font-size: 2em;
            font-weight: bold;
            color: #ffcc00;
        }
    </style>
</head>
<body>
<?php include('include/header.php');?> <div class="wrapper">
        <div class="container">
            <div class="row">
<?php include('include/sidebar.php');?> <div class="span9">
                    <div class="content">

                        <div class="module">
                            <div class="module-head">
                                <h3>Welcome, <?php echo htmlentities($seller_name); ?>!</h3>
                            </div>
                            <div class="module-body">
                                <p>This is your Seller Dashboard. Here you can manage your products, view orders, and update your profile.</p>

                                <div class="dashboard-stats">
                                    <div class="dashboard-stat-item">
                                        <h4>Total Products</h4>
                                        <p><?php echo $total_products; ?></p>
                                    </div>
                                    <div class="dashboard-stat-item">
                                        <h4>Total Orders</h4>
                                        <p><?php echo $total_orders; ?></p>
                                    </div>
                                    </div>

                                <br />
                                <p>Quick Actions:</p>
                                <ul>
                                    <li><a href="manage-products.php">Manage Your Products</a></li>
                                    <li><a href="view-orders.php">View Your Orders</a></li>
                                    <li><a href="seller-profile.php">Update Your Profile</a></li>
                                    <li><a href="update-password.php">Change Your Password</a></li> </ul>

                                </div>
                        </div>

                    </div></div></div>
        </div></div><?php include('include/footer.php');?>

    <script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
    <script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
    <script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="scripts/flot/jquery.flot.js" type="text/javascript"></script>
</body>
<?php } // End of else block for session check ?>