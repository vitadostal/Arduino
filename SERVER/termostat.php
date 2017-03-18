<script>

  function reloadMe()
  {
    location.reload();       
  }

  function program_on() 
  {     
    $.ajax({                                                                                                                                                                         
      method: "POST",
      url: "program_on.php",
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
      url: "program_off.php",
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
      url: "program_control.php",
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

  include "dbase.php";

  //Get manual mode temperature
  $sql = "SELECT max FROM program WHERE production=1 AND title='".$poweron."'";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()) $max = $row['max'];
  
  //Get date
  if (!isset($_GET['date'])) exit;
  $date = mysqli_real_escape_string($conn, $_GET['date']);
  $date = new DateTime($date);
  $day = $date->format("w");  
  
  //SQL query
  $sql = "SELECT id, title, color, priority, production, sun, mon, tue, wed, thu, fri, sat, min, max, from_time, to_time, ".
    "HOUR(from_time) AS fhour, HOUR(to_time) AS thour, MINUTE(from_time) AS fminute, MINUTE(to_time) AS tminute ".
    "FROM program WHERE production=1 ".
    "AND (from_date <= '".$date->format("Y-m-d")."' OR from_date IS NULL) ".
    "AND (to_date   >= '".$date->format("Y-m-d")."' OR to_date IS NULL)   ".
    "AND (($day=0 AND sun=1) || ($day=1 AND mon=1) || ($day=2 AND tue=1) || ($day=3 AND wed=1) || ($day=4 AND thu=1) || ($day=5 AND fri=1) || ($day=6 AND sat=1)) ". 
    "ORDER BY priority ASC, from_date, to_date, mon DESC, tue DESC, wed DESC, thu DESC, fri DESC, sat DESC, sun DESC, from_time, to_time";
  $result = $conn->query($sql);
  
  //Fetch programs
  $programs = array();
  while($row = $result->fetch_assoc())
  {
    $row['from_time'] = substr($row['from_time'],0,5);
    $row['to_time']   = substr($row['to_time'  ],0,5);
    $programs[] = $row;
  }
  
  //Table
  if ($result->num_rows > 0)
  {
    print '<p>Tabulka ukazuje, v kolik hodin se spustí jednotlivé programy ve vybraném dni.';
    print '<table id="program">';
    print '<tr>';
      print '<th>Program & priorita</th>';
      print '<th style="display:none;" class="period">Období</th>';
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
      foreach($programs as $row)
      {
        if ($row['fhour']*60 + $row['fminute'] <= $minute && $row['thour']*60 + $row['tminute'] >= $minute) //Match found
        {               
          if ($lastid != $row['id']) //Already displayed?
          {
            
            $lastid = $row['id'];
            
            print '<tr>';
              print '<td>';
                $color = '#999';
                switch ($row['color'])
                {
                  case "BLUE":   $color = '#599ad3'; break;
                  case "RED":    $color = '#f1595f'; break;
                  case "GREEN":  $color = '#79c36a'; break;
                  case "YELLOW": $color = '#d0c721'; break;
                }        
                print '<div style="float: left; vertical-align: -2px; margin-right: 10px; width: 20px; height: 20px; color: white; text-align: center; background-color: '.$color.'">'.$row['priority'].'</div>';        
                print $row['title'];
              print '</td>';
              
              print '<td style="display:none;"></td>';
              print '<td>'.str_pad(floor($minute / 60), 2, '0', STR_PAD_LEFT).':'.str_pad(($minute % 60), 2, '0', STR_PAD_LEFT).'</td>';
              
              if ($date->format("w") == 1) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['mon']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
              if ($date->format("w") == 2) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['tue']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
              if ($date->format("w") == 3) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['wed']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
              if ($date->format("w") == 4) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['thu']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
              if ($date->format("w") == 5) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['fri']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
              if ($date->format("w") == 6) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['sat']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
              if ($date->format("w") == 0) $color2 = '#f9a65a'; else $color2 = 'black';
              print '<td class="small">'; if ($row['sun']) print '<span style="color: '.$color2.'">✔</span>'; else print '<span class="grey">❖</span>'; print '</td>';
      
              if ($row['min'] != '' && $row['max'] != '')
              {
                print '<td class="temp" style="color:'.$color.'"><b>'.$row['max'].'</b> °C</td>';
                print '<td class="hyst">'.$row['min'].' °C</td>';
              }
              else print '<td colspan="2" style="border-right: 2px solid #ddd;"><i>vytápění vypnuto<i></td>'; 
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

<hr style='border: 0; border-top: 1px solid #ccc;' />

<p>Pro přenastavení termostatu je třeba uvést kontrolní heslo:</p>

<p>
  <input class="ui-button ui-widget ui-corner-all" id="thermopass" type="password" placeholder="Heslo" style="background-color:white; text-align:left;" />
  <button class="ui-button ui-widget ui-corner-all" onclick="program_off()"     >✋ Vypnout topení</button>
  <button class="ui-button ui-widget ui-corner-all" onclick="program_control()" >✿ Řídit programem </button>
  <button class="ui-button ui-widget ui-corner-all" onclick="program_on()"      >➽ Zapnout topení</button>
  na
  <input class="ui-button ui-widget ui-corner-all" type="number" name="max" id="max" value="<?php print $max ?>" min="-50" max="50" step="0.1" style="background-color:white; text-align:left; width:50px;" />
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