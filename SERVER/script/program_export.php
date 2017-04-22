<?php

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();

  if (!isset($_POST['thermopass']) || $_POST['thermopass'] != Config::$passwd) {print Config::$wrongpasswd; exit(); }; 
  
  $sql = "
    START TRANSACTION;
    DELETE FROM program WHERE production = 1;
    CREATE TEMPORARY TABLE temp_export SELECT * FROM program WHERE production = 0;
    UPDATE temp_export SET id = NULL, timestamp = NULL, production = 1;
    INSERT INTO program SELECT * FROM temp_export;
    DROP TEMPORARY TABLE IF EXISTS temp_export;
    COMMIT;
  ";
    
  $database->conn->multi_query($sql);
	$database->conn->close();