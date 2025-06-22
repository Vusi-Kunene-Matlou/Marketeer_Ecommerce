<?php
session_start();
include('include/config.php');

// Set the default timezone for date functions.
// Change according to your actual timezone (e.g., 'Africa/Johannesburg' for SA)
date_default_timezone_set('Africa/Johannesburg'); // Changed from Asia/Kolkata
$currentTime = date('d-m-Y h:i:s A', time());

// IMPORTANT CHANGE: Check if the seller is logged in using $_SESSION['seller_id']
if (!isset($_SESSION['seller_id']) || strlen($_SESSION['seller_id']) == 0) {
    header('location:index.php'); // Redirect to seller login page
    exit(); // Always exit after a header redirect
} else {
    // Get the ID of the logged-in seller from the correct session variable
    $sellerid = $_SESSION['seller_id'];

    // Process product deletion request
    if (isset($_GET['del'])) {
        // Sanitize the product ID from the GET request
        $product_id_to_delete = intval($_GET['id']);

        // IMPORTANT SECURITY: Ensure the seller can only delete their own products.
        // The delete query must include the seller_id filter.
        $delete_query = mysqli_query($con, "DELETE FROM products WHERE id = '$product_id_to_delete' AND seller_id = '$sellerid'");

        if ($delete_query) {
            $_SESSION['delmsg'] = "Product deleted successfully!";
        } else {
            $_SESSION['delmsg'] = "Error deleting product or product not found/owned by you. " . mysqli_error($con);
        }
        // Redirect to the same page to prevent re-deletion on refresh
        header('location:manage-products.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller | Manage Products</title> <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
</head>
<body>
<?php include('include/header.php');?> <div class="wrapper">
        <div class="container">
            <div class="row">
<?php include('include/sidebar.php');?> <div class="span9">
                    <div class="content">

                        <div class="module">
                            <div class="module-head">
                                <h3>Manage My Products</h3> </div>
                            <div class="module-body table">
                                <?php if(isset($_SESSION['delmsg'])) { ?>
                                <div class="alert alert-success">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <strong><?php echo strpos($_SESSION['delmsg'], 'Error') !== false ? 'Oh snap!' : 'Well done!'; ?></strong>
                                    <?php echo htmlentities($_SESSION['delmsg']); ?>
                                    <?php unset($_SESSION['delmsg']); // Clear the message after displaying ?>
                                </div>
                                <?php } ?>

                                <br />

                                <table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Subcategory</th>
                                            <th>Company Name</th>
                                            <th>Product Creation Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    // Query to fetch products belonging ONLY to the logged-in seller
                                    $query = mysqli_query($con, "
                                        SELECT p.*, c.categoryName, s.subcategory
                                        FROM products p
                                        JOIN category c ON c.id = p.category
                                        JOIN subcategory s ON s.id = p.subCategory
                                        WHERE p.seller_id = '$sellerid' -- FILTER BY THE SELLER'S ID
                                        ORDER BY p.id DESC
                                    ");

                                    // Check if the query was successful
                                    if (!$query) {
                                        echo "<tr><td colspan='7'>Database query failed: " . mysqli_error($con) . "</td></tr>";
                                    } else if (mysqli_num_rows($query) == 0) {
                                        echo "<tr><td colspan='7'>No products found for this seller.</td></tr>";
                                    } else {
                                        $cnt = 1; // Counter for numbering rows
                                        while ($row = mysqli_fetch_array($query)) {
                                    ?>
                                        <tr>
                                            <td><?php echo htmlentities($cnt);?></td>
                                            <td><?php echo htmlentities($row['productName']);?></td>
                                            <td><?php echo htmlentities($row['categoryName']);?></td>
                                            <td><?php echo htmlentities($row['subcategory']);?></td>
                                            <td><?php echo htmlentities($row['productCompany']);?></td>
                                            <td><?php echo htmlentities($row['postingDate']);?></td>
                                            <td>
                                                <a href="edit-products.php?id=<?php echo $row['id']?>" title="Edit Product"><i class="icon-edit"></i></a>
                                                <a href="manage-products.php?id=<?php echo $row['id']?>&del=delete" onClick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')" title="Delete Product"><i class="icon-remove-sign"></i></a>
                                            </td>
                                        </tr>
                                    <?php
                                            $cnt = $cnt + 1;
                                        }
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div></div></div></div></div><?php include('include/footer.php');?> <script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
    <script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
    <script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="scripts/flot/jquery.flot.js" type="text/javascript"></script>
    <script src="scripts/datatables/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable-1').dataTable();
            $('.dataTables_paginate').addClass("btn-group datatable-pagination");
            $('.dataTables_paginate > a').wrapInner('<span />');
            $('.dataTables_paginate > a:first-child').append('<i class="icon-chevron-left shaded"></i>');
            $('.dataTables_paginate > a:last-child').append('<i class="icon-chevron-right shaded"></i>');
        });
    </script>
</body>
</html>
<?php } // Closes the main 'else' block for session check ?>