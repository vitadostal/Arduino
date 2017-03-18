<?php
  if (!isset($_POST['sensor']))  exit;
  if (!isset($_POST['item'])) exit;
  if (!isset($_POST['graph'])) exit;

  include "dbase.php";
  
  $sensor  = mysqli_real_escape_string($conn, $_POST['sensor']);
  $item    = mysqli_real_escape_string($conn, $_POST['item']);
  $graph   = mysqli_real_escape_string($conn, $_POST['graph']);

  $sql = "UPDATE sensor SET graph$item='$graph' WHERE sensor='$sensor'";
  $result = $conn->query($sql);