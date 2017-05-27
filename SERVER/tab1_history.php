<script>
  $( function() {
    $( "#subtabs" ).tabs();
  } );
</script>
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
  $sensorSet = Sensor::loadSelected($database, Params::$sensors);
  $classSet = XClass::loadAll($database); 
  $measureSet = Measure::loadHistory($database, Params::$sensors, Params::$date_sql);
  
  //Display
  if (count($sensorSet) == 0) print 'Není vybrán žádný senzor!';      
  
  //At least one sensor
  if (count($sensorSet) > 1)
  { 
    print '<div id="subtabs"><ul>';
    foreach ($sensorSet as $sensor) print "<li><a href='#subtabs-$sensor->sensor'>$sensor->comment</a></li>";
    print '</ul>';
  }       
  
  //Loop sensors
  foreach ($sensorSet as $sensor)
  {                  
    print "<div id='subtabs-$sensor->sensor'>";
    
    $count = 0;
    foreach($measureSet as $measure) if ($measure->sensor == $sensor->sensor) {$count++; break; }
  
    if ($count > 0)
    {
      $out = '';
      $tabs = '';
      $h = null;
      $lastTimestamp = null;
      $inlineCount = 0;

      //Loop all measures
      foreach($measureSet as $measure)
      {
        //Skip records from other sensors
        if ($measure->sensor != $sensor->sensor) continue;
        
        if ($h != $measure->hour)
        {
          if ($h != null) $out .= "\n</table></div>\n";
          $h = $measure->hour;
          
          $tabs .= "<li><a href='#subsubtabs-$sensor->sensor-$h'>$h</a></li>";
          $out .= "<div id='subsubtabs-$sensor->sensor-$h'><table class='default'>\n";
        }

        if ($lastTimestamp == $measure->timestamp)
        {          
          //Not first hardware
          $inlineCount++;
          if ($inlineCount >= 4)
          {
            $out .= '</tr><tr><td></td>';
            $inlineCount = 1;
          }
          
          $out .= '<td class="right">'. $measure->value1. ' <span style="color: rgba('. Utils::hex2rgb($sensor->color, 1). ')">'. $classSet[$measure->class]->unit. '</span></td>';
          $out .= '<td class="left darker">'. $classSet[$measure->class]->hardware. '</td>';  
        }
        else
        {
          //First hardware
          $inlineCount = 0;
          if ($lastTimestamp != null) print '</tr>';
          $lastTimestamp = $measure->timestamp;
                
          $out .= '<tr>';
          $out .= '<td>';
            $out .= '<div class="floatleft colorbox" style="background: rgba('.Utils::hex2rgb($sensorSet[$measure->sensor]->color, 1). ')"></div>';
            $out .= $measure->time;
          $out .= '</td>';
          $out .= '<td class="right">'. $measure->value1. ' <span style="color: rgba('. Utils::hex2rgb($sensor->color, 1). ')">'. $classSet[$measure->class]->unit. '</span></td>';
          $out .= '<td class="left darker">'. $classSet[$measure->class]->hardware. '</td>';
        }
      }
      $out .= '</tr></table></div>';

      print '<script> $( function() { $( "#subsubtabs-'.$sensor->sensor.'" ).tabs(); } ); </script>';
      print '<div id="subsubtabs-'.$sensor->sensor.'"><ul>';
      print $tabs;
      print '</ul>';
      print $out;
      print '</div>';
    }
    else print 'Nenalezen žádný záznam!';
    
    print '</div>';      
  }

  //At least one sensor
  if (count($sensorSet) > 1) print '</div>';
  
  print '<p>Poslední aktualizace proběhla v '.date("G:i").'</p>';
  print '<button class="ui-button ui-widget ui-corner-all" onclick="reload()">Aktualizovat</button>';

?>
<script>
  function reload()
  {
    $.cookie( "activetab", 1, {expires : 10});
    location.reload();
  }
</script>