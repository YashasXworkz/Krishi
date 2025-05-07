<?php
include "../config.php";

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../index.php");
    exit;
}

// Check if return request is submitted
if (isset($_POST["return_order"])) {
    $orderid = $_POST["orderid"];
    $farmerid = $_POST["farmerid"];
    $prodid = $_POST["prodid"];
    $reason = $_POST["reason"];
    
    // Get user ID
    $username = $_SESSION["username"];
    $user_query = mysqli_query($conn, "SELECT userid FROM users WHERE username='$username'");
    $user_row = mysqli_fetch_array($user_query);
    $userid = $user_row["userid"];
    
    // First check if this order has a rejected return
    $check_rejected = mysqli_query($conn, 
        "SELECT * FROM myorder 
        WHERE orderid='$orderid' 
        AND farmerid='$farmerid' 
        AND userid='$userid'
        AND return_status=2" // Return status 2 is rejected
    );
    
    if (mysqli_num_rows($check_rejected) > 0) {
        // This order already has a rejected return, don't allow another return
        header("Location: ../../profile.php?return_error=rejected_return#myorders");
        exit;
    }
    
    // Check if order is valid and can be returned (only delivered orders can be returned)
    $check_order = mysqli_query($conn, 
        "SELECT *, TIMESTAMPDIFF(HOUR, orderdate, NOW()) as hours_since_delivery 
        FROM myorder 
        WHERE orderid='$orderid' 
        AND farmerid='$farmerid' 
        AND prodid='$prodid' 
        AND userid='$userid'
        AND status=" . ORDER_DELIVERED // Status 4 is delivered
    );
    
    if (mysqli_num_rows($check_order) > 0) {
        $order_data = mysqli_fetch_array($check_order);
        
        // Check if order is within the 24-hour return window
        if ($order_data['hours_since_delivery'] > 24) {
            // Return period expired
            header("Location: ../../profile.php?return_error=time_expired#myorders");
            exit;
        }
        
        // Build the update query dynamically
        $update_parts = [];
        $update_parts[] = "status=" . ORDER_RETURN_PENDING;
        $update_parts[] = "return_reason='".$conn->real_escape_string($reason)."'";
        $update_parts[] = "return_date=NOW()";
        
        // Check if return_status column exists
        $check_col_status = mysqli_query($conn, "SHOW COLUMNS FROM myorder LIKE 'return_status'");
        if ($check_col_status && mysqli_num_rows($check_col_status) > 0) {
            $update_parts[] = "return_status=0"; // Set initial return status to Pending
        }
        
        // Update ALL products in this order with the same orderid and farmerid
        $sql_update = "UPDATE myorder SET " . implode(", ", $update_parts) . 
                      " WHERE orderid='$orderid' 
                        AND farmerid='$farmerid' 
                        AND userid='$userid'
                        AND status=" . ORDER_DELIVERED;
                        
        $update_status = mysqli_query($conn, $sql_update);
        
        if ($update_status) {
            // Redirect with success message
            header("Location: ../../profile.php?return_success=1#myorders");
            exit;
        } else {
            // Return failed
            header("Location: ../../profile.php?return_error=update_failed#myorders");
            exit;
        }
    } else {
        // Invalid order for return
        header("Location: ../../profile.php?return_error=invalid_order#myorders");
        exit;
    }
} else {
    // Direct access not allowed
    header("Location: ../../profile.php#myorders");
    exit;
}
?> 