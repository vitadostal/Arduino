<?php

class Params
{
  public static $sensors;
  public static $graph;
  public static $date;
  public static $date_czech_prev;
  public static $date_czech;
  public static $date_czech_next;
  public static $date_sql;
  public static $date_day;
  public static $year;
  public static $race;
  public static $title;
  public static $new;
  public static $implicit;
  
  public static function get()
  {
    if (isset ($_GET['sensor'])) self::$sensors    = $_GET['sensor']; else self::$sensors = array();
    if (isset ($_GET['graph']))  self::$graph      = $_GET['graph'];
    if (isset ($_GET['date']))   self::$date_czech = $_GET['date'];   else {self::$date_czech = date('d.m.Y'); self::$implicit = true;}
    if (isset ($_GET['year']))   self::$year       = $_GET['year'];   else {self::$year = date('Y');}
    if (isset ($_GET['race']))   self::$race       = $_GET['race'];   else {self::$race = 'CZ';}
    if (isset ($_GET['new']))    self::$new        = true;
    
    if (!is_array(self::$sensors)) self::$sensors = array(self::$sensors);
      
    self::$date = new DateTime(self::$date_czech);
    self::$date_sql = self::$date->format('Y-m-d');  
    self::$date_day = self::$date->format('w');        
  }
  
  public static function advanced()
  {
    $date = new DateTime(self::$date_czech);
    self::$title = Utils::czechDay($date->format('w')).' '.$date->format("j. ").' '.Utils::czechMonth($date->format("n"));    

    $date->modify('-1 day');
    self::$date_czech_prev = $date->format('d.m.Y');

    $date->modify('+2 day');
    self::$date_czech_next = $date->format('d.m.Y');
  }
}