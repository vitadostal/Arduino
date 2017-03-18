<?php	//if (isset ($_GET['tab']) && is_numeric($_GET['tab'])) $tab = $_GET['tab']; else $tab = 0; ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>ARDUINO | Sensor Reading | Thermostat</title>
  
  <link rel="stylesheet" href="/jquery/jquery-ui.css">
  <link rel="stylesheet" type="text/css" href="arduino.css">  

  <script src="jquery/external/jquery/jquery.js"></script>
  <script src="jquery/jquery-ui.js"></script> 
  <script src="jquery/jquery.cookie.js"></script>

  <script>
  $( function() {
    $( "#tabs" ).tabs({
    active   : $.cookie("activetab"),
    activate : function( event, ui ){
        $.cookie( "activetab", ui.newTab.index(),{
            expires : 10
        });
      }
    });
  } );
  $( function() {
    $( "#subtabs" ).tabs();
  } );
  $( function() {
    $( "#datepicker" ).datepicker({
      dateFormat: "dd.mm.yy",
      monthNames: ['Leden','Únor','Březen','Duben','Květen','Červen','Červenec','Srpen','Září','Říjen','Listopad','Prosinec'],
      dayNamesMin: ['Ne','Po','Út','St','Čt','Pá','So'],
      firstDay: 1      
      });
  } );
  $( function() {
    $( "input:checkbox" ).checkboxradio();
  } );
  </script>
  
</head>
<body>
<?php

//<!-- FUNKCE ============================================================== -->
  function CzechDay($den) {
      static $nazvy = array('Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota');
      return $nazvy[$den];
  }
  function CzechMonth($mesic) {
      static $nazvy = array('', 'Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec',
          'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec');
      return $nazvy[$mesic];
  }
  function SensorQuery($sensors, $delim = false)
  {
     $data = '';
     foreach ($sensors as $sensor)
     {
        if ($data != '') if ($delim) $data .= '&amp;'; else $data .= '&';
        $data .= 'sensor[]='.$sensor;
     }
     return $data;
  }

//<!-- DB ================================================================== -->
  include "dbase.php";
  
  $titles = array();
  $implicit = array();
  $visible = array();
  $sql = "SELECT * FROM sensor";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc())
  {
    $titles[$row['sensor']] = $row['comment'];
    if ($titles[$row['sensor']] == '') $titles[$row['sensor']] = $row['sensor'];
    $visible[$row['sensor']] = $row['visible'];
    if ($row['implicit']) $implicit[] = $row['sensor'];
  }    

//<!-- INPUT =============================================================== -->
	if (isset ($_GET['sensor']))  $sensors = $_GET['sensor']; else $sensors = array();
	if (isset ($_GET['date']))    $date    = $_GET['date'];   else $date    = date('d.m.Y');
  if (!isset ($_GET['date']) && count($sensors) == 0) $sensors = $implicit; 
  if (!is_array($sensors)) {$sensors = array($sensors);}
  foreach($sensors as $key => $val) {$sensors[$key] = mysqli_real_escape_string($conn, $sensors[$key]);}
  $date = mysqli_real_escape_string($conn, $date);   
  foreach($sensors as $key => $sensor) if (!array_key_exists($sensor, $titles)) unset ($sensors[$key]);
  
//<!-- FORM ================================================================ -->
  print '<form id="form" method="GET" action="/">';

    print '<div style="float: left; margin-right: 20px;">';
      print '<span class="master">Senzory:</span>';
      foreach ($titles as $key => $val)
      {
        if (!$visible[$key] && !in_array($key, $sensors)) continue;
        print '<label for="checkbox-'.$key.'">'.$val.'</label>';
        print '<input type="checkbox" name="sensor[]" id="checkbox-'.$key.'" value="'.$key.'" onchange="$(\'#form\').submit()" ';
        if (in_array($key, $sensors)) print 'checked="checked" ';
        print '/>&nbsp;';
      }
    print '</div>';
   
    print '<div style="float: left;">';
      print '<span class="master">Datum:</span>'; 
      $date2 = new DateTime($date);
      $date2->modify('-1 day');   
      print '<button onclick="$(\'#datepicker\').val(\''.$date2->format("d.m.Y").'\')"  
              class="ui-button ui-widget ui-corner-all">❰❰ Předchozí den</button>&nbsp;';
      $date2->modify('+1 day');   
      print '<input style="background-color:white;"  type="text" name="date" id="datepicker" value="'.$date2->format("d.m.Y").'" 
              class="ui-button ui-widget ui-corner-all" onchange="$(\'#form\').submit()" />&nbsp;';
      $date2->modify('+1 day');   
      print '<button onclick="$(\'#datepicker\').val(\''.$date2->format("d.m.Y").'\')"
              class="ui-button ui-widget ui-corner-all">Následující den ❱❱</button>';                                          
    print '</div>';
    
    print '<div style="clear:both"></div>';
  
  print '</form>';
  $date2->modify('-1 day'); 
  print '<h1>'.CzechDay($date2->format('w')).' '.$date2->format("j. ").' '.CzechMonth($date2->format("n")).'</h1>';
  $date = $date2->format("Y-m-d");
?>

<div id="tabs">
  <ul>
    <li><a href="#tabs-1">Grafy</a></li>
    <li><a href="#tabs-2">Měření</a></li>
    <li><a href="#tabs-3">Aktuálně</a></li>
    <li><a href="#tabs-4">Senzory</a></li>
    <li><a href="#tabs-5">Programy</a></li>
    <li><a href="#tabs-6">Termostat</a></li>
  </ul>
  
  <!-- GRAFY =============================================================== -->
  <div id="tabs-1">
    <?php 
      if (count($sensors) > 0)
      {
        print "<p><img id='chartA' src='chart.php?".SensorQuery($sensors, true)."&amp;date=$date&amp;graph=T' alt='Graph'/></p>";
        print "<p><img id='chartV' src='chart.php?".SensorQuery($sensors, true)."&amp;date=$date&amp;graph=H' alt='Graph'/></p>";
    ?>
        <script>
          var sensor = '<?php print SensorQuery($sensors) ?>';
          var date = '<?php print $date ?>';
          
          setInterval(function() {
            var chart1 = document.getElementById('chart1');
            chart1.src = 'chart.php?'+sensor+'&date='+date+'&value=1&rand=' + Math.random();
            var chart2 = document.getElementById('chart2');
            chart2.src = 'chart.php?'+sensor+'&date='+date+'&value=2&rand=' + Math.random();
          }, 60000);  
        </script>
        
        Grafy se automaticky obnovují každých 60 sekund.     
    <?php    
      }
      else print 'Není vybrán žádný senzor!';
    ?>  
  </div>

  <!-- MERENI ============================================================== -->
  <div id="tabs-2">
    <?php   
    
      if (count($sensors) == 0) print 'Není vybrán žádný senzor!';      
	    
      if (count($sensors) > 1)
      { 
        print '<div id="subtabs"><ul>';
        foreach ($sensors as $sensor) print "<li><a href='#subtabs-$sensor'>$titles[$sensor]</a></li>";
        print '</ul>';
      }       
      
	    foreach ($sensors as $sensor)
      {                  
        print "<div id='subtabs-$sensor'>";
      
        $sql = "SELECT HOUR(timestamp) as hour, MINUTE(timestamp) as minute, DATE(timestamp) AS date, TIME(timestamp) AS time,
                value1, value2, value3, value4, value5, text1, text2 FROM measure WHERE (value1 IS NOT NULL OR text1 IS NOT NULL) AND sensor = '$sensor' AND DATE(timestamp) = '$date'";
        $result = $conn->query($sql);
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0)
        {
          $export = '';
          $tabs = '';
          $h = -1;
          while($row = $result->fetch_assoc())
          {
            if ($h != $row['hour'])
            {
              if ($h != -1) $export .= "\n</table></div>\n";
              $h = $row['hour'];
              $tabs .=  "<li><a href='#subsubtabs-$sensor-$h'>$h</a></li>";
              $export .= "<div id='subsubtabs-$sensor-$h'><table>\n";
              $export .= '<tr class="darker"><td colspan="1" style="border:none"></td><td colspan="3">DALLAS</td><td colspan="2">DHT</td><td colspan="2">GPS</td></tr>';
            }
            $export .= '<tr>'; $hour = $row['hour'];        
              $export .= '<td>'.$row['time'].'</td>';
              $export .= '<td class="right">'.$row['value1'].' <span class="unit">&#x2103;</span></td>';
              $export .= '<td class="right">'.$row['value2'].' <span class="unit">&#x2103;</span></td>';
              $export .= '<td class="right">'.$row['value3'].' <span class="unit">&#x2103;</span></td>';
              $export .= '<td class="right">'.$row['value4'].' <span class="unit">&#x2103;</span></td>';
              $export .= '<td class="right">'.$row['value5'].' <span class="unit">%</span></td>';
              $export .= '<td class="right">'.$row['text1'].' <span class="unit">N</span></td>';
              $export .= '<td class="right">'.$row['text2'].' <span class="unit">E</span></td>';
            $export .= '</tr>';      
          }
          $export .= '</table></div>';
  
          print '<script> $( function() { $( "#subsubtabs-'.$sensor.'" ).tabs(); } ); </script>';
          print '<div id="subsubtabs-'.$sensor.'"><ul>';
          print $tabs;
          print '</ul>';
          print $export;
          print '</div>';
        }
        else print 'Nenalezen žádný záznam!';
        
        print '</div>';      
      }
      if (count($sensors) > 1) print '</div>';
    ?>    
  </div>

  <!-- AKTUÁLNĚ ============================================================ -->
  <div id="tabs-3">
    <div id="target-now">Data se načítají...   
      <script>
        setInterval( function() {
          $('div#target-now').load('news.php?<?php print SensorQuery($sensors) ?>&date=<?php print($date2->format("d.m.Y")) ?>');
        }, 5000);
      $('div#target-now').load('news.php?<?php print SensorQuery($sensors) ?>');
      </script>
    </div>    
  </div>
  
  <!-- SENZORY ============================================================= -->
  <div id="tabs-4">
    <div id="target-sensor">Data se načítají...   
      <script>
         $('div#target-sensor').load('sensor.php?date=<?php print($date2->format("d.m.Y")) ?>');
      </script>
    </div>  
  </div>
  
  <!-- PROGRAMY ============================================================ -->
  <div id="tabs-5">
    <div id="target-program">Data se načítají...   
      <script>
         $('div#target-program').load('program.php?date=<?php print($date2->format("d.m.Y")) ?>');
      </script>
    </div>  
  </div>
  
  <!-- TERMOSTAT =========================================================== -->
  <div id="tabs-6">
    <div id="target-termostat">Data se načítají...   
      <script>
         $('div#target-termostat').load('termostat.php?date=<?php print($date2->format("d.m.Y")) ?>');
      </script>
    </div>
  </div>    

</div>

<p class='footer'>© 2017 Vítězslav Dostál</p>

</body>  
</html>