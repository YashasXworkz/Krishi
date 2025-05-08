<?php
/*
This file contains database configuration assuming you are running mysql using user "root" and password ""
*/

// define("DB_SERVER", "localhost");
// define("DB_USERNAME", "root");
// define("DB_PASSWORD", "");
define("DB_SERVER", "sql205.infinityfree.com");
define("DB_USERNAME", "if0_38925197");
define("DB_PASSWORD", "TVkuueXFUidW4g"); // Replace with your actual vPanel password

// Try connecting to the Database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
//Check the connection
if ($conn == false) {
    die("Error: Cannot connect");
}

// Create database - commented out for InfinityFree deployment
// $sql = "CREATE DATABASE IF NOT EXISTS krishi";
// if (mysqli_query($conn, $sql)) {
//     // Database created successfully or already exists
// } else {
//     echo "Error creating database: " . mysqli_error($conn);
// }
mysqli_close($conn);

//connecting again
//define("DB_NAME", "krishi");
define("DB_NAME", "if0_38925197_krishi");
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
//Check the connection
if ($conn == false) {
    die("Error: Cannot connect");
}

// Table creation commented out for InfinityFree deployment - tables already exist
// $sql1 = "CREATE TABLE IF NOT EXISTS farmer (
//   farmerid int(11) NOT NULL AUTO_INCREMENT,
//   username varchar(50) NOT NULL,
//   password varchar(255) NOT NULL,
//   name varchar(100) NOT NULL,
//   address varchar(100) NOT NULL,
//   mobile text NOT NULL,
//   gender text NOT NULL,
//   PRIMARY KEY (farmerid)
// )";
// if (!mysqli_query($conn, $sql1)) {
//     echo "Error creating table farmer: " . mysqli_error($conn);
// }

// $sql2 = "CREATE TABLE IF NOT EXISTS product (
//   prodid int(11) NOT NULL AUTO_INCREMENT primary key,
//   prodname varchar(50) NOT NULL,
//   prodtype varchar(70) NOT NULL
// )";
// if (!mysqli_query($conn, $sql2)) {
//     echo "Error creating table product: " . mysqli_error($conn);
// }

// $sql3 = "CREATE TABLE IF NOT EXISTS users (
//   userid int(11) NOT NULL AUTO_INCREMENT primary key,
//   username varchar(50) NOT NULL,
//   password varchar(255) NOT NULL,
//   name varchar(100) NOT NULL,
//   address varchar(100) NOT NULL,
//   mobile text NOT NULL,
//   gender text NOT NULL,
//   created_at datetime NOT NULL DEFAULT current_timestamp()
// )";
// if (!mysqli_query($conn, $sql3)) {
//     echo "Error creating table users: " . mysqli_error($conn);
// }

// $sql4 = "CREATE TABLE IF NOT EXISTS myshop (
//   prodid int(11),
//   farmerid int(11),
//   quantity varchar(10) NOT NULL,
//   price varchar(10) NOT NULL,
//   foreign key(prodid) references product(prodid),
//   foreign key(farmerid) references farmer(farmerid),
//   CONSTRAINT myshopid PRIMARY KEY (prodid, farmerid)
// )";
// if (!mysqli_query($conn, $sql4)) {
//     echo "Error creating table myshop: " . mysqli_error($conn);
// }

// $sql6 = "CREATE TABLE IF NOT EXISTS cart (
//   userid int(11),
//   prodid int(11),
//   farmerid int(11) ,
//   cart_quantity int(11) NOT NULL,
//   flag boolean not null default 1,
//   foreign key(prodid, farmerid) references myshop(prodid, farmerid),
//   foreign key(userid) references users(userid),
//   CONSTRAINT cartid PRIMARY KEY (prodid, farmerid, userid)
//   -- primary key(prodid, farmerid, userid) as cartid
// )";
// if (!mysqli_query($conn, $sql6)) {
//     echo "Error creating table cart: " . mysqli_error($conn);
// }

// $sql7 = "CREATE TABLE IF NOT EXISTS myorder(
// 	orderid int(11) not null,
// 	userid int(11),
// 	prodid int(11),
// 	farmerid int(11),
//   amount int(11),
// 	status int(11) not null DEFAULT 0,
//   quantity int(11),
//   orderdate datetime default now(),
//   return_reason varchar(255) DEFAULT NULL,
//   return_date datetime DEFAULT NULL,
//   return_status tinyint(1) DEFAULT NULL,
//   return_rejection_reason varchar(255) DEFAULT NULL,
// 	foreign key(prodid, farmerid, userid) references cart(prodid, farmerid, userid),
// 	primary key(orderid, prodid, farmerid, userid)
// )";
// if (!mysqli_query($conn, $sql7)) {
//     echo "Error creating table order: " . mysqli_error($conn);
// }

// // Add return_status and return_rejection_reason columns if they don't exist
// $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM myorder LIKE 'return_status'");
// if (mysqli_num_rows($check_columns) == 0) {
//     $add_columns = "ALTER TABLE myorder 
//                      ADD COLUMN return_status TINYINT(1) DEFAULT NULL,
//                      ADD COLUMN return_rejection_reason VARCHAR(255) DEFAULT NULL";
//     if (!mysqli_query($conn, $add_columns)) {
//         echo "Error adding return status columns: " . mysqli_error($conn);
//     }
// }

// Check if we need to add coupon-related columns
// $check_coupon_columns = mysqli_query($conn, "SHOW COLUMNS FROM myorder LIKE 'original_amount'");
// if (mysqli_num_rows($check_coupon_columns) == 0) {
//     $add_coupon_columns = "ALTER TABLE myorder 
//                            ADD COLUMN original_amount DECIMAL(10,2) DEFAULT NULL,
//                            ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL";
//     if (!mysqli_query($conn, $add_coupon_columns)) {
//         echo "Error adding coupon columns: " . mysqli_error($conn);
//     }
// }

// Check if cancellation_reason column exists
// $check_cancel_column = mysqli_query($conn, "SHOW COLUMNS FROM myorder LIKE 'cancellation_reason'");
// if (mysqli_num_rows($check_cancel_column) == 0) {
//     $add_cancel_column = "ALTER TABLE myorder ADD COLUMN cancellation_reason VARCHAR(255) DEFAULT NULL";
//     if (!mysqli_query($conn, $add_cancel_column)) {
//         echo "Error adding cancellation reason column: " . mysqli_error($conn);
//     }
// }

// Define order status constants for reference
define("ORDER_NOT_ACCEPTED", 0);
define("ORDER_ACCEPTED", 1);
define("ORDER_DISPATCHED", 2);
define("ORDER_IN_TRANSIT", 3);
define("ORDER_DELIVERED", 4);
define("ORDER_RETURN_PENDING", 5);
define("ORDER_RETURNED", 6);
define("ORDER_CANCELLED", 7);

// $sql8 = "INSERT IGNORE INTO product (prodid, prodname, prodtype) VALUES
// (1, 'Potato', 'Vegetable'),
// (2, 'Tomato', 'Vegetable'),
// (3, 'Onion', 'Vegetable'),
// (4, 'Carrot', 'Vegetable'),
// (5, 'Capsicum', 'Vegetable'),
// (6, 'Spinach', 'Vegetable'),
// (7, 'Apple', 'Fruit'),
// (8, 'Orange', 'Fruit'),
// (9, 'Banana', 'Fruit'),
// (10, 'Wheat', 'Cereal'),
// (11, 'Rice', 'Cereal'),
// (12, 'Maize', 'Cereal')";
// if (!mysqli_query($conn, $sql8)) {
//     echo "Error inserting in table product: " . mysqli_error($conn);
// }

//200 - 10%, 300-15$, 500-20%
// $sql9 = "CREATE TABLE IF NOT EXISTS coupon(
//   couponcode VARCHAR(20),
//   discount int(11) not null,
//   pricelimit int(11) not null,
//   primary key(couponcode)
// )";
// if (!mysqli_query($conn, $sql9)) {
//     echo "Error creating table order: " . mysqli_error($conn);
// }

// $sql10 = "INSERT IGNORE INTO coupon (couponcode, discount, pricelimit) VALUES
// ('KRISHI200', 10, 200),
// ('KRISHI300', 15, 300),
// ('KRISHI500', 20, 500)";
// if (!mysqli_query($conn, $sql10)) {
//     echo "Error inserting in table product: " . mysqli_error($conn);
// }

// Create password_resets table for the forgot password feature
// $sql11 = "CREATE TABLE IF NOT EXISTS password_resets (
//   id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//   email varchar(100) NOT NULL,
//   token varchar(255) NOT NULL,
//   expiry datetime NOT NULL,
//   user_type varchar(20) NOT NULL,
//   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//   INDEX (email, user_type)
// )";
// if (!mysqli_query($conn, $sql11)) {
//     echo "Error creating table password_resets: " . mysqli_error($conn);
// }

?>