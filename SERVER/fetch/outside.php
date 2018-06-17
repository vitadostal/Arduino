<?php
  header('Content-type: text/plain');

  //Include
  include "../class/Config.class.php";
  include "../class/Database.class.php";
  include "../class/Measure.class.php";
  
  //Database
  $database = new Database();
  $database->connect();

  //Objects
  $measureTemp = Measure::loadLastMeasure($database, TEMP_SENSOR_OUT, TEMP_CLASS_OUT);
  $measureHmdt = Measure::loadLastMeasure($database, HMDT_SENSOR_OUT, HMDT_CLASS_OUT);
  $measurePres = Measure::loadLastMeasure($database, PRES_SENSOR_OUT, PRES_CLASS_OUT);

  if (isset($measureTemp->value1)) print $measureTemp->value1;
  print ';';  
  if (isset($measureHmdt->value1)) print $measureHmdt->value1;
  print ';';  
  if (isset($measurePres->value1)) print $measurePres->value1;