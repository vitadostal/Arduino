<?php

  function whereArrayValue ($column, $array)
  {
  	$q = '(0=1';
  	foreach ($array as $key=>$val) $q .= " OR $column = '".$val."'";
  	$q .= ')';	  
  	return $q;
  }
  
  function hex2rgb($hex, $alpha = 100) {
     if (!($hex = str_replace("#", "", $hex))) return '255, 255, 255';
  
     if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
     } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
     }
     return $r.', '.$g.', '.$b.', '.$alpha;
  }    
  
// -----------------------------------------------------------------------------

  include "dbase.php";
  
  $titles = array();
  $colors = array();
  $all = array();
  $sql = "SELECT * FROM sensor";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()) {$all[] = $row['sensor']; $titles[$row['sensor']] = $row['comment']; $colors[$row['sensor']] = $row['color1'];}  

  if (isset ($_GET['sensor']))  $sensors = $_GET['sensor']; else $sensors = $all;
  if (!is_array($sensors)) {$sensors = array($sensors);}
  foreach($sensors as $key => $val) {$sensors[$key] = mysqli_real_escape_string($conn, $sensors[$key]);}
  $sensors = array_unique($sensors);
  foreach($sensors as $sensor) if (!array_key_exists($sensor, $titles)) return;
  
  $sql = "SELECT DATE(timestamp) AS date, TIME(timestamp) AS time, sensor, value1, value2, value3, value4, value5, text1, text2 FROM measure WHERE "
          .whereArrayValue ('sensor', $sensors).' ORDER BY id DESC LIMIT 20'; 
  $result = $conn->query($sql);
  
  if ($result->num_rows > 0)
  {
    print '<table>';
    print '<tr class="darker"><td colspan="3" style="border:none"></td><td colspan="3">DALLAS</td><td colspan="2">DHT</td><td colspan="2">GPS</td></tr>';
    while($row = $result->fetch_assoc())
    {
      $mydate = new DateTime($row['date']);
      $export = '';
      $export .= '<tr>';        
        $export .= '<td style="width: 250px;">';
        $export .= '<div style="float: left; clear: both; margin-right: 10px; width: 20px; height: 20px; background: rgba('.hex2rgb($colors[$row['sensor']], 1).')"></div>';
        $export .= $row['sensor'].': '.$titles[$row['sensor']].'</td>';
        $export .= '<td style="background: rgba('.hex2rgb($colors[$row['sensor']], 0.4).')">'.$mydate->format("d.m.Y").'</td>';
        $export .= '<td style="background: rgba('.hex2rgb($colors[$row['sensor']], 0.4).')">'.$row['time'].'</td>';
        $export .= '<td class="right">'.$row['value1'].' <span class="unit"> &#x2103;</span></td>';
        $export .= '<td class="right">'.$row['value2'].' <span class="unit"> &#x2103;</span></td>';
        $export .= '<td class="right">'.$row['value3'].' <span class="unit"> &#x2103;</span></td>';
        $export .= '<td class="right">'.$row['value4'].' <span class="unit"> &#x2103;</span></td>';
        $export .= '<td class="right">'.$row['value5'].' <span class="unit"> %</span></td>';
        $export .= '<td class="right">'.$row['text1'].' <span class="unit">N</span></td>';
        $export .= '<td class="right">'.$row['text2'].' <span class="unit">E</span></td>';        
      $export .= '</tr>';
      print $export;
    }
    print '</table>';
    print '<p>Tabulka se automaticky aktualizuje každých 5 sekund. Je zobrazeno 20 nejnovějších záznamů.</p>';
    
  	if (isset ($_GET['date'])) $date = $_GET['date']; else $date = date('d.m.Y');
    print '<button class="ui-button ui-widget ui-corner-all" onclick="window.location.href=\'?date='.$date.'\'" >Zobrazit všechny senzory</button>';
  }
  else print 'Nenalezen žádný záznam!';