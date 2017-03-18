<?php

  //DB
  include "dbase.php";

  //Check passwd
  if (!isset($_POST['thermopass']) || $_POST['thermopass'] != $thermopass) {print '!PASSWD'; exit();} 
  
  //SQL 
  $sql = "
    START TRANSACTION;
    DELETE FROM program WHERE production = 1;
    CREATE TEMPORARY TABLE temp_export SELECT * FROM program WHERE production = 0;
    UPDATE temp_export SET id = NULL, timestamp = NULL, production = 1;
    INSERT INTO program SELECT * FROM temp_export;
    DROP TEMPORARY TABLE IF EXISTS temp_export;
    COMMIT;
  ";  
  $result = $conn->multi_query($sql);  
  //print mysqli_error($conn);
