<?php

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  
  $sql = "
    START TRANSACTION;
    DELETE FROM program WHERE production = 0;
    CREATE TEMPORARY TABLE temp_import SELECT * FROM program WHERE production = 1;
    UPDATE temp_import SET id = NULL, timestamp = NULL, production = 0;
    INSERT INTO program SELECT * FROM temp_import;
    DROP TEMPORARY TABLE IF EXISTS temp_import;
    COMMIT;
  ";
    
  $database->conn->multi_query($sql);
	$database->conn->close();