<?php
session_start();
include "config.php";

$farmer = $_SESSION["username"];
$query = mysqli_query($conn, "SELECT * FROM farmer WHERE username='$farmer'");
$row = mysqli_fetch_array($query);
$fid = $row["farmerid"];

if (isset($_POST["delete"])) {
    $id = $_POST["delete"];
    
    // First check if this product is in any customer's cart
    $check_cart = mysqli_query($conn, "SELECT * FROM cart WHERE prodid = $id AND farmerid = $fid");
    
    if (mysqli_num_rows($check_cart) > 0) {
        // Option 1: Set flag to 0 in cart (if flag exists)
        mysqli_query($conn, "UPDATE cart SET flag = 0 WHERE prodid = $id AND farmerid = $fid");
        
        // Display a message instead of deleting
        echo "<script>alert('This product is in customers\' carts. It has been marked as unavailable but cannot be completely removed.');</script>";
        echo "<script>window.location.href = 'myshop.php';</script>";
        exit();
    } else {
        // If not in any cart, delete from myshop
        $sql = "DELETE FROM myshop WHERE prodid = $id AND farmerid = $fid";
        
        if (mysqli_query($conn, $sql)) {
            mysqli_close($conn);
            header("Location: myshop.php");
            exit();
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
        }
    }
}

if (isset($_POST["update"])) {
    $id = $_POST["update"];
    $quantity_field = "quantity_" . $id;
    $price_field = "price_" . $id;
    $quantity = isset($_POST[$quantity_field]) ? trim($_POST[$quantity_field]) : '';
    $price = isset($_POST[$price_field]) ? trim($_POST[$price_field]) : '';
    
    // Validation
    $errors = [];
    if (!empty($quantity) && (!is_numeric($quantity) || $quantity < 0)) {
        $errors[] = "Quantity must be a positive number";
    }
    if (!empty($price) && (!is_numeric($price) || $price < 0)) {
        $errors[] = "Price must be a positive number";
    }
    
    if (!empty($errors)) {
        echo "<script>alert('Error: " . implode("\\n", $errors) . "');</script>";
        echo "<script>window.location.href = 'myshop.php';</script>";
        exit();
    }
    
    // Prepare the SQL update parts
    $updates = [];
    if (!empty($quantity)) {
        $updates[] = "quantity = '$quantity'";
    }
    if (!empty($price)) {
        $updates[] = "price = '$price'";
    }
    
    // Only update if we have values to update
    if (!empty($updates)) {
        $sql = "UPDATE myshop SET " . implode(", ", $updates) . " WHERE prodid = $id AND farmerid = $fid";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Product updated successfully!');</script>";
            echo "<script>window.location.href = 'myshop.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating product: " . mysqli_error($conn) . "');</script>";
            echo "<script>window.location.href = 'myshop.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('No changes provided for update');</script>";
        echo "<script>window.location.href = 'myshop.php';</script>";
        exit();
    }
}