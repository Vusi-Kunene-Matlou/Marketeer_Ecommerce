<?php
session_start();
include_once('includes/config.php');

// If the user is not logged in, redirect to logout
if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit(); // Always exit after a header redirect
}

// If no address is selected, redirect back to checkout
if ($_SESSION['address'] == 0) {
    echo "<script type='text/javascript'> document.location ='checkout.php'; </script>";
    exit(); // Always exit after a script redirect
}

// This block executes when the payment form is submitted
if (isset($_POST['submit'])) {
    $orderno = mt_rand(100000000, 999999999);
    $userid = $_SESSION['id'];
    $addressId = $_SESSION['address']; // Renamed for clarity: it's the address ID
    $totalamount = $_SESSION['gtotal'];
    $txntype = $_POST['paymenttype'];
    $txnno = $_POST['txnnumber'];

    // Start a transaction for atomicity: all operations succeed or all fail
    mysqli_begin_transaction($con);

    try {
        // 1. Insert into the main 'orders' table
        $insertOrderQuery = mysqli_query($con, "
            INSERT INTO orders(orderNumber, userId, addressId, totalAmount, txnType, txnNumber, orderDate, orderStatus)
            VALUES('$orderno', '$userid', '$addressId', '$totalamount', '$txntype', '$txnno', NOW(), 'Order Placed')
        ");

        if (!$insertOrderQuery) {
            // If main order insertion fails, throw an exception to trigger rollback
            throw new Exception("Error inserting main order: " . mysqli_error($con));
        }

        // Get the ID of the newly inserted order
        $order_id = mysqli_insert_id($con);
        if (!$order_id) {
            throw new Exception("Could not retrieve the ID of the newly placed order.");
        }

        // 2. Fetch cart items with their current product price for insertion into order_products
        $cartQuery = mysqli_query($con, "
            SELECT c.productId, c.productQty, p.productPrice
            FROM cart c
            JOIN products p ON p.id = c.productId
            WHERE c.userId = '$userid'
        ");

        if (!$cartQuery) {
            // If fetching cart items fails, throw an exception
            throw new Exception("Error fetching cart items for order details: " . mysqli_error($con));
        }

        // 3. Loop through each item in the cart and insert into 'order_products'
        while ($cartItem = mysqli_fetch_array($cartQuery)) {
            $productId = $cartItem['productId'];
            $quantity = $cartItem['productQty'];
            $item_price = $cartItem['productPrice']; // Record the price at the time of order

            $insertOrderProductQuery = mysqli_query($con, "
                INSERT INTO order_products(order_id, product_id, quantity, item_price)
                VALUES('$order_id', '$productId', '$quantity', '$item_price')
            ");

            if (!$insertOrderProductQuery) {
                // If any order product insertion fails, throw an exception
                throw new Exception("Error inserting product ID $productId into order_products: " . mysqli_error($con));
            }
        }

        // 4. Clear the user's cart after all order details are successfully recorded
        $deleteCartQuery = mysqli_query($con, "DELETE FROM cart WHERE userId = '$userid'");
        if (!$deleteCartQuery) {
            // If cart clearing fails, throw an exception
            throw new Exception("Error clearing user's cart: " . mysqli_error($con));
        }

        // If all operations within the transaction were successful, commit the changes
        mysqli_commit($con);

        // Clear session variables related to the order
        unset($_SESSION['address']);
        unset($_SESSION['gtotal']);

        // Success message and redirection
        echo '<script>alert("Your order successfully placed. Order number is ' . $orderno . '");</script>';
        echo "<script type='text/javascript'> document.location ='my-orders.php'; </script>";
        exit(); // Exit to prevent further script execution after redirection

    } catch (Exception $e) {
        // If any error occurred, rollback the transaction to undo all changes
        mysqli_rollback($con);
        // Display an error message and redirect back to payment page
        echo "<script>alert('Order placement failed: " . $e->getMessage() . ". Please try again.');</script>";
        echo "<script type='text/javascript'> document.location ='payment.php'; </script>";
        exit(); // Exit to prevent further script execution after redirection
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Marketeer | Payment</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="js/jquery.min.js"></script>
    </head>
<style type="text/css"></style>
<body>
    <?php include_once('includes/header.php'); ?>
    <header class="bg-dark py-5">
        <div class="container px-4 px-lg-5 my-5">
            <div class="text-center text-white">
                <h1 class="display-4 fw-bolder">Payment</h1>
            </div>
        </div>
    </header>
    <section class="py-5">
        <div class="container px-4 mt-5">
            <form method="post" name="signup">
                <div class="row">
                    <div class="col-2">Total Payment</div>
                    <div class="col-6">
                        <input type="text" name="totalamount" value="<?php echo  htmlentities($_SESSION['gtotal']); ?>" class="form-control" readonly>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Payment Type</div>
                    <div class="col-6">
                        <select class="form-control" name="paymenttype" id="paymenttype" required>
                            <option value="">Select</option>
                            <option value="e-Wallet">E-Wallet</option>
                            <option value="Internet Banking">Internet Banking</option>
                            <option value="Debit/Credit Card">Debit/Credit Card</option>
                            <option value="Cash on Delivery">Cash on Delivery (COD)</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3" id="txnno">
                    <div class="col-2">Transaction Number</div>
                    <div class="col-6">
                        <input type="text" name="txnnumber" id="txnnumber" class="form-control" maxlength="50">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-4">&nbsp;</div>
                    <div class="col-6">
                        <input type="submit" name="submit" id="submit" class="btn btn-primary" required>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <?php include_once('includes/footer.php'); ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script type="text/javascript">
        //For report file
        $('#txnno').hide();
        $(document).ready(function() {
            $('#paymenttype').change(function() {
                if ($('#paymenttype').val() == 'Cash on Delivery') {
                    $('#txnno').hide();
                } else if ($('#paymenttype').val() == '') {
                    $('#txnno').hide();
                } else {
                    $('#txnno').show();
                    jQuery("#txnnumber").prop('required', true);
                }
            })
        })
    </script>
</body>
</html>