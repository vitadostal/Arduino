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
    
    if (isset($dataset[$display->sensor][$display->class]) && is_array($dataset[$display->sensor][$display->class]))
    foreach (($dataset[$display->sensor][$display->class]) as $row)
    {
      if ($comma2 == false) {$comma2 = true;} else {print ',';}
      print '[';
        if (!$row['yesterday'])
          print ($row['hour']*60*60*1000 + $row['minute']*TIME_STEP*60*1000 -3600000);
        else
          print ($row['hour']*60*60*1000 + $row['minute']*TIME_STEP*60*1000 -3600000 -86400000);
      print ',';
        print($row['value']);
      print ']';
    }
    
    if ($comma2 == false) {print "[0, null]";}

    print ("\n". '    ]'. "\n");
    print ('  }'. "\n");
  }
  
  print (']'. "\n");