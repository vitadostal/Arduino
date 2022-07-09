<?php

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  $columns = 'title, color, priority, production, sun, mon, tue, wed, thu, fri, sat, from_time, to_time, from_date, to_date, min, max';
  
  if (!isset($_POST['thermopass']) || ($_POST['thermopass'] != Config::$passwd && $_SERVER['REMOTE_ADDR'] != Config::$ipaddress)) {print Config::$wrongpasswd; exit(); };
  
  //Import
  $sql = "
    START TRANSACTION;
    DELETE FROM program WHERE production = 0;
    CREATE TEMPORARY TABLE temp_import SELECT * FROM program WHERE production = 1;
    UPDATE temp_import SET production = 0;
    INSERT INTO program ($columns) SELECT $columns FROM temp_import;
    DROP TEMPORARY TABLE IF EXISTS temp_import;
    COMMIT;
  ";
  //Change  
  $sql.= "UPDATE program SET priority = 7 WHERE production = 0 AND title='".Config::$poweron."';";
  $sql.= "UPDATE program SET priority = 6 WHERE production = 0 AND title='".Config::$poweroff."';";
  //Export
  $sql.= "
    START TRANSACTION;
    DELETE FROM program WHERE production = 1;
    CREATE TEMPORARY TABLE temp_export SELECT * FROM program WHERE production = 0;
    UPDATE temp_export SET production = 1;
    INSERT INTO program ($columns) SELECT $columns FROM temp_export;
    DROP TEMPORARY TABLE IF EXISTS temp_export;
    COMMIT;
  ";

  $database->conn->multi_query($sql);
	$database->conn->close();