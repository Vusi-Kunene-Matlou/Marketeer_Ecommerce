<?php
session_start();
include("include/config.php"); // Corrected path based on your last successful fix

// IMPORTANT: Implement robust admin authentication here!
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'superadmin') {
//     header("Location: admin_login.php");
//     exit();
// }

$message = '';

// Handle approval/rejection POST requests
if (isset($_POST['action']) && isset($_POST['seller_id'])) {
    $sellerId = $_POST['seller_id'];
    $newStatus = '';
    $reason = isset($_POST['reason']) ? $_POST['reason'] : NULL; // For rejection reason

    if ($_POST['action'] === 'approve') {
        $newStatus = 'approved';
    } elseif ($_POST['action'] === 'reject') {
        $newStatus = 'rejected';
    }

    if ($newStatus) {
        $stmt = $con->prepare("UPDATE sellers SET application_status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $reason, $sellerId);
        if ($stmt->execute()) {
            $message = "Seller application #{$sellerId} has been {$newStatus}.";
            // TODO: Send email notification to seller (Approved/Rejected)
        } else {
            $message = "Error updating status: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all seller applications (e.g., pending ones)
$sellerApplications = [];
$query = mysqli_query($con, "SELECT * FROM sellers WHERE application_status = 'pending' ORDER BY created_at DESC");
// Or for all: $query = mysqli_query($con, "SELECT * FROM sellers ORDER BY created_at DESC");

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $sellerApplications[] = $row;
    }
} else {
    $message = "Error fetching applications: " . mysqli_error($con);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Seller Applications</title>
    <style>
        /* Add basic admin styling */
        body { font-family: 'Roboto', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #003366; margin-bottom: 25px; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; color: #333; }
        tr.application-summary-row:nth-child(even) { background-color: #f9f9f9; } /* Apply stripe to summary rows only */

        /* Styles for actions buttons */
        .actions button { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
        .actions .approve-btn { background-color: #28a745; color: white; }
        .actions .reject-btn { background-color: #dc3545; color: white; }

        /* Styles for the details section */
        .application-details-container {
            display: none; /* Hidden by default */
            border-top: 1px dashed #eee;
            margin-top: 10px;
            padding-top: 10px;
            font-size: 0.9em;
            color: #666;
        }

        /* Class to show the details */
        .application-details-container.show {
            display: block;
        }

        .view-details-btn {
            background: #007bff;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .view-details-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Seller Applications - Pending Approval</h1>

        <?php if (!empty($message)) { ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo $message; ?></div>
        <?php } ?>

        <?php if (empty($sellerApplications)): ?>
            <p>No pending seller applications at this time.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Business Type</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sellerApplications as $app): ?>
                        <tr class="application-summary-row">
                            <td><?php echo htmlspecialchars($app['id']); ?></td>
                            <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['email']); ?></td>
                            <td><?php echo htmlspecialchars($app['business_type'] ?? 'N/A'); ?></td> <td><?php echo htmlspecialchars($app['application_status']); ?></td>
                            <td><?php echo htmlspecialchars($app['created_at']); ?></td>
                            <td class="actions">
                                <button class="view-details-btn" data-target="details-<?php echo $app['id']; ?>">View Details</button>
                                <?php if ($app['application_status'] === 'pending'): ?>
                                    <form method="post" style="display:inline-block;">
                                        <input type="hidden" name="seller_id" value="<?php echo htmlspecialchars($app['id']); ?>">
                                        <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                                    </form>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirmReject(this);">
                                        <input type="hidden" name="seller_id" value="<?php echo htmlspecialchars($app['id']); ?>">
                                        <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                        <input type="hidden" name="reason" class="rejection-reason-input">
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7">
                                <div id="details-<?php echo $app['id']; ?>" class="application-details-container">
                                    <p><strong>Owner Name:</strong> <?php echo htmlspecialchars($app['owner_first_name'] . ' ' . ($app['owner_last_name'] ?? '')); ?></p>
                                    <p><strong>Owner Email:</strong> <?php echo htmlspecialchars($app['owner_email'] ?? 'N/A'); ?></p>
                                    <p><strong>Owner Mobile:</strong> <?php echo htmlspecialchars($app['owner_mobile_number'] ?? 'N/A'); ?></p>
                                    <p><strong>Company Legal Name:</strong> <?php echo htmlspecialchars($app['company_legal_name'] ?? 'N/A'); ?></p>
                                    <?php if (!empty($app['sa_business_reg_number'])): ?>
                                        <p><strong>SA Business Reg No:</strong> <?php echo htmlspecialchars($app['sa_business_reg_number']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($app['non_sa_business_reg_number'])): ?>
                                        <p><strong>Non-SA Business Reg No:</strong> <?php echo htmlspecialchars($app['non_sa_business_reg_number']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($app['sa_id_number'])): ?>
                                        <p><strong>SA ID No:</strong> <?php echo htmlspecialchars($app['sa_id_number']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($app['passport_number'])): ?>
                                        <p><strong>Passport No:</strong> <?php echo htmlspecialchars($app['passport_number']); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($app['country'] ?? 'N/A'); ?></p>
                                    <?php if (!empty($app['rejection_reason'])): ?>
                                        <p style="color:red;"><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($app['rejection_reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get the target ID from the data-target attribute
                const targetId = this.dataset.target;
                const detailsContainer = document.getElementById(targetId);

                if (detailsContainer) {
                    detailsContainer.classList.toggle('show');
                    // Change button text based on state
                    if (detailsContainer.classList.contains('show')) {
                        this.textContent = 'Hide Details';
                    } else {
                        this.textContent = 'View Details';
                    }
                }
            });
        });

        function confirmReject(form) {
            const reason = prompt("Please provide a reason for rejecting this application:");
            if (reason === null || reason.trim() === "") {
                alert("Rejection requires a reason. Please provide one.");
                return false; // Prevent form submission
            }
            form.querySelector('.rejection-reason-input').value = reason;
            return true; // Allow form submission
        }
    </script>
</body>
</html>