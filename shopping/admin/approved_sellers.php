<?php
session_start();
include("include/config.php"); // Path to your main config.php

// --- ADMIN AUTHENTICATION ---
// This check ensures only logged-in admins can access this page.
if (strlen($_SESSION['alogin']) == 0) {
    header("Location: index.php"); // Redirect to admin login page (assuming index.php is admin login)
    exit();
}

$message = '';

// Handle seller deletion POST request
if (isset($_POST['action']) && $_POST['action'] === 'delete_seller' && isset($_POST['seller_id'])) {
    $sellerIdToDelete = intval($_POST['seller_id']);

    // Start a transaction for atomicity: all or nothing
    mysqli_begin_transaction($con);

    try {
        // 1. Delete seller's products first.
        // This is important even with ON DELETE CASCADE for clarity and explicit control.
        // If other tables are linked to products (e.g., order_products), ensure they also cascade or are handled.
        $deleteProductsStmt = $con->prepare("DELETE FROM products WHERE seller_id = ?");
        if ($deleteProductsStmt === false) {
            throw new Exception("Error preparing product deletion statement: " . $con->error);
        }
        $deleteProductsStmt->bind_param("i", $sellerIdToDelete);
        if (!$deleteProductsStmt->execute()) {
            throw new Exception("Error deleting seller's products: " . $deleteProductsStmt->error);
        }
        $deleteProductsStmt->close();

        // 2. Delete the seller from the sellers table
        $deleteSellerStmt = $con->prepare("DELETE FROM sellers WHERE id = ?");
        if ($deleteSellerStmt === false) {
            throw new Exception("Error preparing seller deletion statement: " . $con->error);
        }
        $deleteSellerStmt->bind_param("i", $sellerIdToDelete);
        if ($deleteSellerStmt->execute()) {
            // Commit transaction if all deletions are successful
            mysqli_commit($con);
            $message = "Seller #{$sellerIdToDelete} and their associated products have been deleted successfully.";
        } else {
            // Rollback if seller deletion fails
            mysqli_rollback($con);
            throw new Exception("Error deleting seller: " . $deleteSellerStmt->error);
        }
        $deleteSellerStmt->close();

    } catch (Exception $e) {
        mysqli_rollback($con); // Ensure rollback on any exception
        $message = "Operation failed: " . $e->getMessage();
    }
}

// Fetch all APPROVED sellers
$approvedSellers = [];
// Assuming 'updated_at' column exists and is set when status changes to approved,
// otherwise use 'created_at' or add a dedicated 'approved_at' timestamp.
$query = mysqli_query($con, "SELECT * FROM sellers WHERE application_status = 'approved' ORDER BY created_at DESC");
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $approvedSellers[] = $row;
    }
} else {
    $message = "Error fetching approved sellers: " . mysqli_error($con);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Approved Sellers</title>
    <!-- Include your existing admin CSS/JS files -->
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
    <style>
        /* Basic styling (copied from seller_applications.php, adjust as needed) */
        body { font-family: sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container-main { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #003366; margin-bottom: 25px; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; color: #333; }
        tr.seller-row:nth-child(even) { background-color: #f9f9f9; }

        .actions button { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
        .actions .delete-btn { background-color: #dc3545; color: white; }
        .view-details-btn {
            background: #007bff;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .view-details-btn:hover { background: #0056b3; }
        .seller-details-container {
            display: none; /* Hidden by default */
            border-top: 1px dashed #eee;
            margin-top: 10px;
            padding-top: 10px;
            font-size: 0.9em;
            color: #666;
        }
        .seller-details-container.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Assuming you have an admin header and sidebar -->
    <?php include('include/header.php');?>

    <div class="wrapper">
        <div class="container">
            <div class="row">
                <?php include('include/sidebar.php');?>
                <div class="span9">
                    <div class="content">

                        <div class="module">
                            <div class="module-head">
                                <h3>Manage Approved Sellers</h3>
                            </div>
                            <div class="module-body table">

                                <?php if (!empty($message)) { ?>
                                    <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo $message; ?></div>
                                <?php } ?>

                                <?php if (empty($approvedSellers)): ?>
                                    <p>No approved sellers found at this time.</p>
                                <?php else: ?>
                                    <table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Shop Name</th>
                                                <th>Contact Person</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Approved On</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($approvedSellers as $seller): ?>
                                                <tr class="seller-row">
                                                    <td><?php echo htmlspecialchars($seller['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($seller['shop_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($seller['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($seller['application_status']); ?></td>
                                                    <td><?php echo htmlspecialchars($seller['created_at']); ?></td>
                                                    
                                                    <td class="actions">
                                                        <button class="view-details-btn" data-target="details-<?php echo $seller['id']; ?>">View Details</button>
                                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you absolutely sure you want to delete seller <?php echo htmlspecialchars($seller['full_name']); ?> (ID: <?php echo htmlspecialchars($seller['id']); ?>)? This action will permanently delete the seller AND ALL their products from the database!');">
                                                            <input type="hidden" name="seller_id" value="<?php echo htmlspecialchars($seller['id']); ?>">
                                                            <button type="submit" name="action" value="delete_seller" class="delete-btn">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="7">
                                                        <div id="details-<?php echo $seller['id']; ?>" class="seller-details-container">
                                                            <p><strong>Owner Mobile:</strong> <?php echo htmlspecialchars($seller['owner_mobile_number'] ?? 'N/A'); ?></p>
                                                            <p><strong>Company Legal Name:</strong> <?php echo htmlspecialchars($seller['company_legal_name'] ?? 'N/A'); ?></p>
                                                            <?php if (!empty($seller['sa_business_reg_number'])): ?>
                                                                <p><strong>SA Business Reg No:</strong> <?php echo htmlspecialchars($seller['sa_business_reg_number']); ?></p>
                                                            <?php endif; ?>
                                                            <?php if (!empty($seller['non_sa_business_reg_number'])): ?>
                                                                <p><strong>Non-SA Business Reg No:</strong> <?php echo htmlspecialchars($seller['non_sa_business_reg_number']); ?></p>
                                                            <?php endif; ?>
                                                            <?php if (!empty($seller['sa_id_number'])): ?>
                                                                <p><strong>SA ID No:</strong> <?php echo htmlspecialchars($seller['sa_id_number']); ?></p>
                                                            <?php endif; ?>
                                                            <?php if (!empty($seller['passport_number'])): ?>
                                                                <p><strong>Passport No:</strong> <?php echo htmlspecialchars($seller['passport_number']); ?></p>
                                                            <?php endif; ?>
                                                            <p><strong>Country:</strong> <?php echo htmlspecialchars($seller['country'] ?? 'N/A'); ?></p>
                                                            <?php if (!empty($seller['rejection_reason'])): ?>
                                                                <p style="color:red;"><strong>Previous Rejection Reason:</strong> <?php echo htmlspecialchars($seller['rejection_reason']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div><!--/.content-->
                </div><!--/.span9-->
            </div><!--/.row-->
        </div><!--/.container-->
    </div><!--/.wrapper-->

    <?php include('include/footer.php');?>

    <!-- Required JavaScript for Bootstrap and DataTables -->
    <script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
    <script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
    <script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="scripts/flot/jquery.flot.js" type="text/javascript"></script>
    <script src="scripts/datatables/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.datatable-1').dataTable();
            $('.dataTables_paginate').addClass("btn-group datatable-pagination");
            $('.dataTables_paginate > a').wrapInner('<span />');
            $('.dataTables_paginate > a:first-child').append('<i class="icon-chevron-left shaded"></i>');
            $('.dataTables_paginate > a:last-child').append('<i class="icon-chevron-right shaded"></i>');

            // JavaScript for View Details toggle
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const detailsContainer = document.getElementById(targetId);

                    if (detailsContainer) {
                        detailsContainer.classList.toggle('show');
                        this.textContent = detailsContainer.classList.contains('show') ? 'Hide Details' : 'View Details';
                    }
                });
            });
        });
    </script>
</body>
</html>
