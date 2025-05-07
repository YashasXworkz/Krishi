<?php
include "config.php";

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
}

if (isset($_POST["add"])) {
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

    echo $fname;

    $sql2 = mysqli_query($conn, "SELECT * FROM farmer WHERE username='$fname'");
    $row = mysqli_fetch_array($sql2);
    $fid = $row["farmerid"];

    // First check if the product exists in the product table
    $check_product = mysqli_query(
        $conn,
        "SELECT * FROM product WHERE prodid='$pid'"
    );
    if (mysqli_num_rows($check_product) == 0) {
        echo "Product with ID $pid does not exist";
        exit();
    }

    // Use prepared statement to prevent SQL injection
    $sql1 =
        "INSERT INTO myshop (prodid, farmerid, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql1);
    mysqli_stmt_bind_param($stmt, "iiss", $pid, $fid, $quantity, $price);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        echo "Error: " . mysqli_error($conn);
    } else {
        header("Location: welcomefarmer.php");
        exit();
    }
}
?>