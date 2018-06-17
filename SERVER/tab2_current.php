<?php

  //Include
  include "class/Config.class.php";
  include "class/Params.class.php";
  include "class/Database.class.php";
  include "class/Utils.class.php";
  include "class/Sensor.class.php";
  include "class/Measure.class.php";
  include "class/Class.class.php";
  Params::get();

  //Database
  $database = new Database();
  $database->connect();
  
  //Objects
  $sensorSet = Sensor::loadAll($database, true);  
  $classSet = XClass::loadAll($database);  
  $measureSet = Measure::loadNews($database, Params::$sensors, 100);
  
  //Display
  if (!empty($measureSet))
  {
    print '<table class="default">';
    $lastTimestamp = null;
    $lastSensor = null;
    $count = 0;
    $inlineCount = 0;   

    foreach($measureSet as $measure)
    {
      if (!isset($sensorSet[$measure->sensor])) $sensorSet[$measure->sensor] = Sensor::createSensor($measure->sensor);
      
      $measure->sensor = $sensorSet[$measure->sensor]; 
      $measure->class = $classSet[$measure->class];
      
      if ($lastTimestamp == $measure->timestamp && $lastSensor == $measure->sensor)
      {
        $inlineCount++;
        if ($inlineCount >= 4)
        {
          print '</tr><tr><td></td><td></td><td></td>';
          $inlineCount = 1;
        }
        
        print '<td class="right">'. $measure->value1. ' <span style="color: rgba('. Utils::hex2rgb($measure->sensor->color, 1). ')">'. $measure->class->unit. '</span></td>';
        print '<td class="left darker">'. $measure->class->hardware. '</td>';      
      }
      else
      {
        $inlineCount = 0;
        if ($count >= 20) break;
        if ($lastSensor != null) print '</tr>';
        $lastTimestamp = $measure->timestamp;
        $lastSensor = $measure->sensor;
                
        print '<tr>';
        print '<td class="longtext">';
        print '<div class="floatleft colorbox" style="background: rgba('. Utils::hex2rgb($measure->sensor->color, 1). ')"></div>';
        print $measure->sensor->sensor.': '. $measure->sensor->comment. '</td>';
        print '<td style="background: rgba('. Utils::hex2rgb($measure->sensor->color, 0.4).')">'. Utils::sql2czech($measure->date). '</td>';
        print '<td style="background: rgba('. Utils::hex2rgb($measure->sensor->color, 0.4).')">'. $measure->time. '</td>';
        print '<td class="right">'. $measure->value1. ' <span style="color: rgba('. Utils::hex2rgb($measure->sensor->color, 1). ')">'. $measure->class->unit. '</span></td>';
        print '<td class="left darker">';
          print $measure->class->hardware;
          //Extra case for GPS, Google Maps link
          if ($measure->class->hardware == 'A7 GPS' && $measure->class->class == 'A7_LAT')
            print ' <a class="nodecor" href="https://www.google.com/search?q='. $measure->value1. '+N+'. $measure->value2. '+E">⛨</a>';
        print '</td>';              
        $count++;
      }
    }
    print '</tr></table>';

    print '<p>Tabulka se automaticky aktualizuje každých 5 sekund. Je zobrazeno 20 nejnovějších záznamů.</p>';    
    print '<button class="ui-button ui-widget ui-corner-all" onclick="window.location.href=\'?date='.Params::$date_czech.'\'" >Zobrazit všechny senzory</button>';
  }
  else
  {
    print 'Nenalezen žádný záznam!';
  }