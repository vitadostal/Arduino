<?php

  if (!isset($_POST['sensor'])) exit;
  if (!isset($_POST['class'])) exit;

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  
  $sensor  = mysqli_real_escape_string($database->conn, $_POST['sensor']);
  $class   = mysqli_real_escape_string($database->conn, $_POST['class']);
  $graph   = mysqli_real_escape_string($database->conn, $_POST['graph']);

  $sql = "INSERT INTO display (sensor, class, graph) VALUES('$sensor', '$class', '$graph') ON DUPLICATE KEY UPDATE graph='$graph'";

  $database->conn->query($sql);
	$database->conn->close();