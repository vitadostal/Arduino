<?php

  if (!isset($_POST['id'])) exit;

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();

  $id = mysqli_real_escape_string($database->conn, $_POST['id']);

  $sql = "DELETE FROM program WHERE id=$id AND production=0 AND priority <= 5 AND priority >= 1";
  $database->conn->query($sql);
	$database->conn->close();