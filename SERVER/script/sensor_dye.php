<?php

  if (!isset($_POST['sensor'])) exit;
  if (!isset($_POST['color'])) exit;

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  
  $sensor  = mysqli_real_escape_string($database->conn, $_POST['sensor']);
  $class   = mysqli_real_escape_string($database->conn, $_POST['class']);
  $color   = mysqli_real_escape_string($database->conn, $_POST['color']);

  if ($class == '')
    $sql = "UPDATE sensor SET color='$color' WHERE sensor='$sensor'";
  else
    $sql = "INSERT INTO display (sensor, class, color) VALUES('$sensor', '$class', '$color') ON DUPLICATE KEY UPDATE color='$color'";
  
  $database->conn->query($sql);
	$database->conn->close();