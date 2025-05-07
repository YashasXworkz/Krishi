<?php
include "config.php";

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
}

if (isset($_POST["confirm"])) {
    $cname = $_SESSION["username"];
    $sql = mysqli_query($conn, "SELECT * FROM users WHERE username='$cname'");
    $row = mysqli_fetch_array($sql);
    $cid = $row["userid"];

    $sql2 = mysqli_query(
        $conn,
        "SELECT * from myorder where orderid=(select max(orderid) FROM myorder)"
    );
    $row2 = mysqli_fetch_array($sql2);
    $orderid = "";
    if ($row2) {
        $orderid = $row2["orderid"] + 1; //if already exists
    } else {
        $orderid = 1; //first order
    }
    $results = mysqli_query(
        $conn,
        "SELECT * from cart where userid=$cid and flag=1"
    );

    // Check if coupon was applied in the cart session
    $coupon_code = isset($_SESSION["applied_coupon"]) ? $_SESSION["applied_coupon"] : null;
    $discount_percent = isset($_SESSION["discount_percent"]) ? $_SESSION["discount_percent"] : 0;

    while ($row = mysqli_fetch_array($results)) {
        $pid = $row["prodid"];
        $fid = $row["farmerid"];
        $sql1 = mysqli_query(
            $conn,
            "SELECT * FROM myshop WHERE prodid='$pid' and farmerid='$fid'"
        );
        $row1 = mysqli_fetch_array($sql1);
        $price = $row1["price"];
        $quantity = $row["cart_quantity"];
        $original_total = $price * $quantity;
        
        // Apply discount if coupon was used
        $total = $original_total;
        if ($coupon_code && $discount_percent > 0) {
            $total = $original_total - ($original_total * ($discount_percent / 100));
        }

        $sql3 = mysqli_query(
            $conn,
            "INSERT into myorder (orderid, userid, prodid, farmerid, amount, original_amount, coupon_code, status, quantity, orderdate, return_reason, return_date, return_status, return_rejection_reason) 
            values('$orderid', '$cid', '$pid', '$fid', '$total', '$original_total', " . ($coupon_code ? "'$coupon_code'" : "NULL") . ", 0, '$quantity', now(), NULL, NULL, NULL, NULL)"
        );
        if (mysqli_error($conn)) {
            echo "Errorcode: " . mysqli_errno($conn);
        }
    }

    $results = mysqli_query(
        $conn,
        "SELECT * from cart where userid=$cid and flag=1"
    );
    while ($row = mysqli_fetch_array($results)) {
        $pid = $row["prodid"];
        $fid = $row["farmerid"];
        $sql4 = "UPDATE cart SET cart_quantity=0, flag=0 WHERE userid='$cid' and farmerid='$fid' and prodid='$pid'";
        mysqli_query($conn, $sql4);
        if (mysqli_error($conn)) {
            echo "Errorcode: " . mysqli_errno($conn);
        }
    }

    // Clear the coupon session variables
    unset($_SESSION["applied_coupon"]);
    unset($_SESSION["discount_percent"]);

    header("Location: ../profile.php#myorders");
}

?>