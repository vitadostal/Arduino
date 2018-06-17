<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>ARDUINO | Sensor Reading</title>
  
  <link rel="stylesheet" type="text/css" href="arduino.css?version=4">
  <link rel="stylesheet" href="/jquery/jquery-ui.css">
  <link rel="stylesheet" href="jquery-hex-colorpicker/css/jquery-hex-colorpicker.css" />

  <script src="jquery/jquery.js"></script>
  <script src="jquery/jquery-ui.js"></script>
  <script src="jquery/jquery.cookie.js"></script>
  <script src="jquery-hex-colorpicker/src/jquery-hex-colorpicker.min.js"></script>

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
    $( "#graphtabs" ).tabs({
    active   : $.cookie("activegraphtab"),
    activate : function( event, ui ){
        $.cookie( "activegraphtab", ui.newTab.index(),{
            expires : 10
        });
      }
    });
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

  //Include
  include "class/Config.class.php";
  include "class/Params.class.php";
  include "class/Database.class.php";
  include "class/Utils.class.php";
  include "class/Sensor.class.php";
  include "class/Graph.class.php";
  include "class/Measure.class.php";
  Params::get();
  Params::advanced();

  //Database
  $database = new Database();
  $database->connect();
  
  //Objects
  $graphSet = Graph::loadAll($database, true);
  $sensorSet = Sensor::loadAll($database, true, true);
  $allSensorArray = Sensor::arrayAll($database);  
  $visibleSensorArray = Sensor::arrayVisible($database);
   
  //Use implicit when no sensor selected
  if (empty(Params::$sensors) && Params::$implicit) Params::$sensors = Sensor::arrayImplicit($database);    

  //Remove not existing sensor references
  $allSensorArray = Sensor::arrayAll($database);    
  foreach(Params::$sensors as $key=>$val) if (!in_array($val, $allSensorArray)) unset(Params::$sensors[$key]); 

  //Header form
  print '<form id="form" method="GET" action="/">';
    
    //Sensors
    print '<div class="floatleft right20">';
      print '<span class="master">Senzory:</span>';
      foreach ($sensorSet as $sensor)
      {
        if (!in_array($sensor->sensor, Params::$sensors) && !in_array($sensor->sensor, $visibleSensorArray)) continue;
        print '<label for="checkbox-'. $sensor->sensor. '">'. $sensor->comment. '</label>';
        print '<input type="checkbox" class="ticker" name="sensor[]" id="checkbox-'. $sensor->sensor. '" value="'. $sensor->sensor. '" onchange="$(\'#form\').submit()" ';
          if (in_array($sensor->sensor, Params::$sensors)) print 'checked="checked" ';
        print '/>&nbsp;';
      }
      print '<button class="ui-button ui-widget ui-corner-all" onclick="$(\'.ticker\').removeAttr(\'checked\');" >✖</button> ';
      print '<button class="ui-button ui-widget ui-corner-all" onclick="$(\'.ticker\').prop(\'checked\',true);" >✔</button>';
    print '</div>';   
    //Calendar
    print '<div class="floatleft">';
      print '<span class="master">Datum:</span>'; 
      print '<button onclick="$(\'#datepicker\').val(\''. Params::$date_czech_prev. '\')"  
              class="ui-button ui-widget ui-corner-all">❰❰ Předchozí den</button>&nbsp;';
      print '<input type="text" name="date" id="datepicker" value="'. Params::$date_czech. '" 
              class="ui-button ui-widget ui-corner-all white" onchange="$(\'#form\').submit()" />&nbsp;';
      print '<button onclick="$(\'#datepicker\').val(\''. Params::$date_czech_next. '\')"
              class="ui-button ui-widget ui-corner-all">Následující den ❱❱</button>';                                          
    print '</div>';
    
    print '<div class="clear"></div>';  
  print '</form>';

  //Title
  print '<h1>'. Params::$title. '</h1>';
?>

<div id="tabs">
  <ul>
    <li><a href="#tabs-1">Grafy</a></li>
    <li><a href="#tabs-2">Měření</a></li>
    <li><a href="#tabs-3">Aktuálně</a></li>
    <li><a href="#tabs-4">Senzory</a></li>
    <li><a href="#tabs-5">Programy</a></li>
    <li><a href="#tabs-6">Termostat</a></li>
    <li><a href="#tabs-7">Mapa</a></li>
  </ul>
  
  <!-- GRAPH =============================================================== -->
  <div id="tabs-1">
    <?php
      print '<div id="graphtabs">';
        print '<ul>';
          foreach ($graphSet as $graph)
          {             
            $chart = 'chart.php?'. Utils::sensorQuery(Params::$sensors). '&graph='. $graph->graph. '&date='. Params::$date_czech; 
             
            print '<li><a href="#'. $graph->graph. '"
              onclick=\'$("div#svg-'. $graph->graph. '").load("'. $chart. '")\'
              >';
              print $graph->description;
            print '</a></li>';
          }
        
        print '</ul>';
      
        $i = 0;
        foreach ($graphSet as $graph)
        {
          $chart = 'chart.php?'. Utils::sensorQuery(Params::$sensors). '&graph='. $graph->graph. '&date='. Params::$date_czech; 

          //Prepare area for the graph
          print '<div class="graph" id="'. $graph->graph. '">';
            print '<div id="svg-'. $graph->graph. '">';
            print '</div>';
            print '<button onclick=\'window.open("'. $chart. '&new=1")\' class="ui-button ui-widget ui-corner-all">Zobrazit graf v novém okně</button>';
            print '<p>Grafy se automaticky obnovují každých 60 sekund</p>';      
          print '</div>';

          //Load graph for the currently opened tab
          if (!isset($_COOKIE['activegraphtab']) || $i == $_COOKIE['activegraphtab'])
          {
            print '<script>';
              print '$("div#svg-'. $graph->graph. '").load("'.$chart. '")';          
            print '</script>';
          }
          $i++;
        }

      print '</div>';
    ?>
  </div>

  <!-- MEASURE ============================================================= -->
  <div id="tabs-2">
    <div id="target-history">Data budou načtena za necelých 5 sekund...<br />Toto zpoždění je záměrné kvůli rychlejšímu vykreslování webu na pomalejších sítích.  
      <script>
        var measureLoaded = false;
        function measureLoad()
        {
          if (!measureLoaded)
          {
            $('div#target-history').load('tab1_history.php?<?php print Utils::sensorQuery(Params::$sensors) ?>&date=<?php print Params::$date_czech ?>');
            measureLoaded = true;
          }
        }
        setInterval(measureLoad, 5000);
      </script>
    </div>    
  </div>  

  <!-- CURRENT ============================================================= -->
  <div id="tabs-3">
    <div id="target-current">Data se načítají...   
      <script>
        setInterval( function() {
          $('div#target-current').load('tab2_current.php?<?php print Utils::sensorQuery(Params::$sensors) ?>&date=<?php print Params::$date_czech ?>');
        }, 5000);
      $('div#target-current').load('tab2_current.php?<?php print Utils::sensorQuery(Params::$sensors) ?>&date=<?php print Params::$date_czech ?>');
      </script>
    </div>    
  </div>
  
  <!-- SENSOR ============================================================== -->
  <div id="tabs-4">
    <div id="target-sensor">Data se načítají...   
      <script>
         $('div#target-sensor').load('tab3_sensor.php?date=<?php print Params::$date_czech ?>');
      </script>
    </div>  
  </div>
  
  <!-- PROGRAM ============================================================= -->
  <div id="tabs-5">
    <div id="target-program">Data se načítají...   
      <script>
         $('div#target-program').load('tab4_program.php?date=<?php print Params::$date_czech ?>');
      </script>
    </div>  
  </div>
  
  <!-- THERMOSTAT ========================================================== -->
  <div id="tabs-6">
    <div id="target-thermostat">Data se načítají...   
      <script>
         $('div#target-thermostat').load('tab5_thermostat.php?date=<?php print Params::$date_czech ?>');
      </script>
    </div>
  </div>
  
  <!-- MAP ================================================================= -->
  <div id="tabs-7">
    <div id="target-map">Data se načítají...   
      <script>
         $('div#target-map').load('tab6_map.php?<?php print Utils::sensorQuery(Params::$sensors) ?>&date=<?php print Params::$date_czech ?>');
      </script>
    </div>  
  </div>      

</div>

<p class='footer'>© 2017 Vítězslav Dostál</p>

</body>  
</html>