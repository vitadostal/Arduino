<?php

  //Include
  include "class/Config.class.php";
  include "class/Params.class.php";
  include "class/Database.class.php";
  include "class/Utils.class.php";
  include "class/Sensor.class.php";
  include "class/Class.class.php";
  include "class/Display.class.php";
  include "class/Graph.class.php";
  Params::get();

  //Database
  $database = new Database();
  $database->connect();
  
  //Objects
  $sensorSet = Sensor::loadAll($database, true);  
  $classSet = XClass::loadAll($database);  
  $displaySet = Display::loadAll($database);
  $graphSet = Graph::loadAll($database);
  
  //Display
  if (!empty($sensorSet))
  {
    print '<p>Změnu názvu senzoru, čidla, kódu barvy nebo referenčního grafu potvrdíte klávesou ENTER. Kliknutím na identifikátor senzoru zobrazíte jeho aktuálně naměřené hodnoty.</p>';
    print '<form id="form-sensor" method="GET" action="/" onsubmit="return false">';
    print '<table class="default">';
    
    foreach($sensorSet as $sensor)
    {
      if (isset($_COOKIE[$sensor->sensor]) && $_COOKIE[$sensor->sensor] == 1)
      {
        $show = ''; $hide = 'hide ';
      }
      else
      {
        $show = 'hide '; $hide = '';      
      }
      
      print '<tr>';
        print '<td class="mediumtext">';
          print '<input type="checkbox" name="sensor[]" value="'. $sensor->sensor. '"> ';
          print '<a onclick="$.cookie(\'activetab\', 2, {expires : 10});"
            href="?sensor='. $sensor->sensor. '&amp;date='. Params::$date_czech. '">'. $sensor->sensor. '</a>';
          print '<div class="floatright">';
            if ($sensor->implicit) print '<span title="implicitní">❤</span>&nbsp;';
            if ($sensor->visible) print '<span title="viditelné">⛺</span>';
          print '</div>';
        print '</td>';
  
        print '<td class="longertext">';
          print '<input class="classic not-sent"
            onchange="rename(\''. $sensor->sensor. '\', this)"
            name="comment-'. $sensor->sensor. '"
            value="'. $sensor->comment. '"
            type="text" />';
        print '</td>';
          
        print '<td class="mediumtext">';
          print '<div class="floatleft colorbox" style="background: rgba('. Utils::hex2rgb($sensor->color, 1). ')"></div>';
          print '<input class="classic not-sent color-picker shortertext"
            onchange="dye(\''. $sensor->sensor. '\', \'\', this)"
            name="color-'. $sensor->sensor. '"
            value="'. $sensor->color. '"
            type="text" />';
        print '</td>';
        
        print '<td class="verylongtext noborder-right">';
          print '<div class="'. $hide. 'show-'. $sensor->sensor. '">► <a onclick="show(\''. $sensor->sensor. '\')">ukázat čidla</a></div>';
          print '<div class="'. $show. 'hide-'. $sensor->sensor. '">▼ <a onclick="hide(\''. $sensor->sensor. '\')">skrýt čidla</a></div>';
        print '</td>';      
      print '</tr>';      

      foreach($classSet as $class)
      {
        print '<tr class="'. $show. 'row-'. $sensor->sensor. '">';
          print '<td></td>';
          
          print '<td>'. $class->class. ': '. $class->description. ' ['. $class->unit .']</td>';

          print '<td>';
            print '<div class="floatleft colorbox"
              style="background: rgba('. Utils::hex2rgb($displaySet[$sensor->sensor][$class->class]->color, 1). ')"></div>';
            print '<input class="classic not-sent color-picker shortertext"
              onchange="dye(\''. $sensor->sensor. '\', \''. $class->class.'\', this)"
              name="color-'. $sensor->sensor. '-'. $class->class. '"
              value="'. $displaySet[$sensor->sensor][$class->class]->color. '"
              type="text" />';
          print '</td>';
          
          print '<td class="noborder-right">';
            print '<select class="classic not-sent"
              onchange="graph(\''. $sensor->sensor. '\', \''. $class->class.'\', this)"
              name="graph-'. $sensor->sensor. '-'. $class->class. '">';
            foreach($graphSet as $graph)
            {
              print '<option ';
              if ($displaySet[$sensor->sensor][$class->class]->graph == $graph->graph) print 'selected="selected" ';
              print 'value='. $graph->graph. '>';
              if ($graph->graph != '') print $graph->description. ' ['. $graph->unit. ']';
              print '</option>';
            }            
            print '</select> &nbsp;';              

            print '<input class="classic not-sent"
              onchange="suffix(\''. $sensor->sensor. '\', \''. $class->class.'\', this)"
              name="suffix-'. $sensor->sensor. '-'. $class->class. '"
              value="'. $displaySet[$sensor->sensor][$class->class]->suffix. '" type="text" />';
          print '</td>';
        print '</tr>';
      }
    }
    print '</table>';
    print '</form>';

    print '<p>';
    print '<button onclick="chart()" class="ui-button ui-widget ui-corner-all">Zobrazit graf vybraných senzorů</button> ';
    print '<button onclick="now()" class="ui-button ui-widget ui-corner-all">Zobrazit aktuální měření vybraných senzorů</button>';
    print '</p>';
  }

?>
<script>
function chart()
{
  var query = '/?';
  $('#form-sensor input[type="checkbox"]:checked').each(function(){
      if (query !== '/?') query += '&';
      query += 'sensor[]=' + ($(this).val());    
  });

  if (query !== '/?') query += '&';  
  query += 'date=' + '<?php print Params::$date_czech ?>';

  $.cookie( "activetab", 0, {expires : 10});
  window.location.href = query; 
}

function now()
{
  var query = '/?';
  $('#form-sensor input[type="checkbox"]:checked').each(function(){
      if (query !== '/?') query += '&';
      query += 'sensor[]=' + ($(this).val());    
  });

  if (query !== '/?') query += '&';  
  query += 'date=' + '<?php print Params::$date_czech ?>';

  $.cookie( "activetab", 2, {expires : 10});   
  window.location.href = query; 
}

function rename(sensor, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");

  $.ajax({
    method: "POST",
    url: "script/sensor_rename.php",
    data: { sensor: sensor, comment: obj.value }
  })
    .done(function() {
      location.reload(); 
    });
}

function suffix(sensor, xclass, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");

  $.ajax({
    method: "POST",
    url: "script/sensor_suffix.php",
    data: { sensor: sensor, class: xclass, suffix: obj.value }
  })
    .done(function() {
      location.reload(); 
    });
}

function dye(sensor, xclass, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");
  
  $.ajax({
    method: "POST",
    url: "script/sensor_dye.php",
    data: { sensor: sensor, class: xclass, color: obj.value }
  })
    .done(function() {
      location.reload();
    });
}

function graph(sensor, xclass, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");
  
  $.ajax({
    method: "POST",
    url: "script/sensor_graph.php",
    data: { sensor: sensor, class: xclass, graph: obj.value }
  })
    .done(function() {
      location.reload();
    });
}

function show(sensor)
{
  $('.show-' + sensor).hide();
  $('.hide-' + sensor).show();
  $.cookie(sensor, 1);
  $('.row-' + sensor).fadeIn();
}

function hide(sensor)
{
  $('.hide-' + sensor).hide();
  $('.show-' + sensor).show();
  $.cookie(sensor, 0);
  $('.row-' + sensor).fadeOut();
}

jQuery(".color-picker").hexColorPicker({
		"container":"dialog"
	});
  
</script>