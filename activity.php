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
<div class="max-size">
  <h4>BattleShip Activity</h4>
  <a href="account.php" class="hyperlink">Go Back</a>
  <form onsubmit="return false;">
    <span class="label">Smart Filter:</span><input id="filter" value="<?php echo date("m/d/Y"); ?>" onkeyup="filterTable(this.value);"><br>
    <span class="label"></span><span>Spaces delimit tokens.'~' prefix negates match.</span><br>
  </form>
</div>
<table class="activity">
<tr>
  <th>Timestamp</th>
  <th>Bot Name</th>
  <th>IP Address</th>
  <th>Message</th>
</tr>
<?php
  foreach($log_contents as $line){
    if(!preg_match("/^\\[(.*)\\] \\((.*)@(.*)\\) (.*)$/", $line, $matches)){
      continue;
    }
    echo sprintf("<tr class=\"activity-row\"><td>%s</td><td>%s</td><td>%s</td><td style=\"white-space:normal;\">%s</td></tr>"
                 , date("m/d/Y h:i:s a", strtotime($matches[1]))
                 , htmlspecialchars($matches[2])
                 , $matches[3]
                 , htmlspecialchars($matches[4]));
  }
?>
<tr id="noDataRow" style="display:none;"><td colspan=4>No Activity Data</td></tr>
</table>
<div class="max-size">
  <a href="account.php" class="hyperlink">Go Back</a>
</div>
</div>

<script>
var rows = document.getElementsByClassName("activity-row");
var filter = document.getElementById("filter").value;
filterTable(filter);

function filterTable(text){
  text = text.trim().toLowerCase();
  var tokens = text.split(/\s+/);

  var hiddenRows = 0;
  for(var i=0;i<rows.length;i++){
    var row = rows[i];
    var innerText = row.innerText.toLowerCase();

    if(tokens.length == 1 && tokens[0].length == 0){
      row.style.display = "table-row";
    }
    else{
      row.style.display = "table-row";
      for(var j=0;j<tokens.length;j++){
        var tok = tokens[j];
        var invert = tok[0] === "~";
        if(invert) tok = tok.substring(1);

        if(!invert && innerText.indexOf(tok) < 0 || invert && innerText.indexOf(tok) >= 0){
          row.style.display = "none";
          hiddenRows += 1;
          break;
        }
      }
    }
  }
  if(hiddenRows == rows.length){
    document.getElementById("noDataRow").style.display="table-row";
  }
  else{
    document.getElementById("noDataRow").style.display="none";
  }

}

</script>

</body>
</html>
