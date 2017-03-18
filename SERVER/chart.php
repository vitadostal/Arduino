<?php
  
  include "dbase.php";
  
  function hex2rgb($hex, $alpha = 100) {
     if (!($hex = str_replace("#", "", $hex))) return array();
  
     if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
     } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
     }
     $rgb = array("R"=>$r, "G"=>$g, "B"=>$b, "Alpha"=>$alpha);
     return $rgb;
  }  

	if (isset ($_GET['sensor']))  $sensors = $_GET['sensor'];                                   else $sensors = array('VD001','VD002');
	if (isset ($_GET['date']))    $date    = mysqli_real_escape_string($conn, $_GET['date']);   else $date = date('d.m.Y');
	if (isset ($_GET['graph']))   $graph   = mysqli_real_escape_string($conn, $_GET['graph']);  else $graph = 'A';  
  
  if (!is_array($sensors)) {$sensors = array($sensors);}
  foreach($sensors as $key => $val) {$sensors[$key] = mysqli_real_escape_string($conn, $sensors[$key]);}
  $sensors = array_unique($sensors);
  
  $graphs = array();
  $units = array();
  $sql = "SELECT * FROM graph WHERE id='$graph'";
  $result = $conn->query($sql);
  $info = $result->fetch_assoc();
  
  $date = new DateTime($date);
  $date = $date->format("Y-m-d");
 
  $values = array();
  $items = array(1, 2, 3, 4, 5);
  $i = 0;
  
  for ($i = 0; $i < 24; $i++)   // 6 interval
  {
     $hours[] = $i;
     $hours[] = '';
     $hours[] = '';
     $hours[] = '';
     $hours[] = '';
     $hours[] = '';     
  }
  
  $titles = array();
  $sql = "SELECT * FROM sensor";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc())
  {
    $titles[$row['sensor']] = $row['comment'];
    if ($titles[$row['sensor']] == '') $titles[$row['sensor']] = $row['sensor'];
    foreach ($items as $item) $colors[$row['sensor']][$item] = hex2rgb($row['color'.$item]);
    foreach ($items as $item) $references[$row['sensor']][$item] = $row['graph'.$item];
  }  
  foreach($sensors as $sensor) if (!array_key_exists($sensor, $titles)) return;    
  
  /* pChart library inclusions */
  include("./class/pData.class.php");
  include("./class/pDraw.class.php");
  include("./class/pImage.class.php");  
  
  /* Create and populate the pData object */
  $MyData = new pData();    

  
  foreach ($sensors as $sensor)
  {
    foreach ($items as $item)
    {
      if ($references[$sensor][$item] != $graph) continue;
      
      $values = array();
      
      $sql = "SELECT defaults.hour, defaults.minute, results.value FROM
      
              (
                SELECT hour, minute FROM
                  (SELECT 0 AS hour UNION ALL SELECT 1   
                  UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 
                  UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 
                  UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13
                  UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17
                  UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL SELECT 21
                  UNION ALL SELECT 22 UNION ALL SELECT 23) AS hours
                INNER JOIN 
                  (SELECT 0 AS minute UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) AS minutes
              ) AS defaults
              
              LEFT JOIN  
              (
                SELECT HOUR(timestamp) as hour,
                       MINUTE(timestamp) DIV 10 as minute,
                       ROUND(AVG(value".$item."), 1) as value
                FROM measure
                WHERE sensor = '$sensor' AND DATE(timestamp) = '$date' GROUP BY hour, minute
              ) AS results
              
              ON defaults.hour = results.hour AND defaults.minute = results.minute        
             ";
             
      $result = $conn->query($sql); 
          
      if ($result->num_rows > 0)
      {
        while($row = $result->fetch_assoc())
        {
          if ($row['value'] === NULL) $values[] = VOID;
                                 else $values[] = $row['value'];
        }             
      }   
      
      $MyData->addPoints($values,$sensor.$item);
      $MyData->setSerieWeight($sensor.$item,2);
      $MyData->setSerieTicks($sensor.$item,1);
      $MyData->setSerieDescription($sensor.$item,$titles[$sensor].' '.$item);    
      $MyData->setPalette($sensor.$item,$colors[$sensor][$item]);
    }    
  }
         
  $MyData->addPoints($hours,"Hours");
  $MyData->setSerieDescription("Hours","Hodiny");
  $MyData->setAbscissa("Hours");  
  $MyData->setAxisName(0, strtoupper($info['description']).'     [ '.$info['unit'].' ]');
  
  /* Create the pChart object */
  $myPicture = new pImage(1000,400,$MyData);
  
  /* Turn of Antialiasing */
  $myPicture->Antialias = FALSE;
  
  /* Add a border to the picture */
  $myPicture->drawRectangle(0,0,999,399,array("R"=>0,"G"=>0,"B"=>0));
  
  /* Set the default font */
  $myPicture->setFontProperties(array("FontName"=>"./fonts/Montserrat-Regular.ttf","FontSize"=>6));
  
  /* Define the chart area */
  $myPicture->setGraphArea(60,40,950,350);
  
  /* Draw the scale */
  $scaleSettings = array("GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"LabelSkip"=>2);
  $myPicture->drawScale($scaleSettings);
  
  /* Write the chart legend */
  $myPicture->drawLegend(580,12,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
  
  /* Turn on shadow computing */ 
  //$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
  
  /* Draw the chart */
  //$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
  //$myPicture->drawBarChart();
  $myPicture->drawLineChart();
  
  /* Render the picture (choose the best way) */
  $myPicture->autoOutput("img/example.drawBarChart.simple.png");