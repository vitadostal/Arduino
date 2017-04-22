<?php

  if (!isset($_POST['sensor'])) exit;
  if (!isset($_POST['class'])) exit;

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  
  $sensor  = mysqli_real_escape_string($database->conn, $_POST['sensor']);
  $class   = mysqli_real_escape_string($database->conn, $_POST['class']);
  $suffix  = mysqli_real_escape_string($database->conn, $_POST['suffix']);

  $sql = "INSERT INTO display (sensor, class, suffix) VALUES('$sensor', '$class', '$suffix') ON DUPLICATE KEY UPDATE suffix='$suffix'";
  
  $database->conn->query($sql);
	$database->conn->close();  