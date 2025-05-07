<?php
//This script will handle login
session_start();

// check if the user is already logged in
if (isset($_SESSION["username"])) {
    $msg = $_SESSION["type"];

    if (strcmp($msg, "farmer") == 0) {
        header("location: php/welcomefarmer.php");
        exit();
    } else {
        header("location: customer.php");
        exit();
    }
}
require_once "./php/config.php";

$username = $password = "";
$err = "";

// if request method is post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["farmer"])) {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        if (
            empty(trim($_POST["username"])) ||
            empty(trim($_POST["password"]))
        ) {
            $err = "Please enter username + password";
            die($err);
        }
    } else {
        $username = trim($_POST["farmername"]);
        $password = trim($_POST["fpassword"]);
        if (
            empty(trim($_POST["farmername"])) ||
            empty(trim($_POST["fpassword"]))
        ) {
            $err = "Please enter username + password";
            die($err);
        }
    }

    if (empty($err)) {
        if (!isset($_POST["farmer"])) {
            $sql =
                "SELECT userid, username, password FROM users WHERE username = ? OR mobile = ?";
        } else {
            $sql =
                "SELECT farmerid, username, password FROM farmer WHERE username = ? OR mobile = ?";
        }

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_username);
        $param_username = $username;

        // Try to execute this statement
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result(
                    $stmt,
                    $id,
                    $username,
                    $hashed_password
                );
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        // this means the password is corrct. Allow user to login
                        session_start();
                        $_SESSION["username"] = $username;
                        $_SESSION["id"] = $id;
                        $_SESSION["loggedin"] = true;

                        //Redirect user to welcome page
                        echo '<script>alert("Successful login")</script>';

                        if (!isset($_POST["farmer"])) {
                            $_SESSION["type"] = "user";
                            header("location: customer.php");
                        } else {
                            header("location: ./php/welcomefarmer.php");
                            $_SESSION["type"] = "farmer";
                        }
                    } else {
                        // Password is not valid, display a generic error message.
                        $err = "Username/Phone number or password is incorrect";
                    }
                }
            } else {
                $err = "Username/Phone number or password is incorrect";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html>

<head>
  <title>Farmer Website</title>
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
  <link rel="stylesheet" type="text/css" href="./css/indexstyle.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

</head>

<body>

  <nav class="navbar navbar-dark navbar-expand-lg fixed-top">
    <div class="container">
      <div class="navbar-header">
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
          <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
          <li class="nav-item"><a class="nav-link" href="#blogs">Blogs</a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-toggle="modal" data-target="#at-login"
              style="cursor: pointer;">Log In</a></li>
          <li class="nav-item"><a class="nav-link" href="./php/register.php">Register</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="at-login-form">
    <div class="modal fade" id="at-login" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content wrapper">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <?php 
                if(!empty($err)){
                    echo "<script>alert('$err');</script>";
                }
            ?>
            <div class="title-text">
              <div class="title login">
                Farmer Login
              </div>
              <div class="title signup">
                Customer Login
              </div>
            </div>
            <div class="form-container">
              <div class="slide-controls">
                <input type="radio" name="slide" id="login" checked>
                <input type="radio" name="slide" id="signup">
                <label for="login" class="slide login">Farmer</label>
                <label for="signup" class="slide signup">Customer</label>
                <div class="slider-tab">
                </div>
              </div>
              <div class="form-inner">
                <form action="#" class="login" method="post">
                  <div class="field">
                    <input type="text" placeholder="Email or Phone Number" name="farmername" required>
                  </div>
                  <div class="field">
                    <input type="password" placeholder="Password" name="fpassword" required>
                  </div>
                  <div class="pass-link">
                    <a href="php/forgot_password.php?type=farmer">Forgot password?</a>
                  </div>
                  <div class="field btn">
                    <div class="btn-layer">
                    </div>
                    <input type="submit" value="Sign in" name="farmer">
                  </div>
                  <div class="signup-link">
                    Not a member? <a href="./php/register.php">Signup now</a>
                  </div>
                </form>
                <form action="#" class="login" method="post">
                  <div class="field">
                    <input type="text" placeholder="Email or Phone Number" name="username" required>
                  </div>
                  <div class="field">
                    <input type="password" placeholder="Password" name="password" required>
                  </div>
                  <div class="pass-link">
                    <a href="php/forgot_password.php?type=customer">Forgot password?</a>
                  </div>
                  <div class="field btn">
                    <div class="btn-layer">
                    </div>
                    <input type="submit" value="Sign in" name="user">
                  </div>
                  <div class="signup-link">
                    Not a member? <a href="./php/register.php">Signup now</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
          </div>
        </div>
  </section>

  <div class="image text-center">
    <h1 class="heading">Products Made With Love</h1>
    <img src="./assets/hands.png">
  </div>

  <div id="about" class="about row m-0">
    <div class="col-lg-12">
      <h1>What We Do</h1>
      <p>Marginal farmers often struggle with the historic obstacle of technological isolation and have zero access to
        educational, scientific information and the market. KrishiMitra connects Farmers to Customers directly,
        eliminating the middlemen and empowering the Farmers. We bring products right from the farm to your kitchen.
        Krishi Mitra is a platform for customers to buy farm fresh products at their best prices online.</p>
    </div>
  </div>

  <div id="blogs" class="container-fluid info">
    <h1>Blogs</h1>
    <div class="bloghead">
      <h3>Eco-Friendly Practices</h3>
    </div>
    <div class="row py-3 px-5">
      <div class="col-lg-4 blogimg">
        <img class="img-responsive" src="assets/Eco-Friendly.jpg" width="100%">
      </div>
      <div class="col-lg-8 blogtext">
        <h2>Eco-Friendly Practices</h2>
        <p style="padding: 1.5em 1em 1em 0em;">World needs, eco-friendly farming systems for sustainable agriculture.
          This is the need of the present day. There is an urgent need to develop farming techniques, which are
          sustainable from environmental, production, and socioeconomic points of view. The means to guarantee
          sufficient food production in the next decades and beyond is critical because modern agriculture production
          throughout the world does not appear to be sustainable in the long-term.
          <span><a href="https://biocyclopedia.com/index/medicinal_plants/cultivation/eco-friendly_farming.php"
              target="_blank" rel="noopener noreferrer">Read More >></a></span>
        </p>
      </div>
    </div>
    <hr width="90%" style="background-color: #fefefe;">
    <div class="bloghead">
      <h3>Top Government Schemes</h3>
    </div>
    <div class="row py-3 px-5">
      <div class="col-lg-8 blogtext">
        <h2>Top Government Schemes</h2>
        <p style="padding: 1.5em 1em 1em 0em;">The Pradhan Mantri Krishi Sinchayee Yojana (PMKSY) was launched during
          the year 2015-16 with the motto of 'Har Khet Ko Paani' for providing end-to end solutions in irrigation supply
          chain, viz. water sources, distribution network and farm level applications. The PMKSY not only focuses on
          creating sources for assured irrigation, but also creating protective irrigation by harnessing rain water at
          micro level through 'Jal Sanchay' and 'Jal Sinchan'.
          <span><a href="http://agricoop.gov.in/divisiontype/rainfed-farming-system/programmes-schemes-new-initiatives"
              target="_blank" rel="noopener noreferrer">Read More >></a></span>
        </p>
      </div>
      <div class="col-lg-4 blogimg">
        <img class="img-responsive" src="assets/Government Schemes.jpg" width="100%">
      </div>
    </div>
    <hr width="90%" style="background-color: #fefefe;">
    <div class="bloghead">
      <h3>Use Of Digital Technology</h3>
    </div>
    <div class="row py-3 px-5">
      <div class="col-lg-4 blogimg">
        <img class="img-responsive" src="assets/Use Of Digital.jpg" width="100%">
      </div>
      <div class="col-lg-8 blogtext">
        <h2>Use Of Digital Technology</h2>
        <p style="padding: 1.5em 1em 1em 0em;">Digital agriculture refers to tools that digitally collect, store,
          analyze, and share electronic data and/or information along the agricultural value chain. Other definitions,
          such as those from the United Nations Project Breakthrough, Cornell University, and Purdue University, also
          emphasize the role of digital technology in the optimization of food systems.
          <span><a
              href="https://en.wikipedia.org/wiki/Digital_agriculture#:~:text=Besides%20streamlining%20farm%20production%2C%20digital,costs%20throughout%20the%20value%20chain."
              target="_blank" rel="noopener noreferrer">Read More >></a></span>
        </p>
      </div>
    </div>
  </div>

  <div class="container-fluid footer">
    <div class="row">
      <div class="col-lg-12 py-4 text-center" style="color: #3797a4; font-weight: 600; font-size: larger;">
        <hr width="30%" align="text-center" style="border-width: 2px; background-color: #999;">
        GET IN TOUCH WITH US!
        <hr width="30%" align="text-center" style="border-width: 2px; background-color: #999;">
      </div>
    </div>

    <div class="row align-items-center justify-content-around mx-2">
      <div class="col-md-2 text-center">
        <img src="./assets/Logo2.png" class="img-responsive" alt="Krishi Mitra Logo" style="max-width: 100%;">
      </div>

      <div class="col-md-2">
        <h5 class="font-weight-bold">Quick Links</h5>
        <a href="#" style="text-decoration: none; color:#444;" data-toggle="modal" data-target="#at-login">
          <p class="mb-1"><i class="fa fa-sign-in mr-2" aria-hidden="true"></i>Login</p>
        </a>
        <a href="./php/register.php" style="text-decoration: none; color:#444;">
          <p class="mb-1"><i class="fa fa-user-plus mr-2" aria-hidden="true"></i>Register</p>
        </a>
    </div>

      <div class="col-md-2">
        <h5 class="font-weight-bold">Contact Us</h5>
        <p class="mb-1"><i class="fa fa-phone mr-2" aria-hidden="true"></i>8899776655</p>
        <p class="mb-1"><i class="fa fa-envelope mr-2" aria-hidden="true"></i>ty34@gmail.com</p>
        <p class="mb-1"><i class="fa fa-home mr-2" aria-hidden="true"></i>VIT, Pune</p>
      </div>

      <div class="col-md-2">
        <h5 class="font-weight-bold">Join Us</h5>
        <p class="mb-1">Products Made With Love</p>
        <a href="./php/register.php" class="btn signupbtn btn-sm" role="button">Sign up</a>
      </div>

      <div class="col-md-2">
        <img src="./assets/mail.gif" class="img-responsive" width="100%" alt="Email us">
      </div>
    </div>

    <div class="row py-3 mt-4" style="background-color: #3797a4; font-weight: 600;">
      <div class="col-lg-12 text-center text-white">
        © 2025 Copyright <a href="#" style="text-decoration: none; color: inherit">KrishiMitra</a>
      </div>
    </div>
  </div>

  <script type="text/javascript" src="./scripts/index.js"></script>
  <script>
  const loginText = document.querySelector(".title-text .login");
  const loginForm = document.querySelector("form.login");
  const loginBtn = document.querySelector("label.login");
  const signupBtn = document.querySelector("label.signup");
  const signupLink = document.querySelector("form .signup-link a");
  signupBtn.onclick = (() => {
    loginForm.style.marginLeft = "-50%";
    loginText.style.marginLeft = "-50%";
  });
  loginBtn.onclick = (() => {
    loginForm.style.marginLeft = "0%";
    loginText.style.marginLeft = "0%";
  });
  </script>
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
    integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
  </script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
    integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
  </script>
</body>

</html>