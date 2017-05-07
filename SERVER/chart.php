<?php
  //Include
  include "class/Config.class.php";
  include "class/Database.class.php";
  include "class/Utils.class.php";
  include "class/Graph.class.php";
  include "class/Params.class.php";
  Params::get();
  
  //Database
  $database = new Database();
  $database->connect();
  
  //Objects
  $graph = Graph::load($database, Params::$graph);  
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <link href="nv.d3/nv.d3.css" rel="stylesheet" type="text/css">
  <script src="nv.d3/d3.min.js" charset="utf-8"></script>
  <script src="nv.d3/nv.d3.js"></script>

  <style>
    svg {
      display: block;
    }
    <?php if (Params::$new) { ?>
      html, body, #chart-<?php print Params::$graph ?>, svg {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
      }
    <?php } else { ?>
      #chart-<?php print Params::$graph ?>, svg {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 500px;
      }
    <?php } ?>    
  </style>
</head>
<body>

<div id="chart-<?php print Params::$graph ?>">
  <svg></svg>
</div>

<script>
  var hours = new Array();
  for (i = 0; i <= 24; i += 1.5) hours.push(i * 60 * 60 * 1000 -3600000 -86400000);
  for (i = 0; i <= 24; i += 1.5) hours.push(i * 60 * 60 * 1000 -3600000);

  nv.addGraph(function() {
    var chart = nv.models.lineChart()
        .useInteractiveGuideline(true)
        .x(function(d) { return d[0] })
        .y(function(d) { return d[1] })
        .interpolate("basis");

    chart.xAxis
        .axisLabel('Čas [h:m]')
        .tickFormat(function(d) { return d3.time.format('%H:%M')(new Date(d)) });

    chart.yAxis
        .axisLabel('<?php print $graph->description. " [". $graph->unit. "]" ?>')
        .tickFormat(d3.format('.1f'));

    <?php if ($graph->from != null) print 'chart.forceY(['. $graph->from. ', '. $graph->to. ']);' ?>
    
    chart.xAxis.tickValues(hours);    
    
    <?php if (Params::$date_sql != date("Y-m-d")) print "chart.xDomain([new Date(0 -3600000), new Date(24 * 60 * 60 * 1000 -3600000)]);"?>            
    
    d3.json("fetch/day.php?<?php print Utils::sensorQuery(Params::$sensors). '&graph='. Params::$graph. '&date='. Params::$date_czech ?>", function(data) {
      d3.select('#chart-<?php print $graph->graph ?> svg')
        .datum(data)
        .call(chart);
    })

    //Update the chart when window resizes
    nv.utils.windowResize(function() { chart.update() });
    
    //Redraw every 60 seconds
    var timer = setInterval(function() {
      d3.json("fetch/day.php?<?php print Utils::sensorQuery(Params::$sensors). '&graph='. Params::$graph. '&date='. Params::$date_czech ?>", function(data) {
        d3.select('#chart-<?php print $graph->graph ?> svg')
          .datum(data)
          .call(chart);
        console.log('X');
      })   
    }, 60000);     

    return chart;
  });

</script>
</body></html>