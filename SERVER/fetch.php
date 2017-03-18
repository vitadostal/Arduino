<?php

  include "dbase.php";
  
  //SQL query
  $sql = "SELECT id, title, color, priority, production, sun, mon, tue, wed, thu, fri, sat, min, max, from_time, to_time, from_date, to_date, ".
    "HOUR(from_time) AS fhour, HOUR(to_time) AS thour, MINUTE(from_time) AS fminute, MINUTE(to_time) AS tminute, ".
    "DAY(from_date) AS fday, MONTH(from_date) AS fmonth, YEAR(from_date) AS fyear, ".
    "DAY(to_date) AS tday, MONTH(to_date) AS tmonth, YEAR(to_date) AS tyear ".
    "FROM program WHERE production=1 ".
    //"AND (from_date <= '".$date->format("Y-m-d")."' OR from_date IS NULL) ".
    //"AND (to_date   >= '".$date->format("Y-m-d")."' OR to_date IS NULL)   ".
    //"AND (($day=0 AND sun=1) || ($day=1 AND mon=1) || ($day=2 AND tue=1) || ($day=3 AND wed=1) || ($day=4 AND thu=1) || ($day=5 AND fri=1) || ($day=6 AND sat=1)) ". 
    "ORDER BY priority ASC, from_date, to_date, mon DESC, tue DESC, wed DESC, thu DESC, fri DESC, sat DESC, sun DESC, from_time, to_time";
  $result = $conn->query($sql);
  
  //Fetch programs
  $rows = array();
  while($row = $result->fetch_assoc()) $rows[] = $row;

  //print JSON
  header('Content-Type: application/json');
  print json_encode($rows);