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
  $measureTemp = Measure::loadLastMeasure($database, Config::$tempsensor_out, Config::$tempclass_out);
  $measureHmdt = Measure::loadLastMeasure($database, Config::$hmdtsensor_out, Config::$hmdtclass_out);
  $measurePres = Measure::loadLastMeasure($database, Config::$pressensor_out, Config::$presclass_out);

  if (isset($measureTemp->value1)) print $measureTemp->value1;
  print ';';  
  if (isset($measureHmdt->value1)) print $measureHmdt->value1;
  print ';';  
  if (isset($measurePres->value1)) print $measurePres->value1;