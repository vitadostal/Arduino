<?php

  include "dbase.php";
  
  $graphs = array(""=>"");
  $units = array();
  $sql = "SELECT * FROM graph";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc())
  {
    $graphs[$row['id']] = $row['description'];
    $units[$row['id']]  = $row['unit'];    
  }

  $sql = "SELECT * FROM sensor";
  $result = $conn->query($sql);
  
  if (!isset($_GET['date'])) exit;
  $date = mysqli_real_escape_string($conn, $_GET['date']);  
  
  if ($result->num_rows > 0)
  {
    print '<p>Změnu názvu senzoru, referenčního grafu nebo kódu barvy potvrdíte klávesou ENTER. Kliknutím na identifikátor senzoru zobrazíte jeho aktuálně naměřené hodnoty.</p>';
    print '<form id="form-sensor" method="GET" action="/" onsubmit="return false">';
    print '<table>';
    print '<tr class="darker"><td colspan="4" style="border:none"></td><td colspan="3">DALLAS</td><td colspan="2">DHT</td></tr>';    
    while($row = $result->fetch_assoc())
    {
      $export = '';
      $export .= '<tr>';        
        $export .= '<td><input type="checkbox" name="sensor[]" value="'.$row['sensor'].'"> ';
        $export .= '<a onclick="$.cookie( \'activetab\', 2, {expires : 10});" href="?sensor='.$row['sensor'].'&amp;date='.$date.'">'.$row['sensor'].'</a></td>';
        $export .= '<td style="border-right: none; width: 175px;">';
        $export .= '<input class="not-sent" style="border: 1px solid black;" onchange="rename(\''.$row['sensor'].'\', this)" name="comment-'.$row['sensor'].
                    '" value="'.$row['comment'].'" type="text" />';
        $export .= '</td>';
        if ($row['visible']) $export .= '<td class="icon right">⛺</td>'; else $export .= '<td class="icon"></td>';        
        if ($row['implicit']) $export .= '<td class="icon wider">❤</td>'; else $export .= '<td class="icon"></td>';                
        for ($c = 1; $c <= 5; $c++)
        {
          $export .= '<td style="border-right: none; width: 162px;">';
            $export .= '<select class="graph-select" style="border: 1px solid black; width: 50px;" onchange="pair(\''.$row['sensor'].'\', '.$c.', this)" name="graph-'.$row['sensor'].'">';
            foreach ($graphs as $key=>$val) 
            {
                $export .= "<option ";
                if ($row["graph$c"] == $key) $export .= " selected='selected' ";
                $export .= " value='$key'>$key</option>";
            }
            $export .= '</select> ';
            $export .= '<input class="not-sent" style="border: 1px solid black; width: 70px;" onchange="dye(\''.$row['sensor'].'\', '.$c.', this)" name="color-'.$row['sensor'].'" value="'.$row["color$c"].'" type="text" />';
            if ($row["color$c"] != '') $export .= '<div style="float: right; margin-right: 10px; width: 20px; height: 20px; background-color: '.$row["color$c"].'"></div>';
          $export .= '</td>';
        }
      $export .= '</tr>';
      print $export;
    }
    print '</table>';
    print '</form>';
    print '<p>';
    print '<button onclick="chart()" class="ui-button ui-widget ui-corner-all">Zobrazit graf vybraných senzorů</button>';
    print '<button onclick="now()" class="ui-button ui-widget ui-corner-all">Zobrazit aktuální měření vybraných senzorů</button>';
    print '<br /><br /></p>';
  }

  print "<hr style='border: 0; border-top: 1px solid #ccc;'' />";
  print '<p>Můžete vybírat z následujících referenčních grafů:</p>';
  print '<table>';
  unset ($graphs['']);
  foreach ($graphs as $key=>$val)
  {
    print '<tr>';
      print '<td>'.$key.'</td>';  
      print '<td>'.$val.'</td>';  
      print '<td class="darker">'.$units[$key].'</td>';  
    print '</tr>';  
  }
  print '</table>';

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
  query += 'date=' + '<?php print $date ?>';

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
  query += 'date=' + '<?php print $date ?>';

  $.cookie( "activetab", 2, {expires : 10});   
  window.location.href = query; 
}

function rename(sensor, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");

  $.ajax({
    method: "POST",
    url: "rename.php",
    data: { sensor: sensor, comment: obj.value }
  })
    .done(function() {
      location.reload(); 
    });
}

function dye(sensor, item, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");
  
  $.ajax({
    method: "POST",
    url: "dye.php",
    data: { sensor: sensor, item: item, color: obj.value }
  })
    .done(function() {
      location.reload();
    });
}

function pair(sensor, item, obj) 
{ 
  $('.not-sent').attr("disabled", "disabled");
  
  $.ajax({
    method: "POST",
    url: "pair.php",
    data: { sensor: sensor, item: item, graph: $(obj).val() }
  })
    .done(function() {
      location.reload();
    });
}
</script>

<?php
/*  
  obj.style.backgroundPosition = "153px 3px";
  obj.style.backgroundRepeat = "no-repeat";
  obj.style.backgroundImage = "url('img/ajax_loader.gif')";
*/