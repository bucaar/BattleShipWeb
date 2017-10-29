<?php


function logger_write($msg){
  $logFile = "/var/www/html/activity.log";

  $timestamp = date("c");

  $user = "";
  if(isset($_SESSION["username"])){
    $user = $_SESSION["username"];
  }

  $ip = $_SERVER["REMOTE_ADDR"];

  $line = "[$timestamp] ($user@$ip) $msg\n";

  return file_put_contents($logFile, $line, LOCK_EX | FILE_APPEND);
}

?>
