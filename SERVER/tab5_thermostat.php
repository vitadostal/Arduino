<script>

  function reloadMe()
  {
    location.reload();       
  }

  function program_on() 
  {     
    $.ajax({                                                                                                                                                                         
      method: "POST",
      url: "script/thermostat_on.php",
      data: {
        thermopass: $("#thermopass").val(),
        max: $("#max").val(),        
      }              
    })
      .done(function(data) { 
        if (data === "!PASSWD") dialogError.dialog( "open" );
                           else dialogSuccess.dialog( "open" );
      });    
  }
  
  function program_off() 
  {     
    $.ajax({
      method: "POST",
      url: "script/thermostat_off.php",
      data: {
        thermopass: $("#thermopass").val(),
      }              
    })
      .done(function(data) { 
        if (data === "!PASSWD") dialogError.dialog( "open" );
                           else dialogSuccess.dialog( "open" );
      });    
  }
  
  function program_control() 
  {     
    $.ajax({
      method: "POST",
      url: "script/thermostat_program.php",
      data: {
        thermopass: $("#thermopass").val(),
      }              
    })
      .done(function(data) { 
        if (data === "!PASSWD") dialogError.dialog( "open" );
                           else dialogSuccess.dialog( "open" );
      });    
  }
  
  dialogError = $( "#program-error" ).dialog({
    width: 400,
    autoOpen: false,
    modal: true,
    buttons: {
      "Zavřít": function() {
        dialogError.dialog( "close" );
        $( "#thermopass" ).val("");
      }
    },
  });
  
  dialogSuccess = $( "#program-success" ).dialog({
    width: 400,
    autoOpen: false,
    modal: true,
    close: function() {reloadMe(); },
    buttons: {
      "Zavřít": function() {
        dialogSuccess.dialog( "close" );
        reloadMe();
      }
    },
  }); 
  
</script>
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
    print '<p>Tabulka ukazuje, v kolik hodin se spustí jednotlivé programy ve vybraném dni.';
    print '<table id="program" class="default">';
    print '<tr>';
      print '<th>Program & priorita</th>';
      print '<th class="hide period">Období</th>';
      print '<th class="time">Zahájení</th>';
      print '<th class="small">Po</th>';
      print '<th class="small">Út</th>';
      print '<th class="small">St</th>';
      print '<th class="small">Čt</th>';
      print '<th class="small">Pá</th>';
      print '<th class="small">So</th>';
      print '<th class="small">Ne</th>';
      print '<th class="temp">Teplota</th>';
      print '<th class="hyst">Hystereze</th>';
    print '</tr>';

    $lastid = -1;
    
    for ($minute = 0; $minute < 1440; $minute++)    
    {
      foreach($programSet as $program)
      {
        if ($program->fhour*60 + $program->fminute <= $minute && $program->thour*60 + $program->tminute >= $minute) //Match found
        {               
          if ($lastid != $program->id) //Already displayed?
          {
            $lastid = $program->id;
            
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
              
              if (Params::$date->format("w") == 1) $class = 'orange'; else $class = 'black';
              print '<td class="small">'; if ($program->mon) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';
              if (Params::$date->format("w") == 2) $class = 'orange'; else $class = 'black';
              print '<td class="small">'; if ($program->tue) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';
              if (Params::$date->format("w") == 3) $class = 'orange'; else $class = 'black';
              print '<td class="small">'; if ($program->wed) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';
              if (Params::$date->format("w") == 4) $class = 'orange'; else $class = 'black';
              print '<td class="small">'; if ($program->thu) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';
              if (Params::$date->format("w") == 5) $class = 'orange'; else $class = 'black';
              print '<td class="small">'; if ($program->fri) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';      
              if (Params::$date->format("w") == 6) $class = 'orange'; else $class = 'black';
              print '<td class="small">'; if ($program->sat) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';
              if (Params::$date->format("w") == 0) $class = 'orange'; else $class = 'black';   
              print '<td class="small">'; if ($program->sun) print "<span class='$class'>✔</span>"; else print '<span class="grey">❖</span>'; print '</td>';
      
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

<p>Den můžete změnit v horní části obrazovky pomocí šipek nebo kalendáře.</p>

<hr />

<p>Pro přenastavení termostatu je třeba uvést kontrolní heslo:</p>

<p>
  <input class="input-passwd ui-button ui-widget ui-corner-all" id="thermopass" type="password" placeholder="Heslo" />
  <button class="ui-button ui-widget ui-corner-all" onclick="program_off()"     >✋ Vypnout topení</button>
  <button class="ui-button ui-widget ui-corner-all" onclick="program_control()" >✿ Řídit programem </button>
  <button class="ui-button ui-widget ui-corner-all" onclick="program_on()"      >➽ Zapnout topení</button>
  na
  <input class="input-temp ui-button ui-widget ui-corner-all" type="number" name="max" id="max" value="<?php print $programPowerOn->max ?>" min="-50" max="50" step="0.1" />
  °C
</p>  

<!-- ERROR -------------------------------------------------------------------->

<div id="program-error" title="⛔ Chyba">
  <p>Bylo zadáno špatné heslo!</p>
</div>

<!-- SUCCESS ------------------------------------------------------------------>

<div id="program-success" title="✔ Výsledek">
  <p>Termostat byl úspěšně přeprogramován</p>
</div>

<!-- END ---------------------------------------------------------------------->    