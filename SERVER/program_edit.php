<?php

  function prepare($data)
  {
    if ($data === null) return 'NULL';
    return '"'.$data.'"';
  }

  //Load variables
  if (!isset($_POST['id'])) exit;

  if (!isset($_POST['title'])) exit;
  if (!isset($_POST['color'])) exit;
  if (!isset($_POST['priority'])) exit;
  
  if (!isset($_POST['from_date'])) exit;
  if (!isset($_POST['to_date'])) exit;
  if (!isset($_POST['from_time'])) exit;
  if (!isset($_POST['to_time'])) exit;

  if (!isset($_POST['max'])) exit;
  if (!isset($_POST['min'])) exit;

  if (!isset($_POST['sun'])) $_POST['sun'] = 0;
  if (!isset($_POST['mon'])) $_POST['mon'] = 0;
  if (!isset($_POST['tue'])) $_POST['tue'] = 0;
  if (!isset($_POST['wed'])) $_POST['wed'] = 0;
  if (!isset($_POST['thu'])) $_POST['thu'] = 0;
  if (!isset($_POST['fri'])) $_POST['fri'] = 0;
  if (!isset($_POST['sat'])) $_POST['sat'] = 0;
  
  //DB
  include "dbase.php";
  
  //Escape variables
  $id         = mysqli_real_escape_string($conn, $_POST['id']);

  $title      = mysqli_real_escape_string($conn, $_POST['title']);
  $color      = mysqli_real_escape_string($conn, $_POST['color']);
  $priority   = mysqli_real_escape_string($conn, $_POST['priority']);  
  $max        = mysqli_real_escape_string($conn, $_POST['max']);
  $min        = mysqli_real_escape_string($conn, $_POST['min']);
  
  $from_date  = mysqli_real_escape_string($conn, $_POST['from_date']);
  $to_date    = mysqli_real_escape_string($conn, $_POST['to_date']);
  $from_time  = mysqli_real_escape_string($conn, $_POST['from_time']);
  $to_time    = mysqli_real_escape_string($conn, $_POST['to_time']);

  $sun        = mysqli_real_escape_string($conn, $_POST['sun']);
  $mon        = mysqli_real_escape_string($conn, $_POST['mon']);
  $tue        = mysqli_real_escape_string($conn, $_POST['tue']);
  $wed        = mysqli_real_escape_string($conn, $_POST['wed']);
  $thu        = mysqli_real_escape_string($conn, $_POST['thu']);
  $fri        = mysqli_real_escape_string($conn, $_POST['fri']);
  $sat        = mysqli_real_escape_string($conn, $_POST['sat']);
  
  //Comma replace
  $min = str_replace(',', '.', $min);  
  $max = str_replace(',', '.', $max);
  
  //Check variables
  if ($title     == '') return;

  if ($title     == '') return;
  if ($color     == '') return;
  if ($priority  == '') return;
  if ($min       == '') $min = $max - $hysteresis;
  if ($max       == '') {$max = null; $min = null;}

  if ($from_date == '') {$from_date = null; $to_date = null;}
  if ($to_date   == '') {$from_date = null; $to_date = null;}
  if ($from_time == '') return;
  if ($to_time   == '') return;

  if ($sun       == '') $sun = 0;
  if ($mon       == '') $mon = 0;
  if ($tue       == '') $tue = 0;
  if ($wed       == '') $wed = 0;
  if ($thu       == '') $thu = 0;
  if ($fri       == '') $fri = 0;
  if ($sat       == '') $sat = 0;
  
  //Date fix
  if ($from_date != null) {$obj = new DateTime($from_date); $from_date = $obj->format("Y-m-d");}
  if ($to_date   != null) {$obj = new DateTime($to_date);   $to_date   = $obj->format("Y-m-d");}
  
  //Time fix
  $from_time .= ':00';
  $to_time   .= ':59';  
  
  //Constraints
  if ($priority < 1) return;
  if ($priority > 5) return;

  //Defaults
  $production = 0;
  
  //Apostrophes and nulls
  $id         = prepare($id);
  $title      = prepare($title);
  $color      = prepare($color);
  $priority   = prepare($priority);
  $production = prepare($production);  
  $sun        = prepare($sun);
  $mon        = prepare($mon);
  $tue        = prepare($tue);
  $wed        = prepare($wed);
  $thu        = prepare($thu);
  $fri        = prepare($fri);
  $sat        = prepare($sat);
  $from_time  = prepare($from_time);
  $to_time    = prepare($to_time);
  $from_date  = prepare($from_date);
  $to_date    = prepare($to_date);
  $min        = prepare($min);
  $max        = prepare($max);
 
  //SQL 
  $sql = "UPDATE program SET title=$title, color=$color, priority=$priority, production=$production, ".
    "sun=$sun, mon=$mon, tue=$tue, wed=$wed, thu=$thu, fri=$fri, sat=$sat, ".
    "from_time=$from_time, to_time=$to_time, from_date=$from_date, to_date=$to_date, min=$min, max=$max ". 
    "WHERE id=$id AND production=0 AND priority <= 5 AND priority >= 1";     
  $result = $conn->query($sql);
