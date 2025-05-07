<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if($_SESSION["type"] == "farmer") {
        header("location: welcomefarmer.php");
    } else {
        header("location: ../customer.php");
    }
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$email_or_phone = $type = $new_password = $confirm_password = "";
$email_or_phone_err = $new_password_err = $confirm_password_err = "";
$success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email or phone number
    if(empty(trim($_POST["email_or_phone"]))) {
        $email_or_phone_err = "Please enter your email address or phone number.";
    } else {
        $email_or_phone = trim($_POST["email_or_phone"]);
        // We don't validate format here as it could be either email or phone
    }
    
    // Get user type
    $type = isset($_POST["user_type"]) ? $_POST["user_type"] : "customer";
    
    // Validate new password
    if(empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check if email/phone exists in database and update password
    if(empty($email_or_phone_err) && empty($new_password_err) && empty($confirm_password_err)) {
        if($type == "farmer") {
            $sql = "SELECT farmerid FROM farmer WHERE username = ? OR mobile = ?";
        } else {
            $sql = "SELECT userid FROM users WHERE username = ? OR mobile = ?";
        }
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $email_or_phone, $email_or_phone);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                // If email/phone exists, update password
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password in the appropriate table
                    if($type == "farmer") {
                        $update_sql = "UPDATE farmer SET password = ? WHERE username = ? OR mobile = ?";
                    } else {
                        $update_sql = "UPDATE users SET password = ? WHERE username = ? OR mobile = ?";
                    }
                    
                    if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "sss", $hashed_password, $email_or_phone, $email_or_phone);
                        
                        if(mysqli_stmt_execute($update_stmt)) {
                            $success_msg = "Your password has been reset successfully. You can now <a href='../index.php'>login</a> with your new password.";
                            
                            // Clear form inputs after successful submission
                            $email_or_phone = $new_password = $confirm_password = "";
                        } else {
                            $email_or_phone_err = "Something went wrong. Please try again later.";
                        }
                        
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    $email_or_phone_err = "No account found with that email address or phone number.";
                }
            } else {
                $email_or_phone_err = "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            margin-top: 100px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #216353;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .btn-primary {
            background-color: #216353;
            border-color: #216353;
        }
        .btn-primary:hover {
            background-color: #184d40;
            border-color: #184d40;
        }
        .back-link {
            margin-top: 15px;
            display: block;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Reset Your Password</h3>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo $success_msg; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center mb-4">Reset your password by entering your email or phone number, selecting account type, and creating a new password.</p>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group">
                                    <label>Email Address or Phone Number</label>
                                    <input type="text" name="email_or_phone" class="form-control <?php echo (!empty($email_or_phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email_or_phone; ?>">
                                    <span class="invalid-feedback"><?php echo $email_or_phone_err; ?></span>
                                </div>
                                
                                <div class="form-group">
                                    <label>Account Type</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="user_type" id="customer" value="customer" <?php echo ($type == "customer" || $type == "") ? "checked" : ""; ?>>
                                        <label class="form-check-label" for="customer">
                                            Customer
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="user_type" id="farmer" value="farmer" <?php echo ($type == "farmer") ? "checked" : ""; ?>>
                                        <label class="form-check-label" for="farmer">
                                            Farmer
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                                    <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="../index.php" class="back-link">Back to Login</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-dismiss alert messages after 10 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').alert('close');
            }, 10000);
        });
    </script>
</body>
</html> 