<?php

include "config.php";
session_start();
$farmer = $_SESSION["username"];
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Use prepared statement for initial farmer query
$stmt = $conn->prepare("SELECT farmerid FROM farmer WHERE username = ?");
$stmt->bind_param("s", $farmer);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$farmerid = $row["farmerid"];
$stmt->close();

// Process direct approve action
if (isset($_POST['direct_approve']) && isset($_POST['orderid']) && isset($_POST['userid'])) {
    $orderid = $_POST['orderid'];
    $userid = $_POST['userid'];
    $prodid = isset($_POST['prodid']) ? $_POST['prodid'] : 0;
    
    // Verify the return request exists - use prepared statement
    $check_stmt = $conn->prepare("SELECT * FROM myorder WHERE orderid=? AND userid=? AND farmerid=? AND status=?");
    $return_pending = ORDER_RETURN_PENDING;
    $check_stmt->bind_param("iiis", $orderid, $userid, $farmerid, $return_pending);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        header("Location: order.php?error=invalid_request");
        exit;
    } else {
        // Update order status - use prepared statement
        $update_stmt = $conn->prepare("UPDATE myorder SET status=?, return_status=1 WHERE orderid=? AND userid=? AND farmerid=? AND status=?");
        $returned_status = ORDER_RETURNED;
        $update_stmt->bind_param("siiis", $returned_status, $orderid, $userid, $farmerid, $return_pending);
        $update_success = $update_stmt->execute();
        $update_stmt->close();
        
        if ($update_success) {
            // Get the products to update inventory - use prepared statement
            $products_stmt = $conn->prepare("SELECT prodid, quantity FROM myorder WHERE orderid=? AND userid=? AND farmerid=? AND status=?");
            $products_stmt->bind_param("iiis", $orderid, $userid, $farmerid, $returned_status);
            $products_stmt->execute();
            $products_result = $products_stmt->get_result();
            $products_stmt->close();
            
            if ($products_result) {
                $inventory_updated = false;
                while ($prod = $products_result->fetch_assoc()) {
                    $returned_prodid = $prod['prodid'];
                    $returned_quantity = intval($prod['quantity']);
                    
                    // Update inventory - use prepared statement
                    $inventory_stmt = $conn->prepare("UPDATE myshop SET quantity = CAST(quantity AS UNSIGNED) + ? WHERE farmerid=? AND prodid=?");
                    $inventory_stmt->bind_param("iii", $returned_quantity, $farmerid, $returned_prodid);
                    $inventory_stmt->execute();
                    
                    if ($inventory_stmt->affected_rows > 0) {
                        $inventory_updated = true;
                    }
                    $inventory_stmt->close();
                }
                
                if ($inventory_updated) {
                    header("Location: order.php?return_approved=1&orderid=$orderid");
                    exit;
                } else {
                    header("Location: order.php?error=inventory_update_failed&orderid=$orderid");
                    exit;
                }
            } else {
                header("Location: order.php?error=product_query_failed&orderid=$orderid");
                exit;
            }
        } else {
            header("Location: order.php?error=update_failed&orderid=$orderid");
            exit;
        }
    }
    $check_stmt->close();
}

// Update order status
if (isset($_POST["update"])) {
    $orderid = $_POST["update"];
    
    // Use prepared statement for the update
    $update_stmt = $conn->prepare("UPDATE myorder SET status = status + 1 WHERE orderid = ? AND farmerid = ?");
    $update_stmt->bind_param("ii", $orderid, $farmerid);
    $update_stmt->execute();
    $update_stmt->close();

    // Get order items - use prepared statement
    $items_stmt = $conn->prepare("SELECT * FROM myorder WHERE orderid = ? AND farmerid = ?");
    $items_stmt->bind_param("ii", $orderid, $farmerid);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $items_stmt->close();
    
    while ($orderinfo = $items_result->fetch_assoc()) {
        if ($orderinfo["status"] == 1) {
            $orderquant = $orderinfo["quantity"];
            $item_farmerid = $orderinfo["farmerid"];
            $prodid = $orderinfo["prodid"];
            
            // Update inventory - use prepared statement
            $inventory_stmt = $conn->prepare("UPDATE myshop SET quantity = quantity - ? WHERE farmerid = ? AND prodid = ?");
            $inventory_stmt->bind_param("iii", $orderquant, $item_farmerid, $prodid);
            $inventory_stmt->execute();
            $inventory_stmt->close();
        }
    }
}

// Cancel/delete order
if (isset($_POST["delete"])) {
    $orderid = $_POST["delete"];
    $cancel_reason = "";
    
    // Get the cancellation reason
    if (isset($_POST["cancel_reason"])) {
        if ($_POST["cancel_reason"] == "Other" && !empty($_POST["other_reason"])) {
            $cancel_reason = $_POST["other_reason"];
        } else {
            $cancel_reason = $_POST["cancel_reason"];
        }
    }
    
    // Get order items - use prepared statement
    $items_stmt = $conn->prepare("SELECT * FROM myorder WHERE orderid = ? AND farmerid = ?");
    $items_stmt->bind_param("ii", $orderid, $farmerid);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $items_stmt->close();
    
    while ($orderinfo = $items_result->fetch_assoc()) {
        $orderquant = $orderinfo["quantity"];
        $item_farmerid = $orderinfo["farmerid"];
        $prodid = $orderinfo["prodid"];
        
        // Update order status - use prepared statement
        $status_stmt = $conn->prepare("UPDATE myorder SET status = ?, cancellation_reason = ? WHERE orderid = ?");
        $cancelled_status = ORDER_CANCELLED; // Use the constant instead of hardcoded value
        $status_stmt->bind_param("isi", $cancelled_status, $cancel_reason, $orderid);
        $status_stmt->execute();
        $status_stmt->close();
        
        // Update inventory - use prepared statement
        $inventory_stmt = $conn->prepare("UPDATE myshop SET quantity = quantity + ? WHERE farmerid = ? AND prodid = ?");
        $inventory_stmt->bind_param("iii", $orderquant, $item_farmerid, $prodid);
        $inventory_stmt->execute();
        $inventory_stmt->close();
    }
}

// Process direct rejection of return request
if (isset($_POST['direct_reject'])) {
    $order_id = $_POST['orderid'];
    $product_id = $_POST['prodid'];
    $user_id = $_POST['userid'];
    $reject_reason = "";
    
    // Get the rejection reason
    if (isset($_POST["reject_reason"])) {
        if ($_POST["reject_reason"] == "Other" && !empty($_POST["other_reject_reason"])) {
            $reject_reason = $_POST["other_reject_reason"];
        } else {
            $reject_reason = $_POST["reject_reason"];
        }
    }
    
    if (empty($order_id) || empty($product_id) || empty($user_id) || empty($reject_reason)) {
        header("Location: order.php?error=missing_data");
        exit();
    }
    
    // Check if return request exists - already using prepared statement
    $check_query = $conn->prepare("SELECT * FROM myorder WHERE orderid = ? AND prodid = ? AND userid = ? AND status = ?");
    $return_status = ORDER_RETURN_PENDING;
    $check_query->bind_param("iiis", $order_id, $product_id, $user_id, $return_status);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update order status - already using prepared statement
        $update_query = $conn->prepare("UPDATE myorder SET status = ?, return_status = 2, return_rejection_reason = ? WHERE orderid = ? AND prodid = ? AND userid = ? AND status = ?");
        $new_status = ORDER_DELIVERED; // Changed from ORDER_CANCELLED to ORDER_DELIVERED
        $update_query->bind_param("isiiii", $new_status, $reject_reason, $order_id, $product_id, $user_id, $return_status);
        
        if ($update_query->execute()) {
            // Redirect with success parameter
            header("Location: order.php?return_rejected=1");
            exit();
        } else {
            header("Location: order.php?error=update_failed");
            exit();
        }
    } else {
        header("Location: order.php?error=invalid_request");
        exit();
    }
}

// Streamline and clean up remaining return-related code
// NOTE: The approve_return and reject_return handlers appear to be for a different table schema
// They reference 'orders', 'order_items', and 'farmer_products' which aren't referenced elsewhere
// For now, keeping them as is since we don't know if they're still needed
// Order status constants are already defined in config.php
?>


<!DOCTYPE html>
<html lang="en">

<head>

  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <!-- Compiled and minified JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>


  <title>Orders</title>
  <style>
  .back {
    margin: 20px;
    background-color: white !important;
    border-color: white !important;
  }

  * {
    font-family: 'Poppins', sans-serif;
  }

  .orders-container {
    padding: 20px 0 40px;
    max-width: 1100px;
    margin: 0 auto;
  }

  /* Main row containing all orders */
  .orders-container > .row {
    margin: 0 -25px;
  }

  /* Column containing a single order card */
  .orders-container .col-12,
  .orders-container .col-md-6 {
    padding: 0 25px;
    margin-bottom: 60px;
  }

  table {
    border-collapse: collapse;
    border-radius: 0.5em;
    overflow: hidden;
    width: 100%;
    background-color: #f5f5f5;
    margin-bottom: 0;
  }

  th {
    height: 40px;
    background-color: #007bff;
    color: #fff;
    font-size: 0.9em;
  }

  tr:hover {
    background-color: #e9ecef;
  }

  .card {
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    border: 1px solid #e0e0e0;
    height: 100%;
    transition: transform 0.2s;
    margin-bottom: 0;
    width: 100%;
  }
  
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
  }

  .card-title {
    padding: 0;
    margin-bottom: 0;
  }

  .card-body {
    padding: 1.25rem;
  }

  .card-footer {
    background-color: rgba(0,0,0,0.03);
    padding: 0.75rem 1.25rem;
    border-top: 1px solid rgba(0,0,0,0.125);
  }

  #orderid {
    border-radius: 10px 10px 0 0;
    text-indent: 10px;
    background-color: #ffd369;
    margin-bottom: 0;
    padding: 15px;
    font-weight: 600;
    font-size: 1.5rem;
  }

  .action-buttons {
    display: flex;
    justify-content: flex-start;
    align-items: center;
  }

  .action-buttons form {
    margin-right: 10px;
  }

  .table-responsive {
    margin-bottom: 1rem;
  }

  /* Equal height cards */
  .row {
    display: flex;
    flex-wrap: wrap;
  }
  
  .col-12,
  .col-md-6 {
    display: flex;
  }
  
  /* Mobile responsiveness */
  @media (max-width: 768px) {
    .orders-container .col-12 {
      padding: 0 15px;
      margin-bottom: 30px;
    }
    
    th, td {
      font-size: 0.9em;
    }
  }
  
  /* Status badge styling */
  .badge {
    padding: 0.4em 0.8em;
    font-size: 85%;
    font-weight: 600;
    border-radius: 0.25rem;
  }
  
  /* Alert styling for rejected returns and cancellations */
  .alert {
    margin-top: 15px;
    margin-bottom: 15px;
    border-radius: 5px;
  }
  
  /* Extra spacing for order status section */
  .mt-3 {
    margin-top: 1.5rem !important;
  }
  </style>
</head>

<body>
  <nav class="nav-wrapper navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="back btn btn-primary" href="welcomefarmer.php" role="button"><img
        src="https://img.icons8.com/android/24/000000/back.png" /><span class="sr-only">(current)</span></a>
    <a class="navbar-brand" href="#"><img src="../assets/Logokrishi.png" /></a>
    <a class="navbar-brand" href="#">Farmer</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown"
      aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item active">
          <a class="nav-link" href="welcomefarmer.php">Home <span class="sr-only">(current)</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>

      <div class="navbar-collapse collapse">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item active">
            <a class="nav-link" href="#"> <img src="https://img.icons8.com/metro/26/ffffff/user-male.png"> <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!--------------------------------------------NAV BAR OVER------------------------------------------------------->
  <div class="container">
    <div class="mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><b>Your Orders </b><img src="https://img.icons8.com/fluent-systems-regular/64/000000/purchase-order.png"></h3>
      </div>
      <hr>
    </div>
    
    <!-- Alert Messages Container -->
    <div id="alertMessages" style="margin-bottom: 20px;">
    <?php
    // Display notification messages
    if (isset($_GET['return_approved'])) {
      echo '<div class="alert alert-success alert-dismissible fade show" style="margin-top: 15px; font-size: 1.2em; padding: 15px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>Success!</strong> You have approved the return request. The product has been added back to your inventory.
        </div>';
    }
    if (isset($_GET['return_rejected'])) {
      echo '<div class="alert alert-info alert-dismissible fade show" style="margin-top: 15px; font-size: 1.2em; padding: 15px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>Success!</strong> You have rejected the return request.
        </div>';
    }
    if (isset($_GET['error'])) {
      $error_message = "An error occurred while processing your request.";
      if ($_GET['error'] == 'invalid_request') {
        $error_message = "Invalid request. The order may not exist or has already been processed.";
      } else if ($_GET['error'] == 'update_failed') {
        $error_message = "Failed to update order status. Please try again later.";
      } else if ($_GET['error'] == 'missing_data') {
        $error_message = "Missing required data for processing the return.";
      } else if ($_GET['error'] == 'query_failed') {
        $error_message = "Database query failed. Please contact the administrator.";
      } else if ($_GET['error'] == 'product_query_failed') {
        $error_message = "Failed to retrieve product information for updating inventory.";
      } else if ($_GET['error'] == 'inventory_update_failed') {
        $error_message = "Failed to update inventory. The return status was updated but inventory was not adjusted.";
      }
      echo '<div class="alert alert-danger alert-dismissible fade show" style="margin-top: 15px; font-size: 1.2em; padding: 15px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>Error!</strong> ' . htmlspecialchars($error_message) . '
        </div>';
    }

    // Display success message for return approval/rejection
    if (isset($_SESSION['return_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" style="margin-top: 15px; font-size: 1.2em; padding: 15px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Success!</strong> ' . htmlspecialchars($_SESSION['return_message']) . '
            </div>';
        unset($_SESSION['return_message']);
    }

    // Display error message for return processing
    if (isset($_SESSION['return_error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" style="margin-top: 15px; font-size: 1.2em; padding: 15px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Error!</strong> ' . htmlspecialchars($_SESSION['return_error']) . '
            </div>';
        unset($_SESSION['return_error']);
    }
    ?>
    </div>
    
    <hr class="mb-4">
    
    <!-- Grid layout for orders -->
    <div class="orders-container py-4">
      <div class="row justify-content-start">
        <?php 
        // Use prepared statement for orders query
        $stmt = $conn->prepare("SELECT m.*, 
            CASE 
              WHEN m.status = ? THEN 0
              ELSE 1
            END AS priority_order
           FROM myorder m 
           WHERE m.farmerid = ? 
           GROUP BY m.orderid
           ORDER BY priority_order ASC, m.orderid DESC");
        $return_pending = ORDER_RETURN_PENDING;
        $stmt->bind_param("si", $return_pending, $farmerid);
        $stmt->execute();
        $query_result = $stmt->get_result();
        $stmt->close();
        
        while ($result = $query_result->fetch_assoc()) { 
          $current_status = $result["status"];
          $orderid = $result["orderid"];
        ?>
          <!-- Make order blocks wider with 2 per row (6 columns each) -->
          <div class="col-12 col-md-6">
            <div class="card shadow-sm mb-4">
              <div class="card-title">
                <h3 id="orderid"><b>ORDER id : <?php echo htmlspecialchars($orderid); ?> </b></h3>
              </div>
              <div class="card-body">
              <?php if ($current_status == ORDER_RETURN_PENDING) { ?>
                <div class="alert alert-warning">
                  <strong>Return Request Pending</strong>
                  <?php
                    // Use prepared statement for return query
                    $return_stmt = $conn->prepare("SELECT m.*, u.name as customer_name, u.userid 
                      FROM myorder m
                      JOIN users u ON m.userid = u.userid
                      WHERE m.orderid = ? 
                      AND m.farmerid = ? 
                      AND m.status = ? LIMIT 1");
                    $return_stmt->bind_param("iis", $orderid, $farmerid, $return_pending);
                    $return_stmt->execute();
                    $return_data = $return_stmt->get_result()->fetch_assoc();
                    $return_stmt->close();
                    
                    if ($return_data) {
                      $userid = $return_data["userid"];
                      $customer_name = $return_data["customer_name"];
                      $prodid = $return_data["prodid"];
                      $return_reason = isset($return_data["return_reason"]) ? $return_data["return_reason"] : "No reason provided";
                      $return_date = isset($return_data["return_date"]) ? date("d/m/Y", strtotime($return_data["return_date"])) : "Unknown date";
                      
                      echo '<p>Customer: <strong>' . htmlspecialchars($customer_name) . '</strong><br>';
                      echo 'Reason: <strong>' . htmlspecialchars($return_reason) . '</strong><br>';
                      echo 'Return request date: ' . htmlspecialchars($return_date) . '</p>';
                      
                      echo '<div class="row mt-3">
                              <div class="col-md-6">
                                <form method="POST" action="" style="display: inline;">
                                  <input type="hidden" name="orderid" value="' . htmlspecialchars($orderid) . '">
                                  <input type="hidden" name="prodid" value="' . htmlspecialchars($prodid) . '">
                                  <input type="hidden" name="userid" value="' . htmlspecialchars($userid) . '">
                                  <button type="submit" class="btn btn-success btn-sm" name="direct_approve" value="1">
                                    <i class="fa fa-check"></i> Approve Return
                                  </button>
                                </form>
                              </div>
                              <div class="col-md-6">
                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal' . htmlspecialchars($orderid) . htmlspecialchars($prodid) . '">
                                  <i class="fa fa-times"></i> Reject Return
                                </button>
                              </div>
                            </div>';
                      
                      // Reject modal
                      echo '<div class="modal fade" id="rejectModal' . htmlspecialchars($orderid) . htmlspecialchars($prodid) . '" tabindex="-1" role="dialog">
                              <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Reject Return Request</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                    </button>
                                  </div>
                                  <form method="POST" action="">
                                    <div class="modal-body">
                                      <input type="hidden" name="orderid" value="' . htmlspecialchars($orderid) . '">
                                      <input type="hidden" name="prodid" value="' . htmlspecialchars($prodid) . '">
                                      <input type="hidden" name="userid" value="' . htmlspecialchars($userid) . '">
                                      <div class="form-group">
                                        <label><strong>Please select a reason for rejecting return:</strong></label>
                                        <div class="custom-control custom-radio mt-2">
                                          <input type="radio" id="reject_reason1_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '" name="reject_reason" value="The return period has expired" class="custom-control-input" required>
                                          <label class="custom-control-label" for="reject_reason1_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '">The return period has expired</label>
                                        </div>
                                        <div class="custom-control custom-radio mt-2">
                                          <input type="radio" id="reject_reason2_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '" name="reject_reason" value="Product appears to be used/consumed" class="custom-control-input">
                                          <label class="custom-control-label" for="reject_reason2_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '">Product appears to be used/consumed</label>
                                        </div>
                                        <div class="custom-control custom-radio mt-2">
                                          <input type="radio" id="reject_reason3_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '" name="reject_reason" value="Product quality was as described" class="custom-control-input">
                                          <label class="custom-control-label" for="reject_reason3_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '">Product quality was as described</label>
                                        </div>
                                        <div class="custom-control custom-radio mt-2">
                                          <input type="radio" id="reject_reason4_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '" name="reject_reason" value="Other" class="custom-control-input">
                                          <label class="custom-control-label" for="reject_reason4_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '">Other (please specify)</label>
                                        </div>
                                        <textarea id="other_reject_reason_' . htmlspecialchars($orderid) . '_' . htmlspecialchars($prodid) . '" name="other_reject_reason" class="form-control mt-2" style="display: none;" placeholder="Please specify reason"></textarea>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                      <button type="submit" class="btn btn-danger" name="direct_reject">Reject Return</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>';
                    } else {
                      echo '<p class="text-danger">Error: Could not fetch return request details.</p>';
                    }
                  ?>
                </div>
              <?php } ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Use prepared statement for order items
                      $items_stmt = $conn->prepare("SELECT * FROM myorder WHERE orderid = ? AND farmerid = ?");
                      $items_stmt->bind_param("ii", $orderid, $farmerid);
                      $items_stmt->execute();
                      $items_result = $items_stmt->get_result();
                      $items_stmt->close();

                      while ($rows = $items_result->fetch_assoc()) {
                          $prodid = $rows["prodid"];
                          
                          // Use prepared statement for product details
                          $prod_stmt = $conn->prepare("SELECT prodname FROM product WHERE prodid = ?");
                          $prod_stmt->bind_param("i", $prodid);
                          $prod_stmt->execute();
                          $prod_result = $prod_stmt->get_result();
                          $row1 = $prod_result->fetch_assoc();
                          $prod_stmt->close();
                          
                          $name = $row1["prodname"];
                          $quant = $rows["quantity"];
                          $amount = $rows["amount"];
                          $status = $rows["status"];
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td><?php echo htmlspecialchars($quant); ?></td>
                        <td><?php echo htmlspecialchars($amount); ?></td>
                      </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>

                <!-- Display Status Label -->
                <div class="mt-3 p-2" style="background-color: #f8f9fa; border-radius: 5px;">
                  <strong>Order Status: </strong>
                  <span class="badge 
                    <?php 
                      // Check if this is a delivered order with rejected return
                      $has_rejected_return = false;
                      if ($current_status == ORDER_DELIVERED) {
                        $return_check = $conn->prepare("SELECT return_status FROM myorder WHERE orderid = ? AND farmerid = ? AND return_status = 2 LIMIT 1");
                        $return_check->bind_param("ii", $orderid, $farmerid);
                        $return_check->execute();
                        $return_result = $return_check->get_result();
                        $has_rejected_return = ($return_result->num_rows > 0);
                        $return_check->close();
                      }
                    
                      if ($current_status == ORDER_NOT_ACCEPTED) echo "badge-secondary";
                      elseif ($current_status == ORDER_ACCEPTED) echo "badge-primary";
                      elseif ($current_status == ORDER_DISPATCHED) echo "badge-info";
                      elseif ($current_status == ORDER_IN_TRANSIT) echo "badge-primary";
                      elseif ($current_status == ORDER_DELIVERED && $has_rejected_return) echo "badge-warning";
                      elseif ($current_status == ORDER_DELIVERED) echo "badge-success";
                      elseif ($current_status == ORDER_RETURN_PENDING) echo "badge-warning";
                      elseif ($current_status == ORDER_RETURNED) echo "badge-dark";
                      elseif ($current_status == ORDER_CANCELLED) echo "badge-danger";
                      else echo "badge-secondary";
                    ?>" style="font-size: 14px; padding: 5px 8px;">
                    <?php 
                      if ($current_status == ORDER_NOT_ACCEPTED) echo "Pending Acceptance";
                      elseif ($current_status == ORDER_ACCEPTED) echo "Accepted";
                      elseif ($current_status == ORDER_DISPATCHED) echo "Dispatched";
                      elseif ($current_status == ORDER_IN_TRANSIT) echo "In Transit";
                      elseif ($current_status == ORDER_DELIVERED && $has_rejected_return) echo "Return Rejected";
                      elseif ($current_status == ORDER_DELIVERED) echo "Delivered";
                      elseif ($current_status == ORDER_RETURN_PENDING) echo "Return Requested";
                      elseif ($current_status == ORDER_RETURNED) echo "Returned";
                      elseif ($current_status == ORDER_CANCELLED) echo "Cancelled";
                      else echo "Unknown Status (" . htmlspecialchars($current_status) . ")";
                    ?>
                  </span>
                </div>
              </div>
              <div class="card-footer">
              <?php if ($current_status == ORDER_RETURN_PENDING) { ?>
                  <!-- Handled by Approve/Reject buttons above -->
                  <span class="text-muted">Return Request</span>
              <?php } elseif ($current_status < 4) { ?>
                <!-- Actions for orders that are not yet delivered -->
                <div class="action-buttons">
                  <!-- Accept/Update Button - Separate Form -->
                  <form method="POST" action="order.php" style="display: inline-block;">
                    <button type="submit" class="btn btn-success" name="update" value="<?php echo htmlspecialchars($orderid); ?>">
                      <?php
                        if ($result["status"] == 0) {
                            echo "Accept";
                        }
                        if ($result["status"] == 1) {
                            echo "Dispatch";
                        }
                        if ($result["status"] == 2) {
                            echo "Transit";
                        }
                        if ($result["status"] == 3) {
                            echo "Delivered";
                        }
                      ?>
                    </button>
                  </form>
                  
                  <!-- Cancel Button - Just a button to open modal -->
                  <button type="button" class="btn btn-danger ml-2" data-toggle="modal" data-target="#cancelModal<?php echo htmlspecialchars($orderid); ?>">Cancel</button>
                </div>
                
                <!-- Cancel Order Modal - Completely separate form -->
                <div class="modal fade" id="cancelModal<?php echo htmlspecialchars($orderid); ?>" tabindex="-1" role="dialog">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Cancel Order #<?php echo htmlspecialchars($orderid); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <form method="POST" action="order.php" id="cancelForm<?php echo htmlspecialchars($orderid); ?>">
                        <div class="modal-body">
                          <div class="form-group">
                            <label><strong>Please select a reason for cancellation:</strong></label>
                            <div class="custom-control custom-radio mt-2">
                              <input type="radio" id="reason1_<?php echo htmlspecialchars($orderid); ?>" name="cancel_reason" value="Sorry, at this time can't able to deliver to your address" class="custom-control-input" required>
                              <label class="custom-control-label" for="reason1_<?php echo htmlspecialchars($orderid); ?>">Sorry, at this time can't able to deliver to your address</label>
                            </div>
                            <div class="custom-control custom-radio mt-2">
                              <input type="radio" id="reason2_<?php echo htmlspecialchars($orderid); ?>" name="cancel_reason" value="We are unable to reach out to you" class="custom-control-input">
                              <label class="custom-control-label" for="reason2_<?php echo htmlspecialchars($orderid); ?>">We are unable to reach out to you</label>
                            </div>
                            <div class="custom-control custom-radio mt-2">
                              <input type="radio" id="reason3_<?php echo htmlspecialchars($orderid); ?>" name="cancel_reason" value="Items are out of stock" class="custom-control-input">
                              <label class="custom-control-label" for="reason3_<?php echo htmlspecialchars($orderid); ?>">Items are out of stock</label>
                            </div>
                            <div class="custom-control custom-radio mt-2">
                              <input type="radio" id="reason4_<?php echo htmlspecialchars($orderid); ?>" name="cancel_reason" value="Other" class="custom-control-input">
                              <label class="custom-control-label" for="reason4_<?php echo htmlspecialchars($orderid); ?>">Other (please specify)</label>
                            </div>
                            <textarea id="other_reason_<?php echo htmlspecialchars($orderid); ?>" name="other_reason" class="form-control mt-2" style="display: none;" placeholder="Please specify reason"></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                          <button type="submit" class="btn btn-danger" name="delete" value="<?php echo htmlspecialchars($orderid); ?>">Cancel Order</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              <?php } else { ?>
                <div class="text-muted">Order Completed</div>
              <?php } ?>
              </div>
            </div>
          </div>
        <?php } ?>
      </div> <!-- End of row -->
    </div> <!-- End of orders-container -->
  </div> <!-- End of container -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
    integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
  </script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
    integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
  </script>
  
  <script>
    // Auto-dismiss alert messages after 5 seconds
    $(document).ready(function() {
      // Set timeout for alerts to fade out - only target alerts in the alertMessages container
      setTimeout(function() {
        $('#alertMessages .alert').alert('close');
      }, 5000); // 5000 milliseconds = 5 seconds
      
      // Prevent rejection modals from closing when clicking inside them
      $('.modal').on('click', function(e) {
        if ($(e.target).closest('.modal-content').length) {
          e.stopPropagation();
        }
      });
      
      // Show/hide other reason textarea based on radio selection for cancel
      $('input[name="cancel_reason"]').change(function() {
        const orderId = $(this).attr('id').split('_')[1];
        if ($(this).val() === 'Other') {
          $('#other_reason_' + orderId).show();
          $('#other_reason_' + orderId).attr('required', 'required');
        } else {
          $('#other_reason_' + orderId).hide();
          $('#other_reason_' + orderId).removeAttr('required');
        }
      });
      
      // Show/hide other reason textarea based on radio selection for reject return
      $('input[name="reject_reason"]').change(function() {
        const idParts = $(this).attr('id').split('_');
        const orderId = idParts[2];
        const prodId = idParts[3];
        if ($(this).val() === 'Other') {
          $('#other_reject_reason_' + orderId + '_' + prodId).show();
          $('#other_reject_reason_' + orderId + '_' + prodId).attr('required', 'required');
        } else {
          $('#other_reject_reason_' + orderId + '_' + prodId).hide();
          $('#other_reject_reason_' + orderId + '_' + prodId).removeAttr('required');
        }
      });
    });
  </script>
</body>

</html>