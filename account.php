<?php
session_start();

if(!isset($_SESSION["username"])){
  header("location:index.php");
  die("You must be logged in to see this page. Redirecting to <a href=\"index.php\">Login</a>");
}

require("logger.php");

$username = $_SESSION["username"].trim();
$path = "/var/www/html";

//--------------------------------------
//----------Upload a file---------------
//--------------------------------------

$upload_error_message = "";
$upload_message = "";

if($username != "Admin" && isset($_FILES["fileToUpload"])){
  $target_dir = "$path/jars/";
  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
  $fileType = pathinfo($target_file,PATHINFO_EXTENSION);

  // Check file size
  if ($_FILES["fileToUpload"]["size"] > 500000)
    $upload_error_message .= "Sorry, your file is too large<br>";

  // Allow certain file formats
  if($fileType != "jar")
    $upload_error_message .= "Sorry, only jar files are allowed<br>";

  // Allow certain file formats
  if(basename($_FILES["fileToUpload"]["name"]) != ($username . "." . $fileType))
    $upload_error_message .= "Sorry, the jar file must be named " . htmlspecialchars($username) . ".jar<br>";

  //check if we have an error
  if (strlen($upload_error_message) > 0)
    $upload_error_message .= "Your file was not uploaded.<br>";
  // if everything is ok, try to upload file
  else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
      $upload_message .= "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded<br>";
      $upload_message .= "Your standings have been reset<br>";
      $handshake_result = exec("bash $path/handshake.sh " . escapeshellarg($username));
      if($handshake_result == "FAIL"){
        $upload_error_message .= "There was an error with the server handshake. Your upload will be deleted.<br>Ensure your Java class name matches your bot name and your program does not generate any output before calling the start method of the protocol class.<br>If this seems like an error, please contact <a href=\"mailto:Aaron.L.Buchholz@ndsu.edu\">Aaron Buchholz</a>";
        exec("rm $target_file");
      }
      exec("bash $path/clearhistory.sh " . escapeshellarg($username));
    } else {
      $upload_error_message .= "Sorry, there was an error uploading your file<br>";
    }
  }
  logger_write(sprintf("Upload JAR Attempt - FILE:%s,MESSAGE:%s",
                       $target_file,
                       $upload_message . $upload_error_message));

}

//--------------------------------------
//--------Submit a game request---------
//--------------------------------------

$request_error_message = "";
$request_message = "";

$alljars = scandir("jars");
$current_requests = explode("\n", file_get_contents("gamerequests.txt"));

if(isset($_POST["redplayer"])){
  $red = $_POST["redplayer"];
  $blue = $_POST["blueplayer"];

  $line = 1;
  foreach($current_requests as $request){
    if(strlen($request)==0)
      continue;
    $tokens = explode(" ", $request);
    if(strpos($tokens[0], $username) === 0){
      $request_error_message .= sprintf("You have already requested a match. It is #%d in queue<br>", $line);
      break;
    }
    if(strpos($tokens[1], $red)===0 && strpos($tokens[2], $blue)===0){
      $request_error_message .= sprintf("That match has already been requested by %s. It is #%d in queue<br>", htmlspecialchars($tokens[0]), $line);
      break;
    }

    $line += 1;
  }

  if($red == "---" || $blue == "---")
    $request_error_message .= "You must specify both a Red and Blue player<br>";
  else if($red == $blue)
    $request_error_message .= "You must specify two different opponents<br>";

  if($red != "---" && !in_array($red . ".jar", $alljars))
    $request_error_message .= sprintf("Could not locate script for %s<br>", $red);
  if($blue != "---" && !in_array($blue . ".jar", $alljars))
    $request_error_message .= sprintf("Could not locate script for %s<br>", $blue);

  if(strlen($request_error_message)>0)
    $request_error_message .= "Your request has not been submitted<br>";
  else{
    if(file_put_contents("gamerequests.txt", sprintf("%s %s.jar %s.jar\n", $username, $red, $blue), FILE_APPEND | LOCK_EX) === false)
      $request_error_message .= "There was an error submitting your request<br>";
    else
      $request_message .= "Your request has been submitted<br>";
  }
  logger_write(sprintf("Request Game Attempt - RED:%s,BLUE:%s,MESSAGE:%s",
                       $red,
                       $blue,
                       $request_message . $request_error_message));
}

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
    <div class="profile">Hello, <?php echo htmlspecialchars($username); ?>!<a href="logout.php" title="logout" class="logout">&#10006;</a></div>
  </div>
</div>

<div class="content max-size">
  <?php if($username != "Admin"){ ?>
    <h2>Upload your jar file:</h2>
    <form method="post" enctype="multipart/form-data">
      <?php if(strlen($upload_message)>0){?><p style="color:green;"><?php echo $upload_message; ?></p><?php } ?>
      <?php if(strlen($upload_error_message)>0){?><p style="color:red;"><?php echo $upload_error_message; ?></p><?php } ?>
      <div>
        <span class="label">Select jar to upload:</span>
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Upload" name="submit">
      </div>
      <?php
        $user_last_uploaded_time = filemtime("jars/$username.jar");
        $uploaddate = date ("m/d/Y h:i:s a", $user_last_uploaded_time);
        if($user_last_uploaded_time == 0)
          $uploaddate = "None";
      ?>
      <span class="label">Last upload: </span> <?php echo htmlspecialchars($uploaddate); ?>
    </form>
  <hr>
  <h2>Request a match</h2>
  <form method="post" id="requestform">
    <?php
      $select_options = "<option>---</option>\n";
      foreach($alljars as $jar){
        if(strpos($jar, ".jar")>0){
          $name = htmlspecialchars(explode(".", $jar)[0]);
          $select_options .= sprintf("<option value=\"%s\">%s</option>\n", $name, $name);
        }
      }
    ?>
    <?php if(strlen($request_message)>0){?><p style="color:green;"><?php echo $request_message; ?></p><?php } ?>
    <?php if(strlen($request_error_message)>0){?><p style="color:red;"><?php echo $request_error_message; ?></p><?php } ?>
    <span class="label">Current queue size:</span><span><?php echo sizeof($current_requests) - 1; ?></span><br>
    <span class="label">Red player</span><select form="requestform" name="redplayer"><?php echo $select_options; ?></select><br>
    <span class="label">Blue player</span><select form="requestform" name="blueplayer"><?php echo $select_options; ?></select><br>
    <input type="submit" name="submit" value="Submit Request">
  </form>
  <hr>
  <?php } /* end if($username != "Admin") */
  else { ?>
  <h2>View the activity log</h2>
  <a class="hyperlink" href="activity.php">Activity</a>
  <hr>
  <h2>Registered Users</h2>
  <ul>
    <?php
    $users = explode("\n", file_get_contents("../roster.txt"));
    foreach($users as $user){
      if(strlen($user) == 0) continue;
      $tokens = explode(" ", $user);
      if(sizeof($tokens) > 1){
        echo sprintf("<li>%s - %s</li>"
                     , htmlspecialchars($tokens[0])
                     , htmlspecialchars($tokens[1]));
      }
    }
    ?>
  </ul>
  <hr>
  <?php } /* end else: $username == "Admin" */ ?>

  <h2>Current standings:</h2>
  <span>Hover over a row for details.</span>
  <table class="standings">
  <tr>
    <th>Rank</th>
    <th>Bot Name</th>
    <th>Games Won</th>
    <th>Games Played</th>
    <th>Percent Won</th>
  </tr>
  <?php
    //read in the results file
    $results = file_get_contents("$path/BattleShipServer/logs/results.log");
    $lines = explode("\n", $results);

    $data = array();

    //parse results file and start adding things up
    foreach($lines as $line){
      if(strlen($line)==0) continue;

      $tokens = explode(" ", $line);
      //tokens[0] -> winner
      //tokens[2] -> loser

      //skip those who play with themselves
      if($tokens[0] === $tokens[2]){
        continue;
      }

      if(!isset($data[$tokens[0]])) {
        $data[$tokens[0]] = array("wins"=>0,
                                  "games"=>0,
                                  "ratio"=>0,
                                  "breakdown"=>array());
      }
      if(!isset($data[$tokens[2]])) {
        $data[$tokens[2]] = array("wins"=>0,
                                  "games"=>0,
                                  "ratio"=>0,
                                  "breakdown"=>array());
      }
      //increase the counts for calculating the ratio
      $data[$tokens[0]]["wins"] += 1;
      $data[$tokens[0]]["games"] += 1;
      $data[$tokens[2]]["games"] += 1;

      //increase win count for this specific loser
      if(!isset($data[$tokens[0]]["breakdown"][$tokens[2]])){
        $data[$tokens[0]]["breakdown"][$tokens[2]] = 0;
      }

      $data[$tokens[0]]["breakdown"][$tokens[2]] += 1;
    }

    //calculate the ratios after parsing the file
    foreach(array_keys($data) as $user){
      $data[$user]["ratio"] = $data[$user]["wins"] / $data[$user]["games"];
    }

    //sort the list on ratio
    function compare_ratios($user1, $user2){
      if($user1["ratio"] == $user2["ratio"])
        return 0;
      return ($user1["ratio"] < $user2["ratio"]) ? 1 : -1;
    }

    uasort($data, "compare_ratios");

    //display the table rows
    if(sizeof($data) == 0)
      echo "<tr><td colspan=5>No game data.</td><tr>";

    //Output of the array
    //highlight_string("\$data =\n" . var_export($data, true) . ";\n");

    $lastRatio = -1;
    $rank = 1;
    $ties = 0;
    //go through each user in data to build their row in the table
    foreach(array_keys($data) as $user){
      //capture these so it is easier to output in strings
      $myusername = htmlspecialchars($user);
      $myratio = $data[$user]["ratio"];
      $mytooltip = htmlspecialchars($user) . "'s breakdown";

      //this loop will calculate wins per username and the relative ratio
      //will be output in the tooltip when hovering on the row
      foreach(array_keys($data[$user]["breakdown"]) as $loser){
        $wins = $data[$user]["breakdown"][$loser];
        $total = $data[$user]["breakdown"][$loser];
        //if loser has also beaten user, make sure we include those games in our total
        if(isset($data[$loser]["breakdown"][$user])){
          $total += $data[$loser]["breakdown"][$user];
        }
        $ratio = sprintf("%4.2f%%", $wins/$total*100);

        $mytooltip .= "&#10;" . htmlspecialchars($loser) . ": $wins / $total = $ratio";
      }

      //highlight the row if it is us
      if($username == $myusername)
        $tablerow = "<tr title=\"$mytooltip\" class=\"self\">";
      else
        $tablerow = "<tr title=\"$mytooltip\">";

      //see if we have a tie in rank with the last guy
      if($lastRatio == $myratio)
        $ties += 1;
      else
        $ties = 0;

      //update the last ratio to the current one for the next iteration
      $lastRatio = $myratio;

      //output the row
      echo sprintf("%s<td>%d</td><td>%s</td><td>%d</td><td>%d</td><td>%4.2f%%</td></tr>"
      	  , $tablerow, $rank++ - $ties, $myusername
      	  , $data[$user]["wins"], $data[$user]["games"], $myratio*100);
    }
    ?>
  </table>

  <hr>
    <?php
    $logs = scandir("$path/BattleShipServer/logs/");
    $yourGames = array();
    $otherGames = array();
    function compare_times($f1, $f2){
      $f1t = 0;
      $f2t = 0;
      $path = "/var/www/html";

      if(strpos($f1, "VS") !== false)
        $f1t = filemtime("$path/BattleShipServer/logs/$f1");
      if(strpos($f2, "VS") !== false)
        $f2t = filemtime("$path/BattleShipServer/logs/$f2");

      if($f1t == $f2t) return 0;
      return ($f1t < $f2t) ? 1 : -1;
    }

    usort($logs, "compare_times");

    foreach($logs as $log){
      $vsPos = strpos($log, "VS");
      if($vsPos !== false){
        $dotPos = strpos($log, ".");
        $name1 = substr($log, 0, $vsPos);
        $name2 = substr($log, $vsPos+2, $dotPos-$vsPos-2);

        //skip those who play with themselves
        if($name1 === $name2){
          continue;
        }
        $log_filetime = filemtime("$path/BattleShipServer/logs/" . $log);
        $linktext = sprintf("<li><a class=\"hyperlink\" href=\"#\" onClick=\"window.open('visualizer.html?%s', 'MyWindow', width=600, height=300); return false;\">%s vs. %s - %s %s</a></li>\n"
                            , htmlspecialchars($log), htmlspecialchars($name1)
                            , htmlspecialchars($name2), htmlspecialchars(date ("F d Y h:i:s a", $log_filetime))
                            , ($name1==$username || $name2==$username)
                              && ($user_last_uploaded_time == 0 || $log_filetime < $user_last_uploaded_time)
                                 ? "<span class=\"old-submission\">[OLD SUBMISSION]</span>"
                                 : "");

        if($name1 == $username || $name2 == $username)
          $yourGames[] = $linktext;
        else
          $otherGames[] = $linktext;
      }
    }
    ?>

  <?php if($username != "Admin") { ?>
  <h2>Watch your games</h2>
  <ul>
    <?php
    if(sizeof($yourGames)==0){
      echo "<p>No game data.</p>";
    }
    foreach($yourGames as $game){
      echo $game;
    }
    ?>
  </ul>
  <hr>
  <?php } /* end if(username != "Admin") */
  else { ?>

  <?php } /* end else: username == "Admin" */ ?>

  <h2>Watch other games</h2>
  <ul>
    <?php
    if(sizeof($otherGames)==0){
      echo "<p>No game data.</p>";
    }
    foreach($otherGames as $game){
      echo $game;
    }
    ?>
  </ul>
</div>
</body>
</html>
