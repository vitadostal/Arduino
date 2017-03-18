<?php
  if (!isset($_POST['sensor']))  exit;
  if (!isset($_POST['item'])) exit;
  if (!isset($_POST['color'])) exit;

  include "dbase.php";
  
  $sensor  = mysqli_real_escape_string($conn, $_POST['sensor']);
  $item    = mysqli_real_escape_string($conn, $_POST['item']);
  $color   = mysqli_real_escape_string($conn, $_POST['color']);

  $sql = "UPDATE sensor SET color$item='$color' WHERE sensor='$sensor'";
  $result = $conn->query($sql);