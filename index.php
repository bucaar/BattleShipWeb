<?php
session_start();

$path = "/var/www/html";

$login_error_message = "";
$register_error_message = "";
$register_message = "";
$change_error_message = "";
$change_message = "";

$is_login = isset($_POST["login_submit"]);
$is_register = isset($_POST["register_submit"]);
$is_change = isset($_POST["change_submit"]);

function lookup_cs_id($cs_id){
  $users = explode("\n",file_get_contents("/var/www/roster.txt"));
  foreach($users as $user){
    $info = explode(" ", $user);
    if(strtolower($cs_id) === strtolower($info[0])){
      return $info;
    }
  }
  return false;
}

function is_username_taken($username){
  $users = explode("\n",file_get_contents("/var/www/roster.txt"));
  foreach($users as $user){
    $info = explode(" ", $user);
    if(sizeof($info) > 1 && strtolower($info[1]) === strtolower($username)){
      return true;
    }
  }
  return false;
}

function escape_backreference($x){
  return preg_replace('/\$(\d)/', '\\\$$1', $x);
}

function update_account($cs_id, $username, $pin){
  $file = file_get_contents("/var/www/roster.txt");
  $password = password_hash($pin, PASSWORD_DEFAULT);
  //ensure that the $2y$10.. isn't used a a preg backreference
  $password = escape_backreference($password);
  $updated_file = preg_replace("/^" . $cs_id . "/m", sprintf("%s %s %s", $cs_id, $username, $password), $file);
  if($file === $updated_file){
    return false;
  }
  $result = file_put_contents("/var/www/roster.txt", $updated_file, LOCK_EX);
  if($result === false){
    return false;
  }
  return $result > 0;
}

function update_password($cs_id, $pin){
  $file = file_get_contents("/var/www/roster.txt");
  $password = password_hash($pin, PASSWORD_DEFAULT);
  //ensure that the $2y$10.. isn't used a a preg backreference
  $password = escape_backreference($password);
  $updated_file = preg_replace("/^(" . $cs_id . ") (\w+) .*/m", "$1 $2 $password", $file);
  if($file === $updated_file){
    return false;
  }
  $result = file_put_contents("/var/www/roster.txt", $updated_file, LOCK_EX);
  if($result === false){
    return false;
  }
  return $result > 0;
}

//check if they are regestering
if($is_register){
  //validate cs_id length > 4
  if(!preg_match("/^\w{4,}$/", $_POST["cs_id"])){
    $register_error_message .= "You must provide a valid CS ID<br>";
  }
  //validate username length 4-15 [a-zA-Z_]
  else if(!preg_match("/^[a-zA-Z_]{4,15}$/", $_POST["username"])){
    $register_error_message .= "Desired bot name must be between 4 and 15 characters [a-zA-Z_]<br>";
  }
  //validate pin being 6 digit number
  else if(!preg_match("/^\d{6}$/", $_POST["pin_code"])){
    $register_error_message .= "Pin code must be a 6 digit number<br>";
  }
  //validate pin codes match
  else if($_POST["pin_code"] !== $_POST["pin_code_repeat"]){
    $register_error_message .= "Pin codes do not match<br>";
  }
  //everything valid
  else{
    //ensure they haven't already enrolled
    $info = lookup_cs_id($_POST["cs_id"]);
    if($info === false){
      $register_error_message .= "Could not find an account that matches the specified CS ID<br>";
    }
    else if(sizeof($info)>1){
      $register_error_message .= "This CS ID has already been registered<br>";
    }
    else if(is_username_taken($_POST["username"])){
      $register_error_message .= "This bot name is already being used<br>";
    }
    else{
      if(update_account($info[0], $_POST["username"], $_POST["pin_code"])){
        $register_message .= "Account has been registered!<br>";
      }
      else{
        $register_error_message .= "There was an error with registration<br>";
      }
    }
  }
}
//check if they are logging in
else if($is_login){
  //validate cs_id length > 4
  if(!preg_match("/^\w{4,}$/", $_POST["cs_id"])){
    $login_error_message .= "You must provide a valid CS ID<br>";
  }
  //validate pin being 6 digit number
  else if(!preg_match("/^\d{6}$/", $_POST["pin_code"])){
    $login_error_message .= "Pin code must be a 6 digit number<br>";
  }
  //everything valid
  else{
    //ensure they have already enrolled
    $info = lookup_cs_id($_POST["cs_id"]);
    if($info === false){
      $login_error_message .= "Could not find an account that matches the specified CS ID<br>";
    }
    else if(sizeof($info) == 1){
      $login_error_message .= "This CS ID has not yet been registered.<br>";
    }
    else{
      if(password_verify($_POST["pin_code"], $info[2])){
        $_SESSION["username"] = $info[1];
        header("location:account.php");
        die("Redirecting to <a href=\"account.php\">account page</a>");
        $login_error_message .= "Login success<br>";
      }
      else{
        $login_error_message .= "CS ID or Pin code is incorrect<br>";
      }
    }
  }
}
//check if they are changing their pin
else if($is_change){
  //validate cs_id length > 4
  if(!preg_match("/^\w{4,}$/", $_POST["cs_id"])){
    $change_error_message .= "You must provide a valid CS ID<br>";
  }
  //validate pin being 6 digit number
  else if(!preg_match("/^\d{6}$/", $_POST["pin_code_old"])){
    $change_error_message .= "Current pin code must be a 6 digit number<br>";
  }
  else if(!preg_match("/^\d{6}$/", $_POST["pin_code"])){
    $change_error_message .= "New pin code must be a 6 digit number<br>";
  }
  //validate pin codes match
  else if($_POST["pin_code"] !== $_POST["pin_code_repeat"]){
    $change_error_message .= "New pin codes do not match<br>";
  }
  //everything valid
  else{
    //ensure they haven't already enrolled
    $info = lookup_cs_id($_POST["cs_id"]);
    if($info === false){
      $change_error_message .= "Could not find an account that matches the specified CS ID<br>";
    }
    else if(sizeof($info) == 1){
      $change_error_message .= "This CS ID has not yet been registered<br>";
    }
    else if(!password_verify($_POST["pin_code_old"], $info[2])){
      $change_error_message .= "Current pin code is incorrect<br>";
    }
    else{
      if(update_password($info[0], $_POST["pin_code"])){
        $change_message .= "Your pin code has been changed!<br>";
      }
      else{
        $change_error_message .= "There was an error with changing your pin code<br>";
      }
    }
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
    <?php if(strlen($login_error_message)>0) echo "<p style=\"color:red\">$login_error_message</p>"; ?>
    <span class="label">CS ID</span><input name="cs_id" value="<?php if($is_login) echo htmlspecialchars($_POST["cs_id"]); ?>"><br>
    <span class="label">Pin code</span><input type="password" name="pin_code"><br>
    <input type="submit" value="Login" name="login_submit">
  </form>
  <hr>
  <h4>Register your account</h4>
  <form method="post">
    <?php if(strlen($register_message)>0) echo "<p style=\"color:green\">$register_message</p>"; ?>
    <?php if(strlen($register_error_message)>0) echo "<p style=\"color:red\">$register_error_message</p>"; ?>
    <span class="label">CS ID</span><input name="cs_id" value="<?php if($is_register) echo htmlspecialchars($_POST["cs_id"]); ?>"><br>
    <span class="label">Desired bot name</span><input name="username" value="<?php if($is_register) echo htmlspecialchars($_POST["username"]); ?>"><br>
    <span class="label">Pin code</span><input type="password" name="pin_code"><br>
    <span class="label">Repeat pin code</span><input type="password" name="pin_code_repeat"><br>
    <input type="submit" value="Register" name="register_submit">
  </form>
  <hr>
  <h4>Change your pin code</h4>
  <form method="post">
    <?php if(strlen($change_message)>0) echo "<p style=\"color:green\">$change_message</p>"; ?>
    <?php if(strlen($change_error_message)>0) echo "<p style=\"color:red\">$change_error_message</p>"; ?>
    <span class="label">CS ID</span><input name="cs_id" value="<?php if($is_change) echo htmlspecialchars($_POST["cs_id"]); ?>"><br>
    <span class="label">Current pin code</span><input type="password" name="pin_code_old"><br>
    <span class="label">New pin code</span><input type="password" name="pin_code"><br>
    <span class="label">Repeat pin code</span><input type="password" name="pin_code_repeat"><br>
    <input type="submit" value="Change" name="change_submit">
  </form>
</div>
</body>
</html>

