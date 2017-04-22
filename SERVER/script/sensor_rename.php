<?php

  if (!isset($_POST['sensor'])) exit;
  if (!isset($_POST['comment'])) exit;

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  
  $sensor  = mysqli_real_escape_string($database->conn, $_POST['sensor']);
  $comment = mysqli_real_escape_string($database->conn, $_POST['comment']);

  $sql = "UPDATE sensor SET comment='$comment' WHERE sensor='$sensor'";

  $database->conn->query($sql);
	$database->conn->close();