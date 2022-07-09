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

  //Number of satellites
  Measure::setMinSatellites($database, 3);
  
  //Objects
  $sensorSet = Sensor::loadAll($database, true);  

  //Display
  print '<div id="subtabs"><ul>';
    print "<li><a href='#subtabs-24h'>Posledních 24 hodin</a></li>";
    //print "<li><a href='#subtabs-hour'>Poslední hodina</a></li>";
    //print "<li><a href='#subtabs-now'>Aktuálně</a></li>";
  print '</ul>';

  //print "<div id='subtabs-now'>"; 
  //print "</div>";

  //print "<div id='subtabs-hour'>"; 
  //print "</div>";

  print "<div class='graph' id='subtabs-24h' >";
    print '<script>';
      print 'function initMap() {';
      print '  var prague = {lat: 50.0755,   lng: 14.4378};';
      print '  var zdar   = {lat: 49.677214, lng: 15.880990};';
      print '  var map = new google.maps.Map(document.getElementById("subtabs-24h"), {';
      print '    zoom: 7,';
      print '    center: zdar';
      print '  });';
        
      foreach ($sensorSet as $sensor)
      {
        if (!in_array($sensor->sensor, Params::$sensors)) continue;
        
        print 'var pin'. $sensor->sensor. ' = new google.maps.MarkerImage("http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|'. substr($sensor->color, 1, 6). '",';
        print ' new google.maps.Size(21, 34),';
        print ' new google.maps.Point(0,0),';
        print ' new google.maps.Point(10, 34));';              
      }
      
      $measureSet = Measure::loadClassNewsLastMinutes($database, Config::$gpsclass, Params::$sensors, 10 * 60*24); //60*24 = 1 day
      foreach($measureSet as $id => $measure)
      {                             
        print 'var point'. $id. ' = {lat: '. $measure->value1. ', lng: '. $measure->value2. '};';
        print 'var marker = new google.maps.Marker({';
        print '  position: point'. $id. ',';
        print '  title: "'. $measure->date. ' | '. $sensorSet[$measure->sensor]->comment. ' | '. $measure->time. '",'; 
        print '  icon: pin'. $measure->sensor. ',';
        print '  map: map';
        print '});';
        
        if (isset($lastMeasure[$measure->sensor]))
        {
          print 'var lineCoordinates = [';
          print '  {lat: '. $lastMeasure[$measure->sensor]->value1. ', lng: '. $lastMeasure[$measure->sensor]->value2. '},';
          print '  {lat: '. $measure->value1. ', lng: '. $measure->value2. '}';
          print '];';
          print 'var linePath = new google.maps.Polyline({';
          print '  path: lineCoordinates,';
          print '  geodesic: true,';
          print '  strokeColor: "#000000",';
          print '  strokeOpacity: 1.0,';
          print '  strokeWeight: 2';
          print '});';
          print 'linePath.setMap(map);';
        }        
        $lastMeasure[$measure->sensor] = $measure;
      }

      print '}';
    print '</script>';
    print '<script async defer src="https://maps.googleapis.com/maps/api/js?key='. Config::$gmapskey .'&callback=initMap"></script>';
    
  print '</div>';