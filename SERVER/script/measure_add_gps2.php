<?php
  
  include "../class/Config.class.php";
  include "../class/Database.class.php";
  include "../class/Measure.class.php";
  
  $database = new Database();
  $database->connect();
    
  $data = file_get_contents('php://input');
  $blocks = explode('|', $data, 4);
  
  $key = $blocks[0];
  $sensor = $blocks[1];
  $voltage = round($blocks[2] / 1024, 2);
  $packet = $blocks[3];
  
  if (!isset ($key) || $key != Config::$key) exit();
  if (isset ($sensor)) $sensor = mysqli_real_escape_string($database->conn, $sensor); else exit();
  if (isset ($voltage)) $voltage = mysqli_real_escape_string($database->conn, $voltage); else exit();    

  //Get last measure stored in the database
  $lastdatetime = null;
  $measure = Measure::loadLastMeasure($database, $sensor, Config::$gpsclasssat);
  if ($measure != null) $lastdatetime = new DateTime($measure->timestamp);
  
  //Get last week
  $lastweek = new DateTime();
  $lastweek->modify('-1 week');
  
  //Get next week 
  $nextweek = new DateTime();
  $nextweek->modify('+1 week');
  
  //Analyze sensor data
  $cycles = floor(strlen($packet) / 13);
  $records = array(); 
  for($cycle = 0; $cycle < $cycles; $cycle++)
  {
    try
    {
      $position = $cycle * 13;    
  
      $id  = unpack('V', substr($packet, $position+0, 4));
      $sat = unpack('C', substr($packet, $position+4, 1));
      $lng = unpack('l', substr($packet, $position+5, 4));
      $lat = unpack('l', substr($packet, $position+9, 4));
      
      $id  = $id[1];
      $sat = $sat[1];
      $lng = $lng[1];
      $lat = $lat[1];
      
      $record = array();
      $record['id'] = $id;
      $record['sat'] = $sat;
      $record['lng'] = round($lng/ 10000000, 5);
      $record['lat'] = round($lat/ 10000000, 5);
      
      $date = floor($id / 100000);
      $time = $id - $date*100000;
  
      $year = floor($date / 372);
      $date -= $year*372;
      $month = floor($date / 31);
      $date -= $month*31;
      $day = $date;
  
      $record['year'] = 2000 + $year; 
      $record['month'] = $month; 
      $record['day'] = $day; 
  
      $hour = floor($time / 3600);
      $time -= $hour*3600;
      $min = floor($time / 60);
      $time -= $min*60;
      $sec = $time;
           
      $record['hour'] = $hour; 
      $record['min'] = $min; 
      $record['sec'] = $sec;
  
      $datetime = new DateTime("$year-$month-$day $hour:$min:$sec");
      $datetime->add(new DateInterval('PT'. date('Z').'S')); //adds server timezone
      $timestamp = $datetime->format('Y-m-d H:i:s');
      
      $record['stamp'] = $timestamp;

      if ($lastweek < $datetime && $nextweek > $datetime && floatval($lat) != 0 && floatval($lng) != 0 && intval($sat) != 0) $records[$id] = $record;
    }
    catch (Exception $e) {}    
  }

  //Sort records from the oldest to the newest
  ksort($records);
  foreach ($records as $record) $lastid = $record['id'];
    
  //Database transaction
  $sql = 'START TRANSACTION;';
 
  foreach ($records as $record)
  {
    $datetime = new DateTime($record['stamp']);

    //Only newer measures are stored
    if ($lastdatetime == null || $lastdatetime < $datetime)
    {
      $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1, value2, value3)
      VALUES ('".$record['stamp']."', '$sensor', '".Config::$gpsclass."', 1, '".$record['lat']."', '".$record['lng']."', '".$record['sat']."');";    
  
      $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1)
      VALUES ('".$record['stamp']."', '$sensor', '".Config::$gpsclasslong."', 2, '".$record['lng']."');";
      
      $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1)
      VALUES ('".$record['stamp']."', '$sensor', '".Config::$gpsclasssat."', 3, '".$record['sat']."');";
      
      //Last record has voltage included
      if ($lastid == $record['id'])
      {
        $sql .= "INSERT INTO measure (timestamp, sensor, class, field, value1)
        VALUES ('".$record['stamp']."', '$sensor', '".Config::$gpsclassvcc."', 4, '$voltage');";      
      }
    }
  }
 
  $sql .= 'COMMIT;';

  $database->conn->multi_query($sql);
  $database->conn->close();

  /*file_put_contents('/tmpx', date("D M j G:i:s T Y"). "\n");
  file_put_contents('/tmpx', count($records). "\n", FILE_APPEND);  
  file_put_contents('/tmpx', $lastid. "\n", FILE_APPEND);  
  file_put_contents('/tmpx', $record['id']. "\n", FILE_APPEND);  
  file_put_contents('/tmpx', $cycles. "\n", FILE_APPEND);  
  file_put_contents('/tmpx', $key. "\n", FILE_APPEND);
  file_put_contents('/tmpx', $sensor. "\n", FILE_APPEND);
  file_put_contents('/tmpx', $voltage. "\n", FILE_APPEND);  
  file_put_contents("/tmpx", print_r($records, true), FILE_APPEND);*/
