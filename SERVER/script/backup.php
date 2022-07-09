#!/usr/bin/php
<?php
  
  include "/home/vdostal/public_html/arduino/class/Config.class.php";
  include "/home/vdostal/public_html/arduino/class/Database.class.php";

  $database = new Database();
  $database->connect();

  $sql = 'SELECT MAX(timestamp) as timestamp FROM measure WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)';
  
  $result = $database->conn->query($sql);
  $data = $result->fetch_all(MYSQLI_ASSOC);
  $timestamp = $data[0]['timestamp'];
  
  if ($result->num_rows == 1 && $timestamp != null)
  {
    $sql  = 'START TRANSACTION;';
    $sql .= 'INSERT INTO backup SELECT * FROM measure WHERE timestamp < "'. $timestamp. '";';
    $sql .= 'DELETE FROM measure                      WHERE timestamp < "'. $timestamp. '";';
    $sql .= 'COMMIT;';
  
    $database->conn->multi_query($sql);
  }	

  $database->conn->close();
