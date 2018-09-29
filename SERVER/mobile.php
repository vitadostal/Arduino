<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Topení</title>

  <meta name="viewport" content="width=device-width, initial-scale=2">
  
  <link rel="stylesheet" type="text/css" href="arduino.css?version=4">
  <link rel="stylesheet" href="jquery/jquery-ui.css">

  <script src="jquery/jquery.js"></script>
  <script src="jquery/jquery-ui.js"></script>
  
  <script>
  $( function() {
    $( "#tabs" ).tabs();
  });
  </script>  
  
</head>
<body style="width: 480px;">
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">Topení dnes</a></li>
  </ul>

<div id="tabs-1">
<?php

  //Include
  include "class/Config.class.php";
  include "class/Database.class.php";
  include "class/Program.class.php";
  include "class/Params.class.php";
  Params::get();

  //Database
  $database = new Database();
  $database->connect();

  //Objects
  $programSet = Program::loadAllForDay($database, Params::$date_sql, Params::$date_day);  
  $programPowerOn = Program::loadByProductionTitle($database, Config::$poweron);
   
  //Display  
  if (count($programSet) > 0)
  {
    print '<p>Tabulka ukazuje, v kolik hodin se dnes spustí jednotlivé programy';
    print '<table id="program" class="default">';
    print '<tr>';
      print '<th>Program & priorita</th>';
      print '<th class="hide period">Období</th>';
      print '<th class="time">Zahájení</th>';
      print '<th class="temp">Teplota</th>';
      print '<th class="hyst">Hystereze</th>';
    print '</tr>';

    $lastid = -1;
    $manual = 2;
    
    for ($minute = 0; $minute < 1440; $minute++)    
    {
      foreach($programSet as $program)
      {
        if ($program->fhour*60 + $program->fminute <= $minute && $program->thour*60 + $program->tminute >= $minute) //Match found
        {               
          if ($lastid != $program->id) //Already displayed?
          {
            $lastid = $program->id;
            if ($program->title == Config::$poweroff) $manual = 0;
            if ($program->title == Config::$poweron) $manual = 1;
            
            print '<tr>';
              print '<td>';
                if (defined('Program::'. $program->color))
                  $color = constant('Program::'. $program->color);
                else
                  $color = Program::GREY; 
                print '<div class="floatleft vert2 colorbox-prog" style="background-color: '. $color. '">'. $program->priority.'</div>';        
                print $program->title;
              print '</td>';
              
              print '<td class="hide"></td>';
              print '<td>'. str_pad(floor($minute / 60), 2, '0', STR_PAD_LEFT). ':'. str_pad(($minute % 60), 2, '0', STR_PAD_LEFT). '</td>';
                    
              if ($program->min != '' && $program->max != '')
              {
                print '<td class="temp" style="color:'. $color. '"><b>'. $program->max. '</b> °C</td>';
                print '<td class="hyst">'. $program->min. ' °C</td>';
              }
              else print '<td colspan="2" class="border-right"><i>vytápění vypnuto<i></td>'; 
            print '</tr>';
          }
          
          break;          
        }        
      }                       
    }    
    print '</table>';
  }
?>

<script>
  function sent()
  {
    $("body").css("opacity", 0.3);
    $("button").prop('disabled', true);
    setTimeout(function() {location.reload();}, 2000);    
  }

  function program_off() 
  {     
    $.ajax({
      method: "POST",
      url: "./script/thermostat_off.php",
      data: {
        thermopass: "N/A",
      }              
    })
      .done(function(data) { 
        sent();
      });    
  }
  
  function program_control() 
  {     
    $.ajax({
      method: "POST",
      url: "./script/thermostat_program.php",
      data: {
        thermopass: "N/A",
      }              
    })
      .done(function(data) { 
        sent();
      });    
  }
</script>

  <p>
    <button class="ui-button <?php if ($manual == 0) print "ui-state-active" ?> ui-widget ui-corner-all" onclick="program_off()">✋ Vypnout topení</button>
    <br /><br />
    <button class="ui-button <?php if ($manual == 2) print "ui-state-active" ?> ui-widget ui-corner-all" onclick="program_control()">✿ Řídit programem </button>
  </p>

</div>

</body>
</html>