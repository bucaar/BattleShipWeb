<?php
session_start();

$path = "/var/www/html";

$login_error_message = "";
$register_error_message = "";

$is_register = isset($_POST["username"]) && isset($_POST["pin_code_repeat"]);
$is_login = !$is_register && isset($_POST["submit"]) && isset($_POST["cs_id"]) && isset($_POST["pin_code"]);

function custom_hash($cs_id, $username, $pin){

}

function lookup_cs_id($cs_id){
  $cs_id = strtolower($cs_id);
  $users = explode("\n",file_get_contents("/var/www/roster.txt"));
  foreach($users as $user){
    if($cs_id === $info[0]){
      return $info;
    }
  }
  return false;
}

function update_account($cs_id, $username, $pin){

}

//check if they are regestering
if($is_register){
  if(!preg_match("/^\w{4,}$/", $_POST["cs_id"])){
    $register_error_message .= "You must provide a valid CS ID<br>";
  }
  else if(!preg_match("/^\w{4,15}$/", $_POST["username"])){
    $register_error_message .= "Desired bot name must be between 4 and 15 characters [a-zA-Z_]<br>";
  }
  else if(!preg_match("/^\d{6}$/", $_POST["pin_code"])){
    $register_error_message .= "Pin code must be a 6 digit number<br>";
  }
  else if($_POST["pin_code"] !== $_POST["pin_code_repeat"]){
    $register_error_message .= "Pin codes do not match<br>";
  }
  else{
    //ensure they haven't already enrolled
    $info = lookup_cs_id($_POST["cs_id"]);
    if($info === false){
      $register_error_message .= "Could not find an account that matches the specified CS ID<br>";
    }
    else if(sizeof($info)>1){
      $register_error_message .= "<br>";
    }
  }
}
//check if they are logging in
else if($is_login){
  $login_error_message .= "Username incorrect";
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
    <?php if(strlen($login_error_message)>0) echo "<p style=\"color:red\">$login_error_message</p>"; ?>
    <span class="label">CS ID</span><input name="cs_id" value="<?php if($is_login) echo htmlspecialchars($_POST["cs_id"]); ?>"><br>
    <span class="label">Pin code</span><input type="password" name="pin_code"><br>
    <input type="submit" value="Login" name="submit">
  </form>
  <hr>
  <h4>Or register your account</h4>
  <form method="post">
    <?php if(strlen($register_error_message)>0) echo "<p style=\"color:red\">$register_error_message</p>"; ?>
    <span class="label">CS ID</span><input name="cs_id" value="<?php if($is_register) echo htmlspecialchars($_POST["cs_id"]); ?>"><br>
    <span class="label">Desired bot name</span><input name="username" value="<?php if($is_register) echo htmlspecialchars($_POST["username"]); ?>"><br>
    <span class="label">Pin code</span><input type="password" name="pin_code"><br>
    <span class="label">Repeat pin code</span><input type="password" name="pin_code_repeat"><br>
    <input type="submit" value="Register" name="submit">
  </form>
</div>
</body>
</html>

