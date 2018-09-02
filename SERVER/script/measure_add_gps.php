<?php
  
  include "../class/Config.class.php";
  include "../class/Database.class.php";
  include "../class/Measure.class.php";
  
  $database = new Database();
  $database->connect();
    
  if (!isset ($_POST['key']) || $_POST['key'] != Config::$key) exit();
  if (isset ($_POST['sensor'])) $sensor = mysqli_real_escape_string($database->conn, $_POST['sensor']); else exit();
  if (isset ($_POST['data'])) $data = mysqli_real_escape_string($database->conn, $_POST['data']); else exit();

  //Get last measure stored in the database
  $lastdatetime = null;
  $measure = Measure::loadLastMeasure($database, $sensor, Config::$gpsclasssat);
  if ($measure != null) $lastdatetime = new DateTime($measure->timestamp);

  $sql = 'START TRANSACTION;';

  $records = explode ('|', $data); 
  if (!empty($records)) array_shift($records); //delete first incomplete record
  
  foreach ($records as $record)
  {
    $blocks = explode(';', $record);    

    try
    {
      //Block example: 160618;174732;3;49.29111;17.41111 => date;time;sat;lat;lng
      $blocks[2] = floatval($blocks[2]);
      $blocks[3] = floatval($blocks[3]);
      $blocks[4] = floatval($blocks[4]);
  
      $day   = substr($blocks[0], 0, 2);
      $month = substr($blocks[0], 2, 2);
      $year  = '20'. substr($blocks[0], 4, 2); //nobody will run this in the 22nd century
      $hour  = substr($blocks[1], 0, 2);
      $min   = substr($blocks[1], 2, 2);
      $sec   = substr($blocks[1], 4, 2);
  
      $datetime = new DateTime("$year-$month-$day $hour:$min:$sec");
      $datetime->add(new DateInterval('PT'. date('Z').'S')); //adds server timezone
      $timestamp = $datetime->format('Y-m-d H:i:s');
  
      //Only newer measures are stored
      if ($lastdatetime == null || $lastdatetime < $datetime)
      {
        if ($blocks[3] != 0)
        {
          $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1, value2, value3)
          VALUES ('$timestamp', '$sensor', '".Config::$gpsclass."', 1, $blocks[3], $blocks[4], $blocks[2]);";    
      
          $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1)
          VALUES ('$timestamp', '$sensor', '".Config::$gpsclasslong."', 2, $blocks[4]);";
          
          $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1)
          VALUES ('$timestamp', '$sensor', '".Config::$gpsclasssat."', 3, $blocks[2]);";
        }
        else
        {
          $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1)
          VALUES ('$timestamp', '$sensor', '".Config::$gpsclasssat."', 1, $blocks[2]);";
        }
      }      
    }
    catch (Exception $e) {}
  }

  $sql .= 'COMMIT;';

  $database->conn->multi_query($sql);
  $database->conn->close();