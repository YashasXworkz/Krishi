<?php
include "../config.php";

// Enable error reporting for debugging (comment out in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to capture any errors
ob_start();

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Verify user is a farmer
$username = $_SESSION["username"];
$query = mysqli_query($conn, "SELECT * FROM farmer WHERE username='$username'");
if (mysqli_num_rows($query) == 0) {
    header("location: ../welcomefarmer.php");
    exit;
}
$row = mysqli_fetch_array($query);
$farmerid = $row["farmerid"];

// Emergency debug - uncomment if needed to see all POST data
/* 
echo "<h1>Debug: POST Data</h1>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
exit; 
*/

// Process return approval
if (isset($_POST["approve_return"])) {
    try {
        // Get form data
        $orderid = isset($_POST["orderid"]) ? $_POST["orderid"] : 0;
        $prodid = isset($_POST["prodid"]) ? $_POST["prodid"] : 0;
        $userid = isset($_POST["userid"]) ? $_POST["userid"] : 0;
        
        // Validate input data
        if (!$orderid || !$prodid || !$userid) {
            throw new Exception("Missing data: orderid=$orderid, prodid=$prodid, userid=$userid");
        }
        
        // Check if the return request exists and belongs to this farmer
        $check_query = "SELECT * FROM myorder 
            WHERE orderid='$orderid' 
            AND farmerid='$farmerid' 
            AND userid='$userid'
            AND status=" . ORDER_RETURN_PENDING;
        
        $check_return = mysqli_query($conn, $check_query);
        
        if (!$check_return) {
            throw new Exception("Database error in check_query: " . mysqli_error($conn));
        }
        
        if (mysqli_num_rows($check_return) > 0) {
            // Update ALL items in this order to status 6 (returned/completed)
            $update_query = "UPDATE myorder 
                SET status=" . ORDER_RETURNED . ", 
                    return_status=1
                WHERE orderid='$orderid' 
                AND farmerid='$farmerid' 
                AND userid='$userid'
                AND status=" . ORDER_RETURN_PENDING;
                
            $update_status = mysqli_query($conn, $update_query);
            
            if (!$update_status) {
                throw new Exception("Database error in update_query: " . mysqli_error($conn));
            }
            
            // For each product being returned, update inventory
            $products_query = "SELECT prodid, quantity FROM myorder 
                WHERE orderid='$orderid' 
                AND farmerid='$farmerid' 
                AND userid='$userid'
                AND status=" . ORDER_RETURNED;
                
            $products_result = mysqli_query($conn, $products_query);
            
            if (!$products_result) {
                throw new Exception("Database error in products_query: " . mysqli_error($conn));
            }
            
            $updated_inventory = false;
            
            while ($product = mysqli_fetch_array($products_result)) {
                $returned_prodid = $product["prodid"];
                $returned_quantity = intval($product["quantity"]);
                
                // Return the quantity to farmer's inventory
                $inventory_query = "UPDATE myshop 
                    SET quantity = CAST(quantity AS UNSIGNED) + $returned_quantity 
                    WHERE farmerid='$farmerid' 
                    AND prodid='$returned_prodid'";
                    
                $inventory_result = mysqli_query($conn, $inventory_query);
                
                if (!$inventory_result) {
                    throw new Exception("Database error in inventory_query: " . mysqli_error($conn));
                }
                
                if (mysqli_affected_rows($conn) > 0) {
                    $updated_inventory = true;
                }
            }
            
            // Success! Redirect back to orders page
            header("Location: ../order.php?return_approved=1&orderid=$orderid");
            exit;
        } else {
            throw new Exception("No pending return found for orderid=$orderid, farmerid=$farmerid, userid=$userid");
        }
    } catch (Exception $e) {
        // Display error for debugging
        echo "<h2>Error in Approve Return:</h2>";
        echo "<p style='color:red'>" . $e->getMessage() . "</p>";
        echo "<p><a href='../order.php'>Return to Order Page</a></p>";
        exit;
    }
}

// Process return rejection
if (isset($_POST["reject_return"])) {
    try {
        $orderid = $_POST["orderid"];
        $prodid = $_POST["prodid"];
        $userid = $_POST["userid"];
        $reject_reason = $_POST["reject_reason"];
        
        // Check if the return request exists and belongs to this farmer
        $check_return = mysqli_query($conn, 
            "SELECT * FROM myorder 
            WHERE orderid='$orderid' 
            AND farmerid='$farmerid' 
            AND userid='$userid'
            AND status=" . ORDER_RETURN_PENDING
        );
        
        if (!$check_return) {
            throw new Exception("Database error in check_return: " . mysqli_error($conn));
        }
        
        if (mysqli_num_rows($check_return) > 0) {
            // Update ALL items in this order to status 4 (delivered) with rejection info
            $update_query = "UPDATE myorder 
                            SET status=" . ORDER_DELIVERED . ", 
                                return_status=2,
                                return_rejection_reason='" . $conn->real_escape_string($reject_reason) . "'
                            WHERE orderid='$orderid' 
                            AND farmerid='$farmerid' 
                            AND userid='$userid'
                            AND status=" . ORDER_RETURN_PENDING;
            
            $update_status = mysqli_query($conn, $update_query);
            
            if (!$update_status) {
                throw new Exception("Database error in update_query: " . mysqli_error($conn));
            }
            
            // Success! Redirect back to orders page
            header("Location: ../order.php?return_rejected=1&orderid=$orderid");
            exit;
        } else {
            throw new Exception("No pending return found for orderid=$orderid, farmerid=$farmerid, userid=$userid");
        }
    } catch (Exception $e) {
        // Display error for debugging
        echo "<h2>Error in Reject Return:</h2>";
        echo "<p style='color:red'>" . $e->getMessage() . "</p>";
        echo "<p><a href='../order.php'>Return to Order Page</a></p>";
        exit;
    }
}

// If no action was specified
header("Location: ../order.php");
exit;
?> 