<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

if (isset($_POST["add"])) {
    try {
        // Get the product ID from the hidden field
        $pid = $_POST["proid"];
        $fname = $_SESSION["username"];

        // Match the field names from the form
        $quantity = $_POST["qty"];
        $price = $_POST["price"];

        // Check if quantity and price are provided
        if (empty($quantity) || empty($price)) {
            echo "Quantity and price must be provided";
            exit();
        }

        // Make sure connection is valid
        if (!$conn) {
            echo "Database connection failed: " . mysqli_connect_error();
            exit();
        }

        $sql2 = mysqli_query($conn, "SELECT * FROM farmer WHERE username='$fname'");
        if (!$sql2) {
            echo "Query error: " . mysqli_error($conn);
            exit();
        }
        
        $row = mysqli_fetch_array($sql2);
        if (!$row) {
            echo "No farmer found with username: $fname";
            exit();
        }
        
        $fid = $row["farmerid"];

        // First check if the product exists in the product table
        $check_product = mysqli_query(
            $conn,
            "SELECT * FROM product WHERE prodid='$pid'"
        );
        if (!$check_product) {
            echo "Query error: " . mysqli_error($conn);
            exit();
        }
        
        if (mysqli_num_rows($check_product) == 0) {
            echo "Product with ID $pid does not exist";
            exit();
        }

        // Check if this farmer already has this product in their shop
        $check_duplicate = mysqli_query(
            $conn,
            "SELECT * FROM myshop WHERE prodid='$pid' AND farmerid='$fid'"
        );
        
        if (!$check_duplicate) {
            echo "Query error: " . mysqli_error($conn);
            exit();
        }
        
        if (mysqli_num_rows($check_duplicate) > 0) {
            echo "<script>
                alert('This product is already in your shop! You can update it from the My Shop section.');
                window.location.href = 'products.php';
            </script>";
            exit();
        }

        // Use prepared statement to prevent SQL injection
        $sql1 = "INSERT INTO myshop (prodid, farmerid, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql1);
        
        if (!$stmt) {
            echo "Prepare failed: " . mysqli_error($conn);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, "iiss", $pid, $fid, $quantity, $price);
        $result = mysqli_stmt_execute($stmt);

        if (!$result) {
            echo "Error: " . mysqli_stmt_error($stmt);
        } else {
            // Use JavaScript alert and redirect
            echo "<script>
                alert('Product successfully added to your shop!');
                window.location.href = 'products.php';
            </script>";
            exit();
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage();
    }
}
?>