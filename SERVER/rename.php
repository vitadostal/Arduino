<?php
  if (!isset($_POST['sensor']))  exit;
  if (!isset($_POST['comment'])) exit;

  include "dbase.php";
  
  $sensor  = mysqli_real_escape_string($conn, $_POST['sensor']);
  $comment = mysqli_real_escape_string($conn, $_POST['comment']);

  $sql = "UPDATE sensor SET comment='$comment' WHERE sensor='$sensor'";
  $result = $conn->query($sql);
