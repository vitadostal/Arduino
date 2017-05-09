<?php

  //Load and check variables
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

  //Include
  include "../class/Config.class.php";
  include "../class/Database.class.php";  

  //Database
  $database = new Database();
  $database->connect();
  
  //Escape variables
  $title      = mysqli_real_escape_string($database->conn, $_POST['title']);
  $color      = mysqli_real_escape_string($database->conn, $_POST['color']);
  $priority   = mysqli_real_escape_string($database->conn, $_POST['priority']);  
  $max        = mysqli_real_escape_string($database->conn, $_POST['max']);
  $min        = mysqli_real_escape_string($database->conn, $_POST['min']);
  
  $from_date  = mysqli_real_escape_string($database->conn, $_POST['from_date']);
  $to_date    = mysqli_real_escape_string($database->conn, $_POST['to_date']);
  $from_time  = mysqli_real_escape_string($database->conn, $_POST['from_time']);
  $to_time    = mysqli_real_escape_string($database->conn, $_POST['to_time']);

  $sun        = mysqli_real_escape_string($database->conn, $_POST['sun']);
  $mon        = mysqli_real_escape_string($database->conn, $_POST['mon']);
  $tue        = mysqli_real_escape_string($database->conn, $_POST['tue']);
  $wed        = mysqli_real_escape_string($database->conn, $_POST['wed']);
  $thu        = mysqli_real_escape_string($database->conn, $_POST['thu']);
  $fri        = mysqli_real_escape_string($database->conn, $_POST['fri']);
  $sat        = mysqli_real_escape_string($database->conn, $_POST['sat']);

  //Comma replace
  $min = str_replace(',', '.', $min);
  $max = str_replace(',', '.', $max);

  //Check variables
  if ($title     == '') return;
  if ($color     == '') return;
  if ($priority  == '') return;
  if ($min       == '') $min = $max - Config::$hysteresis;
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
  $title      = Database::prepareData($title);
  $color      = Database::prepareData($color);
  $priority   = Database::prepareData($priority);
  $production = Database::prepareData($production);  
  $sun        = Database::prepareData($sun);
  $mon        = Database::prepareData($mon);
  $tue        = Database::prepareData($tue);
  $wed        = Database::prepareData($wed);
  $thu        = Database::prepareData($thu);
  $fri        = Database::prepareData($fri);
  $sat        = Database::prepareData($sat);
  $from_time  = Database::prepareData($from_time);
  $to_time    = Database::prepareData($to_time);
  $from_date  = Database::prepareData($from_date);
  $to_date    = Database::prepareData($to_date);
  $min        = Database::prepareData($min);
  $max        = Database::prepareData($max);
 
  //SQL
  $sql = "INSERT INTO program
    (title, color, priority, production, sun, mon, tue, wed, thu, fri, sat, from_time, to_time, from_date, to_date, min, max) 
    VALUES ($title, $color, $priority, $production, $sun, $mon, $tue, $wed, $thu, $fri, $sat, $from_time, $to_time, $from_date, $to_date, $min, $max)
  ";     

  $database->conn->query($sql);
	$database->conn->close();