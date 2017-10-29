<?php
  session_start();

  require("logger.php");
  logger_write("Log Out Attempt");

  session_unset();

  header("location:index.php");
  die("You have successfully been logged out. Redirecting to <a href=\"index.php\">Login</a>");
?>
