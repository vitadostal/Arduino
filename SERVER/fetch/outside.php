<?php

  //Include
  include "../class/Config.class.php";
  include "../class/Database.class.php";
  include "../class/Measure.class.php";
  
  //Database
  $database = new Database();
  $database->connect();

  //Objects
  $measure = Measure::loadLastMeasure($database, SENSOR_OUT, CLASS_OUT);
  print $measure->value1;  