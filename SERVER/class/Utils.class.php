<?php

class Utils
{
  public static function hex2rgb($hex, $alpha = 1)
  {
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
  
  public static function sql2czech($date)
  {
    $date = new DateTime($date);
    return $date->format("d.m.Y");
  }
  
  public static function czechDay($day)
  {
    static $names = array('Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota');
    return $names[$day];
  }
  
  public static function czechMonth($month)
  {
    static $names = array('', 'Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec',
      'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec');
    return $names[$month];
  }
  
  public static function sensorQuery($sensors, $html_entity = false)
  {
     $data = '';
     foreach ($sensors as $sensor)
     {
        if ($data != '')
          if ($html_entity) $data .= '&amp;'; else $data .= '&';
        $data .= 'sensor[]='. $sensor;
     }
     return $data;
  }

  public static function keepAttributes($obj, $attributes)
  {
    foreach ($obj as $key => $value) {
      if (!in_array($key, $attributes)) unset ($obj->$key);
    }
  }
}