<?php
  header('Content-type: application/json');

  //Include
  include "../class/Config.class.php";
  include "../class/Database.class.php";
  include "../class/Sensor.class.php";
  include "../class/Display.class.php";
  include "../class/Measure.class.php";
  include "../class/Params.class.php";
  Params::get();
  
  //Database
  $database = new Database();
  $database->connect();
  
  //Objects
  $displaySet = Display::loadBySensorsAndGraph($database, Params::$sensors, Params::$graph);
  $dataset = Measure::datasetGraph($database, $displaySet, Params::$date_sql, TIME_STEP);
  
  //Ouput
  print('['. "\n");
  
  $comma1 = false;
  
  foreach($displaySet as $display)
  {
    if ($comma1 == false) {$comma1 = true;} else {print ','. "\n";}

    print('  {'. "\n");
    
    $name = Sensor::load($database, $display->sensor)->comment;  
    if ($name == '') $name = Sensor::load($database, $display->sensor)->sensor;    
    if ($display->suffix != '') $name .= ': '. $display->suffix; 
    
    $color = Sensor::load($database, $display->sensor)->color;
    if ($display->color != '') $color = $display->color;
    if ($color == '') $color = '#888';    
    
    print('    "key": "'. $name. '",'. "\n");
    print('    "color": "'. $color. '",'. "\n");
    print('    "values": ['. "\n");
    
    $comma2 = false;
    
    for ($hour = 0; $hour < 24; $hour++)
  	{
      for ($minute = 0; $minute < (60/TIME_STEP); $minute++)
      {
        if ($comma2 == false) {$comma2 = true;} else {print ',';}
        
        print '[';
          print ($hour*60*60*1000 + $minute*TIME_STEP*60*1000 -3600000);
        print ',';
          if (isset($dataset[$display->sensor][$display->class][$hour][$minute]))
            print($dataset[$display->sensor][$display->class][$hour][$minute]);
          else
            print('null');
        print ']';
      }  
    }

    print ("\n". '    ]'. "\n");
    print ('  }'. "\n");
  }
  
  print (']'. "\n");