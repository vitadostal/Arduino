<?php

class Program
{		
	public $id;
	public $title;
	public $color;
	public $priority;
	public $production;
	public $sun;
	public $mon;
	public $tue;
	public $wed;
	public $thu;
	public $fri;
	public $sat;
	public $from_time;
	public $to_time;
	public $from_date;
	public $to_date;
	public $min;
	public $max;
	public $timestamp;

	public function fromDataRow(array $dataRow)
	{
    $this->id         = $dataRow['id'];
    $this->title      = $dataRow['title'];
    $this->color      = $dataRow['color'];
    $this->priority   = $dataRow['priority'];
    $this->production = $dataRow['production'];
    $this->sun        = $dataRow['sun'];
    $this->mon        = $dataRow['mon'];
    $this->tue        = $dataRow['tue'];
    $this->wed        = $dataRow['wed'];
    $this->thu        = $dataRow['thu'];
    $this->fri        = $dataRow['fri'];
    $this->sat        = $dataRow['sat'];
    $this->from_time  = $dataRow['from_time'];
    $this->to_time    = $dataRow['to_time'];
    $this->from_date  = $dataRow['from_date'];
    $this->to_date    = $dataRow['to_date'];
    $this->min        = $dataRow['min'];
    $this->max        = $dataRow['max'];
    $this->timestamp  = $dataRow['timestamp'];
	}

	public function toDataRow()
	{
    $dataRow = array();
    $dataRow['id']           = $this->id;
    $dataRow['title']        = $this->title;
    $dataRow['color']        = $this->color;
    $dataRow['priority']     = $this->priority;
    $dataRow['production']   = $this->production;
    $dataRow['sun']          = $this->sun;
    $dataRow['mon']          = $this->mon;
    $dataRow['tue']          = $this->tue;
    $dataRow['wed']          = $this->wed;
    $dataRow['thu']          = $this->thu;
    $dataRow['fri']          = $this->fri;
    $dataRow['sat']          = $this->sat;
    $dataRow['from_time']    = $this->from_time;
    $dataRow['to_time']      = $this->to_time;
    $dataRow['from_date']    = $this->from_date;
    $dataRow['to_date']      = $this->to_date;
    $dataRow['min']          = $this->min;
    $dataRow['max']          = $this->max;
    $dataRow['timestamp']    = $this->timestamp;
    return $dataRow;        	
  }
  
	public static function loadAllEditable($db)
	{
    $dataset = $db->getByCondition(
      "program",
      "*",
      "production = 0",
      "priority DESC, from_date, to_date, mon DESC, tue DESC, wed DESC, thu DESC, fri DESC, sat DESC, sun DESC, from_time, to_time");     

		$result = array();
		foreach($dataset as $row)
		{
			$object = new Program();
      $object->fromDataRow($row);      
      $result[$object->id] = $object;
		}		
		return $result;	
	}
  
	public static function loadAllToday($db)
	{
    $dataset = $db->getByCondition(
      "program",
      "id, title, color, priority, production, sun, mon, tue, wed, thu, fri, sat, min, max, from_time, to_time, ".
        "HOUR(from_time) AS fhour, HOUR(to_time) AS thour, MINUTE(from_time) AS fminute, MINUTE(to_time) AS tminute",
      "production = 1 ".
        "AND (from_date <= '".$date->format("Y-m-d")."' OR from_date IS NULL) ".
        "AND (to_date   >= '".$date->format("Y-m-d")."' OR to_date IS NULL)   ".
        "AND (($day=0 AND sun=1) || ($day=1 AND mon=1) || ($day=2 AND tue=1) || ($day=3 AND wed=1) || ($day=4 AND thu=1) || ($day=5 AND fri=1) || ($day=6 AND sat=1)) ", 
      "priority ASC, from_date, to_date, mon DESC, tue DESC, wed DESC, thu DESC, fri DESC, sat DESC, sun DESC, from_time, to_time");

		$result = array();
		foreach($dataset as $row)
		{
			$object = new Program();
      $object->fromDataRow($row);      
      $result[$object->id] = $object;
		}		
		return $result;	
	}  

  public static function load($db, $id)
  {	
    $object = new Program();
    $dataset = $db->getById("program", "id", $id);
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }

}