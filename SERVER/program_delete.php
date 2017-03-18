<?php

  //Load variables
  if (!isset($_POST['id'])) exit;
  
  //DB
  include "dbase.php";
  
  //Escape variables
  $id = mysqli_real_escape_string($conn, $_POST['id']);
   
  //SQL 
  $sql = "DELETE FROM program WHERE id=$id AND production=0 AND priority <= 5 AND priority >= 1";     
  $result = $conn->query($sql);
