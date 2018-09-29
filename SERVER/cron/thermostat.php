#!/usr/bin/php
<?php

  //Include
  include "/home/vdostal/public_html/arduino/class/Config.class.php";
  include "/home/vdostal/public_html/arduino/class/Database.class.php";
  include "/home/vdostal/public_html/arduino/class/Measure.class.php";
  include "/home/vdostal/public_html/arduino/class/Program.class.php";
  include "/home/vdostal/public_html/arduino/class/Params.class.php";
  $filename = "/home/vdostal/public_html/arduino/fetch/power.txt";
  Params::get();

  //Database
  $database = new Database();
  $database->connect();

  //Objects
  $programSet = Program::loadAllForDay($database, Params::$date_sql, Params::$date_day);  
  $programPowerOn = Program::loadByProductionTitle($database, Config::$poweron);

  //Current Time
  $now = new DateTime();
  
  //Last Measure
  $measureTemp = Measure::loadLastMeasure($database, Config::$tempsensor_in, Config::$tempclass_in);
  if (!isset($measureTemp->value1)) {return;} else {$temperature = $measureTemp->value1;}
   
  //Time simulation
  $programFinalSet = array();
  if (count($programSet) > 0)
  {
    $lastid = -1;
    
    for ($minute = 0; $minute < 1440; $minute++)    
    {
      foreach($programSet as $program)
      {
        if ($program->fhour*60 + $program->fminute <= $minute && $program->thour*60 + $program->tminute >= $minute) //Match found
        {               
          if ($lastid != $program->id) //Already displayed?
          {
            $lastid = $program->id;

            //Prepare program set
            $start = new DateTime(date("Y-m-d"));
            $start->modify("+". $minute. " minutes");
            $program->from_time = $start;
            $programFinalSet[] = clone $program;
          }
          break;
        }
      }
    }
  }

  //Reverse intervals
  $programFinalSet = array_reverse($programFinalSet);
  
  //Read hysteresis status
  $file = fopen($filename,"r");
  $data = fgets($file);
  if (strpos($data, '<ON>') !== false) {$on = true;} else {$on = false;}
  fclose($file); 

  //Evaluate
  foreach($programFinalSet as $program)
  {
    if ($now >= $program->from_time)
    {
      //Time to stop heating      
      if ($on && $temperature >= $program->max)
      {
        file_put_contents($filename, "<OFF>");
      }
      //Time to start heating
      if (!$on && $temperature < $program->min)
      {
        file_put_contents($filename, "<ON>");
      }
      return;
    }
  }