<?php
  session_start();
  session_unset();
  header("location:index.php");
  die("You have successfully been logged out. Redirecting to <a href=\"index.php\">Login</a>");
?>
