<?php
session_start();

if(!isset($_SESSION["username"])){
  header("location:index.php");
  die("You must be logged in to see this page. Redirecting to <a href=\"index.php\">Login</a>");
}
if($_SESSION["username"] != "Admin"){
  header("location:index.php");
  die("You must be an Admin to see this page. Redirecting to <a href=\"account.php\">Account page</a>");
}

require("logger.php");

$log_contents = array_reverse(explode("\n", file_get_contents("activity.log", "r")));

?>

<html>
<head>
  <title>BattleShip</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
<div class="fixed-top navbar">
  <div class="max-size">
    <div class="brand">BattleShip</div>
    <div class="profile">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?>!<a href="logout.php" title="logout" class="logout">&#10006;</a></div>
  </div>
</div>
<div class="content">
<h4>BattleShip Activity</h4>
<a href="account.php" class="hyperlink">Go Back</a>
<table class="activity">
<tr>
  <th>Timestamp</th>
  <th>Username</th>
  <th>IP Address</th>
  <th>Message</th>
</tr>
<?php
  foreach($log_contents as $line){
    if(!preg_match("/^\\[(.*)\\] \\((.*)@(.*)\\) (.*)$/", $line, $matches)){
      continue;
    }
    echo sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td style=\"white-space:normal;\">%s</td></tr>"
                 , date("m/d/Y h:i:s a", strtotime($matches[1]))
                 , htmlspecialchars($matches[2])
                 , $matches[3]
                 , htmlspecialchars($matches[4]));
  }
?>
</table>
<a href="account.php" class="hyperlink">Go Back</a>
</div>

</body>
</html>
