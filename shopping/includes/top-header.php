<?php
// session_start(); // Keep this commented out if it's already called in login.php or an index file before including this.
                  // If this file can be accessed directly or included without a prior session_start(), then uncomment it.
                  // Given your previous context, it's likely session_start() is handled in login.php or main entry point.
?>

<div class="top-bar animate-dropdown">
    <div class="container">
        <div class="header-top-inner">
            <div class="cnt-account">
                <ul class="list-unstyled">

                    <?php
                    // 1. Changed: Replaced strlen($_SESSION['login']) with (isset($_SESSION['login']) && $_SESSION['login'] != '')
                    if (isset($_SESSION['login']) && $_SESSION['login'] != '') {
                    ?>
                        <li><a href="#"><i class="icon fa fa-user"></i>Welcome -<?php echo htmlentities($_SESSION['username']);?></a></li>
                    <?php
                    }
                    ?>

                    <li><a href="my-account.php"><i class="icon fa fa-user"></i>My Account</a></li>
                    <li><a href="my-wishlist.php"><i class="icon fa fa-heart"></i>Wishlist</a></li>
                    <li><a href="my-cart.php"><i class="icon fa fa-shopping-cart"></i>My Cart</a></li>


                    <?php
                    // 2. Changed: Replaced strlen($_SESSION['login'])==0 with (!isset($_SESSION['login']) || $_SESSION['login'] == '')
                    if (!isset($_SESSION['login']) || $_SESSION['login'] == '') {
                    ?>
                        <li><a href="login.php"><i class="icon fa fa-sign-in"></i>Login</a></li>
                    <?php
                    } else {
                    ?>
                        <li><a href="logout.php"><i class="icon fa fa-sign-out"></i>Logout</a></li>
                    <?php
                    }
                    ?>
                </ul>
            </div><div class="cnt-block">
                <ul class="list-unstyled list-inline">
                    <li class="dropdown dropdown-small">
                        <a href="track-orders.php" class="dropdown-toggle"><span class="key">Track Order</span></a>

                    </li>


                </ul>
            </div>

            <div class="clearfix"></div>
        </div></div></div>```