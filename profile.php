<?php

include "./php/config.php";

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit; // Added exit for security
}

// No need to redefine constants - they're already defined in config.php
// For reference, the order status values are:
// ORDER_NOT_ACCEPTED = 0, ORDER_ACCEPTED = 1, ORDER_DISPATCHED = 2
// ORDER_IN_TRANSIT = 3, ORDER_DELIVERED = 4, ORDER_RETURN_PENDING = 5
// ORDER_RETURNED = 6, ORDER_CANCELLED = 7

// Status arrays moved to top for better organization
$status_array = [
    "Not Accepted",
    "Accepted",
    "Dispatched",
    "In-Transit",
    "Delivered",
    "Return Pending",
    "Returned",
    "Cancelled by farmer",
];

// Define return status text
$return_status_array = [
    "Pending",
    "Approved",
    "Rejected"
];

// Get user information using prepared statement
$uname = $_SESSION["username"];
$stmt = $conn->prepare("SELECT userid, name, gender FROM users WHERE username = ?");
$stmt->bind_param("s", $uname);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

// Store user ID for later use
$cid = $user_data["userid"];
$user_name = $user_data["name"];
$gender = $user_data["gender"];

?>

<!DOCTYPE html>
<html>

<head>
  <title>Customer Profile</title>
  <!-- Meta tags for responsiveness-->
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="HandheldFriendly" content="true">
  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- jQuery library -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <!-- Latest compiled JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <!-- Popper JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <!-- Stylesheet -->
  <link rel="stylesheet" type="text/css" href="./css/profilestyle.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    .return-btn {
      margin-top: 10px;
    }
    .alert-container {
      margin-top: 20px;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-dark navbar-expand-lg fixed-top">
    <div class="container">
      <div class="navbar-header">
        <a href="customer.php#shop"><button class="btn mr-5" style="background-color: white;"><i
              class="fa fa-chevron-left" aria-hidden="true"></i></button></a>
        <a class="navbar-brand" href="#"><img src="./assets/Logokrishi.png" class="img-responsive"></a>
      </div>
      <div class="navtoggle">
        <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar" aria-expanded="false"
          aria-controls="navbar">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
      <div id="navbar" class="collapse navbar-collapse stroke">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item"><a class="nav-link" href="customer.php#shop">Shop</a></li>
          <li class="nav-item"><a class="nav-link" href="#myorders">My Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="./php/logout.php">Log Out</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="image">
    <div class="heading text-center">
      <img src="./assets/<?php echo (strcmp($gender, "Female") == 0) ? "woman" : "man"; ?>.png" class="img-responsive" width="10%">
      <h1>Hello
        <?php echo htmlspecialchars($user_name); ?></h1>
    </div>
    <div class="subhead">
      <p>Happy Shopping!</p>
    </div>
  </div>

  <div class="container-fluid" id="myorders" style="padding-top: 7%;">
    <div class="text-center">
      <h1 style="font-weight: 600; font-size: 3.5em; letter-spacing: 2px; color: #34626c;">YOUR ORDERS</h1>
      <hr width="40%" align="text-center" style="border-width: 4px; background-color: #999;">
    </div>

    <?php
    // Display return success/error messages
    if (isset($_GET['return_success'])) {
      echo '<div class="container alert-container"><div class="alert alert-success alert-dismissible fade show auto-dismiss">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>Success!</strong> Your return request has been submitted successfully.
        </div></div>';
    }
    if (isset($_GET['return_error'])) {
      $error_message = "An error occurred while processing your return.";
      if ($_GET['return_error'] == 'invalid_order') {
        $error_message = "This order cannot be returned. Only delivered orders can be returned.";
      } else if ($_GET['return_error'] == 'update_failed') {
        $error_message = "Failed to update order status. Please try again later.";
      } else if ($_GET['return_error'] == 'rejected_return') {
        $error_message = "This order cannot be returned as a previous return request was rejected by the farmer.";
      } else if ($_GET['return_error'] == 'time_expired') {
        $error_message = "This order cannot be returned as the 24-hour return period has expired.";
      }
      echo '<div class="container alert-container"><div class="alert alert-danger alert-dismissible fade show auto-dismiss">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>Error!</strong> ' . htmlspecialchars($error_message) . '
        </div></div>';
    }
    
    // Get all order IDs for this user - using prepared statement
    $order_stmt = $conn->prepare("SELECT orderid from myorder WHERE userid = ? GROUP BY orderid HAVING COUNT(*) >= 1 ORDER BY orderid DESC");
    $order_stmt->bind_param("i", $cid);
    $order_stmt->execute();
    $orders_result = $order_stmt->get_result();
    $order_stmt->close();
    ?>
    
    <!-- Start grid container -->
    <div class="container mt-4">
      <div class="row">
            <?php
    // For each order ID
    while ($order_row = $orders_result->fetch_assoc()) {
      $oid = $order_row['orderid'];
      
      // Get order date - using prepared statement
      $date_stmt = $conn->prepare("SELECT orderdate FROM myorder WHERE userid = ? AND orderid = ? LIMIT 1");
      $date_stmt->bind_param("ii", $cid, $oid);
      $date_stmt->execute();
      $date_result = $date_stmt->get_result();
      $date_row = $date_result->fetch_assoc();
      $date_stmt->close();
      
      $order_date = date_create($date_row['orderdate']);
      $formatted_date = date_format($order_date, "d/m/Y");
    ?>
      <!-- Each order is in a column that takes up 6 columns on medium screens (2 per row) and 12 on small screens (1 per row) -->
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                <span class="badge badge-pill badge-warning px-3 py-2">Order #<?php echo htmlspecialchars($oid); ?></span>
              </h5>
              <small class="text-muted"><?php echo $formatted_date; ?></small>
        </div>
      </div>
          <div class="card-body p-3">
        <?php
            // Get all farmers for this order - using prepared statement
            $farmer_stmt = $conn->prepare(
                "SELECT DISTINCT m.farmerid, f.name, m.status 
                 FROM myorder m
                 JOIN farmer f ON m.farmerid = f.farmerid
                 WHERE m.userid = ? AND m.orderid = ?
                 GROUP BY m.farmerid HAVING COUNT(*) >= 1"
            );
            $farmer_stmt->bind_param("ii", $cid, $oid);
            $farmer_stmt->execute();
            $farmers_result = $farmer_stmt->get_result();
            $farmer_stmt->close();
            
            while ($farmer_row = $farmers_result->fetch_assoc()) {
              $fid = $farmer_row["farmerid"];
              $fname = $farmer_row["name"];
              $status = $farmer_row["status"];
            ?>
              <div class="farmer-info mb-3 border-bottom pb-2">
                <div class="d-flex justify-content-between align-items-center">
                  <h6 class="mb-2"><i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($fname); ?></h6>
                  <span class="
                    <?php switch ($status) {
                        case 0:
                            echo "badge badge-secondary";
                            break;
                        case 1:
                            echo "badge badge-info";
                            break;
                        case 2:
                            echo "badge badge-dark";
                            break;
                        case 3:
                            echo "badge badge-warning";
                            break;
                        case 4:
                            echo "badge badge-success";
                            break;
                          case 5:
                              echo "badge badge-primary";
                            break;
                        case 6:
                              echo "badge badge-success";
                              break;
                          case 7:
                            echo "badge badge-danger";
                            break;
                        default:
                            echo "Not Available";
                            break;
                      } ?>"> <?php echo htmlspecialchars($status_array[$status]); ?> </span>
                </div>
                <div class="items-list">
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <tbody>
              <?php
                        // Get products for this order and farmer - using prepared statement
                        $products_stmt = $conn->prepare(
                          "SELECT m.prodid, m.quantity, m.amount, m.original_amount, m.coupon_code, m.status, p.prodname 
                           FROM myorder m
                           JOIN product p ON m.prodid = p.prodid
                           WHERE m.userid = ? AND m.orderid = ? AND m.farmerid = ?"
                        );
                        $products_stmt->bind_param("iii", $cid, $oid, $fid);
                        $products_stmt->execute();
                        $products_result = $products_stmt->get_result();
                        $products_stmt->close();
                        
                        while ($product = $products_result->fetch_assoc()) {
                          $pname = $product["prodname"];
                          $quant = $product["quantity"];
                          $amount = $product["amount"];
                          $original_amount = $product["original_amount"];
                          $coupon_code = $product["coupon_code"];
                          $product_status = $product["status"];
                  ?>

              <tr>
                          <td class="text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($pname); ?>"><?php echo htmlspecialchars($pname); ?></td>
                          <td class="text-nowrap"><small>x<?php echo htmlspecialchars($quant); ?> kg</small></td>
                          <td class="text-right">
                            <?php 
                              if ($coupon_code && $original_amount > $amount) {
                                echo '<span style="text-decoration: line-through;">₹' . htmlspecialchars($original_amount) . '</span> ';
                                echo '₹' . htmlspecialchars($amount);
                              } else {
                                echo '₹' . htmlspecialchars($amount);
                              }
                            ?>
                          </td>
              </tr>

              <?php
              }
              ?>
                      </tbody>
            </table>
          </div>
        </div>

        <?php
                // If a coupon was applied, show the coupon information
                $coupon_query = $conn->prepare(
                  "SELECT coupon_code, original_amount, amount 
                   FROM myorder 
                   WHERE orderid = ? AND farmerid = ? AND userid = ? AND coupon_code IS NOT NULL 
                   LIMIT 1"
                );
                $coupon_query->bind_param("iii", $oid, $fid, $cid);
                $coupon_query->execute();
                $coupon_result = $coupon_query->get_result();
                $coupon_query->close();
                
                if ($coupon_result->num_rows > 0) {
                  $coupon_data = $coupon_result->fetch_assoc();
                  $coupon_code = $coupon_data["coupon_code"];
                  
                  // Get the discount percentage from the coupon code
                  $disc_query = $conn->prepare("SELECT discount FROM coupon WHERE couponcode = ?");
                  $disc_query->bind_param("s", $coupon_code);
                  $disc_query->execute();
                  $disc_result = $disc_query->get_result();
                  $disc_query->close();
                  
                  if ($disc_result->num_rows > 0) {
                    $disc_data = $disc_result->fetch_assoc();
                    $discount = $disc_data["discount"];
                    
                    echo '<div class="text-right mt-2 text-success">
                      <small><i class="fa fa-tag"></i> Coupon applied: ' . htmlspecialchars($coupon_code) . ' - ' . $discount . '% off</small>
                    </div>';
                  }
                }
                
                // Check for rejected returns first
                $return_rejected = false;
                $rejection_reason = "";
                
                // Using prepared statement for checking returns
                $return_check_stmt = $conn->prepare(
                  "SELECT return_status, status, return_rejection_reason 
                   FROM myorder 
                   WHERE orderid = ? AND farmerid = ? AND userid = ?"
                );
                $return_check_stmt->bind_param("iii", $oid, $fid, $cid);
                $return_check_stmt->execute();
                $return_check_result = $return_check_stmt->get_result();
                $return_check_stmt->close();
                
                // Check if any product in this order has a rejected return
                while ($return_check_data = $return_check_result->fetch_assoc()) {
                  // Check if this is a rejected return
                  if ((isset($return_check_data["return_status"]) && $return_check_data["return_status"] == 2) || 
                      $return_check_data["status"] == ORDER_CANCELLED) {  // Use ORDER_CANCELLED instead of ORDER_RETURN_REJECTED
                    $return_rejected = true;
                    $rejection_reason = isset($return_check_data["return_rejection_reason"]) ? 
                                      htmlspecialchars($return_check_data["return_rejection_reason"]) : 
                                      "No reason provided";
                    break; // Found a rejected return, no need to check further
                  }
                }
                
                // Add Return button if status is "Delivered" (4) and there's no rejected return
                if ($status == ORDER_DELIVERED && !$return_rejected) {
                  // Get order date to check if it's within return period
                  $order_time_stmt = $conn->prepare(
                    "SELECT orderdate, TIMESTAMPDIFF(HOUR, orderdate, NOW()) as hours_since_delivery 
                     FROM myorder 
                     WHERE orderid = ? AND farmerid = ? AND userid = ? 
                     LIMIT 1"
                  );
                  $order_time_stmt->bind_param("iii", $oid, $fid, $cid);
                  $order_time_stmt->execute();
                  $order_time_result = $order_time_stmt->get_result();
                  $order_time_data = $order_time_result->fetch_assoc();
                  $order_time_stmt->close();
                  
                  $hours_since_delivery = $order_time_data['hours_since_delivery'];
                  
                  // Only show return button if within 24 hours
                  if ($hours_since_delivery <= 24) {
                    // Get the first product ID to use for the return modal - using prepared statement
                    $first_prod_stmt = $conn->prepare(
                      "SELECT m.prodid, p.prodname 
                       FROM myorder m
                       JOIN product p ON m.prodid = p.prodid
                       WHERE m.orderid = ? AND m.farmerid = ? AND m.userid = ? 
                       LIMIT 1"
                    );
                    $first_prod_stmt->bind_param("iii", $oid, $fid, $cid);
                    $first_prod_stmt->execute();
                    $first_prod_result = $first_prod_stmt->get_result();
                    $first_prod = $first_prod_result->fetch_assoc();
                    $first_prod_stmt->close();
                    
                    $modal_pid = $first_prod["prodid"];
                    $modal_pname = $first_prod["prodname"];
                    
                    echo '<div class="text-center mt-2">
                      <button type="button" class="btn btn-warning btn-sm btn-block" 
                        onclick="openReturnModal(' . $oid . ', ' . $fid . ', ' . $modal_pid . ', \'' . htmlspecialchars($modal_pname, ENT_QUOTES) . '\')">
                        <i class="fa fa-undo"></i> Return Order
                      </button>
                    </div>';
                  } else {
                    // Return period expired
                    echo '<div class="text-center mt-2">
                      <div class="alert alert-secondary p-2" style="font-size: 0.8em;">
                        <i class="fa fa-clock-o"></i> Return period expired (24 hours)
                      </div>
                    </div>';
                  }
                }
                
                // Show return information if status is "Return Pending" (5)
                if ($status == ORDER_RETURN_PENDING) {
                  // Using prepared statement for return info
                  $return_stmt = $conn->prepare(
                    "SELECT return_reason, return_date 
                     FROM myorder 
                     WHERE orderid = ? AND farmerid = ? AND status = ? 
                     LIMIT 1"
                  );
                  $return_pending = ORDER_RETURN_PENDING;
                  $return_stmt->bind_param("iii", $oid, $fid, $return_pending);
                  $return_stmt->execute();
                  $return_result = $return_stmt->get_result();
                  $return_stmt->close();
                  
                  if ($return_result->num_rows > 0) {
                    $return_data = $return_result->fetch_assoc();
                    $reason = isset($return_data["return_reason"]) ? htmlspecialchars($return_data["return_reason"]) : "No reason provided";
                    $date = isset($return_data["return_date"]) ? date("d/m/Y", strtotime($return_data["return_date"])) : "Unknown date";
                    
                    echo '<div class="alert alert-primary mt-2 p-2" style="font-size: 0.9em;">
                      <strong>Return Requested:</strong> ' . $reason . '<br>
                      <small>Request date: ' . $date . '</small><br>
                      <small class="text-muted">Under review</small>
                    </div>';
                  }
                }
                
                // Show return information if status is "Returned" (6)
                if ($status == ORDER_RETURNED) {
                  // Using prepared statement for return info
                  $return_stmt = $conn->prepare(
                    "SELECT return_reason, return_date 
                     FROM myorder 
                     WHERE orderid = ? AND farmerid = ? AND status = ? 
                     LIMIT 1"
                  );
                  $returned = ORDER_RETURNED;
                  $return_stmt->bind_param("iii", $oid, $fid, $returned);
                  $return_stmt->execute();
                  $return_result = $return_stmt->get_result();
                  $return_stmt->close();
                  
                  if ($return_result->num_rows > 0) {
                    $return_data = $return_result->fetch_assoc();
                    echo '<div class="alert alert-success mt-2 p-2" style="font-size: 0.9em;">
                      <strong>Return Approved:</strong> ' . htmlspecialchars($return_data["return_reason"]) . '<br>
                      <small>Return date: ' . date("d/m/Y", strtotime($return_data["return_date"])) . '</small>
                    </div>';
                  }
                }
                
                // Show cancelled order message if status is "Cancelled" (7)
                if ($status == ORDER_CANCELLED) {
                  // Get cancellation reason
                  $reason_stmt = $conn->prepare(
                    "SELECT cancellation_reason 
                     FROM myorder 
                     WHERE orderid = ? AND farmerid = ? AND status = ? 
                     LIMIT 1"
                  );
                  $cancelled = ORDER_CANCELLED;
                  $reason_stmt->bind_param("iii", $oid, $fid, $cancelled);
                  $reason_stmt->execute();
                  $reason_result = $reason_stmt->get_result();
                  $reason_stmt->close();
                  
                  $cancellation_reason = "No reason provided";
                  if ($reason_result->num_rows > 0) {
                    $reason_data = $reason_result->fetch_assoc();
                    if (!empty($reason_data["cancellation_reason"])) {
                      $cancellation_reason = htmlspecialchars($reason_data["cancellation_reason"]);
                    }
                  }
                  
                  echo '<div class="alert alert-danger mt-2 p-2" style="font-size: 0.9em;">
                    <strong>Order Cancelled by Farmer</strong><br>
                    <small>Reason: ' . $cancellation_reason . '</small>
                  </div>';
                  
                  // When an order is cancelled, we don't need to show any return rejection messages
                  $return_rejected = false;
                }
                
                // Show rejection message if return was rejected
                if ($return_rejected) {
                  // Only show rejection message if this is not a cancelled order
                  if ($status != ORDER_CANCELLED) {
                    echo '<div class="alert alert-warning mt-2 p-2" style="font-size: 0.9em;">
                      <strong>Return Rejected:</strong> ' . $rejection_reason . '<br>
                      <small>Request declined by farmer</small>
                    </div>';
                  }
                }
                
                // Also check for return_rejection_reason in delivered orders (for rejected returns)
                // Only check if we haven't already shown a rejection message
                if ($status == ORDER_DELIVERED && !$return_rejected) {
                  // Using prepared statement for rejection info
                  $rejection_stmt = $conn->prepare(
                    "SELECT return_rejection_reason 
                     FROM myorder 
                     WHERE orderid = ? AND farmerid = ? AND userid = ? AND return_status = 2 
                     LIMIT 1"
                  );
                  $rejection_stmt->bind_param("iii", $oid, $fid, $cid);
                  $rejection_stmt->execute();
                  $rejection_result = $rejection_stmt->get_result();
                  $rejection_stmt->close();
                  
                  if ($rejection_result->num_rows > 0) {
                    $rejection_data = $rejection_result->fetch_assoc();
                    $reject_reason = htmlspecialchars($rejection_data["return_rejection_reason"]);
                    
                    echo '<div class="alert alert-warning mt-2 p-2" style="font-size: 0.9em;">
                      <strong>Return Rejected:</strong> ' . $reject_reason . '<br>
                      <small>Request declined by farmer</small>
                    </div>';
                  }
                }
                ?>
              </div> <!-- End farmer-info -->
            <?php
            } // End farmer loop
            ?>
          </div> <!-- End card-body -->
        </div> <!-- End card -->
      </div> <!-- End column -->
    <?php
    } // End order loop
    ?>
      </div> <!-- End row -->
    </div> <!-- End container -->
  </div> <!-- End myorders container-fluid -->

  <!-- Include the return modal -->
  <?php include "./php/returns/return_modal.php"; ?>

  <div class="container-fluid py-2 mt-3" style="background-color: #3797a4; font-weight: 600;">
    <div class="col-lg-12 text-center">
      © 2025 Copyright <a href="#" style="text-decoration: none; color: inherit">KrishiMitra</a>
    </div>
  </div>

  <script type="text/javascript" src="./scripts/cart.js"></script>
  
  <script>
    // Auto-dismiss alert messages after 5 seconds - only for notification alerts, not status messages
    $(document).ready(function() {
      // Set timeout for alerts to fade out - only for auto-dismiss class
      setTimeout(function() {
        $('.auto-dismiss').alert('close');
      }, 5000); // 5000 milliseconds = 5 seconds
      
      // Initialize return modal functionality
      window.openReturnModal = function(orderId, farmerId, prodId, prodName) {
        $('#return_orderid').val(orderId);
        $('#return_farmerid').val(farmerId);
        $('#return_prodid').val(prodId);
        $('#return_product').val(prodName);
        $('#returnModal').modal('show');
      };
    });
  </script>
</body>

</html>