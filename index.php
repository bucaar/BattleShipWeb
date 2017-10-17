<?php
session_start();

$error_message = "";

if(isset($_POST["submit"]) && isset($_POST["username"]) && isset($_POST["password"])){
  if(strlen($_POST["username"]) > 0){
    $_SESSION["username"] = $_POST["username"];
    header("location:account.php");
    die("Redirecting to <a href=\"account.php\">your account page</a>");
  }
  else{
    $error_message = "Username or Password is incorrect";
  }
}
?>

<html>
<head>
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
<div class="fixed-top navbar">
  <div class="max-size">
    <div class="brand">BattleShip</div>
    <div class="profile"></div>
  </div>
</div>
<div class="content max-size">
  <h4>Login to BattleShip</h4>
  <form method="post">
    <?php if(strlen($error_message)>0) echo "<p style=\"color:red\">$error_message</p>"; ?>
    <span class="label">Username</span><input name="username"><br>
    <span class="label">Password</span><input type="password" name="password"><br>
    <input type="submit" value="Login" name="submit">
  </form>
</div>
</body>
</html>

