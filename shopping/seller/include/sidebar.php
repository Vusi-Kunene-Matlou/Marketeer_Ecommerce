<?php
// Ensure session is started and $con (database connection) is available
// These should typically be included from a config/header file that's already loaded
// e.g., session_start(); include('include/config.php');

// You need to make sure the seller_id is available in the session here
// This sidebar is assumed to be included on pages where $_SESSION['seller_id'] is set.
// For example, in seller dashboard, insert-product, etc.
?>
<div class="span3">
    <div class="sidebar">
        <ul class="widget widget-menu unstyled">
            <li>
                <a class="collapsed" data-toggle="collapse" href="#togglePages">
                    <i class="menu-icon icon-cog"></i>
                    <i class="icon-chevron-down pull-right"></i><i class="icon-chevron-up pull-right"></i>
                    Order Management
                </a>
                <ul id="togglePages" class="collapse unstyled">
                    <li>
                        <a href="todays-orders.php">
                            <i class="icon-tasks"></i>
                            Today's Orders
                            <?php
                            // Ensure seller_id is set in session before querying
                            if (isset($_SESSION['seller_id'])) {
                                $seller_id = $_SESSION['seller_id'];
                                $f1="00:00:00";
                                $from=date('Y-m-d')." ".$f1;
                                $t1="23:59:59";
                                $to=date('Y-m-d')." ".$t1;

                                // Query to count distinct orders for the current seller's products today
                                $result = mysqli_query($con, "
                                    SELECT COUNT(DISTINCT o.id) AS total_orders
                                    FROM Orders o
                                    JOIN order_products op ON o.id = op.order_id
                                    JOIN products p ON op.product_id = p.id
                                    WHERE p.seller_id = '$seller_id'
                                      AND o.orderDate BETWEEN '$from' AND '$to'
                                ");
                                $num_rows1 = 0;
                                if ($result && mysqli_num_rows($result) > 0) {
                                    $data = mysqli_fetch_assoc($result);
                                    $num_rows1 = $data['total_orders'];
                                }
                            ?>
                                <b class="label orange pull-right"><?php echo htmlentities($num_rows1); ?></b>
                            <?php
                            } else {
                                // Display 0 if seller_id is not in session (e.g., not logged in properly)
                                echo '<b class="label orange pull-right">0</b>';
                            }
                            ?>
                        </a>
                    </li>
                    <li>
                        <a href="pending-orders.php">
                            <i class="icon-tasks"></i>
                            Pending Orders
                            <?php   
                            if (isset($_SESSION['seller_id'])) {
                                $seller_id = $_SESSION['seller_id'];
                                $status='Delivered';                                        
                                // Query to count distinct pending orders for the current seller's products
                                $ret = mysqli_query($con, "
                                    SELECT COUNT(DISTINCT o.id) AS total_orders
                                    FROM Orders o
                                    JOIN order_products op ON o.id = op.order_id
                                    JOIN products p ON op.product_id = p.id
                                    WHERE p.seller_id = '$seller_id'
                                      AND (o.orderStatus != '$status' OR o.orderStatus IS NULL)
                                ");
                                $num = 0;
                                if ($ret && mysqli_num_rows($ret) > 0) {
                                    $data = mysqli_fetch_assoc($ret);
                                    $num = $data['total_orders'];
                                }
                            ?>
                                <b class="label orange pull-right"><?php echo htmlentities($num); ?></b>
                            <?php
                            } else {
                                echo '<b class="label orange pull-right">0</b>';
                            }
                            ?>
                        </a>
                    </li>
                    <li>
                        <a href="delivered-orders.php">
                            <i class="icon-inbox"></i>
                            Delivered Orders
                            <?php   
                            if (isset($_SESSION['seller_id'])) {
                                $seller_id = $_SESSION['seller_id'];
                                $status='Delivered';                                        
                                // Query to count distinct delivered orders for the current seller's products
                                $rt = mysqli_query($con, "
                                    SELECT COUNT(DISTINCT o.id) AS total_orders
                                    FROM Orders o
                                    JOIN order_products op ON o.id = op.order_id
                                    JOIN products p ON op.product_id = p.id
                                    WHERE p.seller_id = '$seller_id'
                                      AND o.orderStatus = '$status'
                                ");
                                $num1 = 0;
                                if ($rt && mysqli_num_rows($rt) > 0) {
                                    $data = mysqli_fetch_assoc($rt);
                                    $num1 = $data['total_orders'];
                                }
                            ?>
                                <b class="label green pull-right"><?php echo htmlentities($num1); ?></b>
                            <?php
                            } else {
                                echo '<b class="label green pull-right">0</b>';
                            }
                            ?>
                        </a>
                    </li>
                </ul>
            </li>
            
            </ul>

        <ul class="widget widget-menu unstyled">
            <li><a href="insert-product.php"><i class="menu-icon icon-paste"></i>Insert Product </a></li>
            <li><a href="manage-products.php"><i class="menu-icon icon-paste"></i>Manage Product </a></li>

            </ul><ul class="widget widget-menu unstyled">
            <li>
                <a href="logout.php">
                    <i class="menu-icon icon-signout"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div></div>```

