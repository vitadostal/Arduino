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
  
	const GREY   = '#999';
  const BLUE   = '#599ad3';
  const RED    = '#f1595f';
  const GREEN  = '#79c36a';
  const YELLOW = '#d0c721';
  
  const PRIORITY_ON = 7;  
  const PRIORITY_OFF = 6;  
  const PRIORITY_LOW = 0;  

	public function fromDataRow(array $dataRow)
	{
    if (isset($dataRow['id']))         $this->id         = $dataRow['id'];
    if (isset($dataRow['title']))      $this->title      = $dataRow['title'];
    if (isset($dataRow['color']))      $this->color      = $dataRow['color'];
    if (isset($dataRow['priority']))   $this->priority   = $dataRow['priority'];
    if (isset($dataRow['production'])) $this->production = $dataRow['production'];
    if (isset($dataRow['sun']))        $this->sun        = $dataRow['sun'];
    if (isset($dataRow['mon']))        $this->mon        = $dataRow['mon'];
    if (isset($dataRow['tue']))        $this->tue        = $dataRow['tue'];
    if (isset($dataRow['wed']))        $this->wed        = $dataRow['wed'];
    if (isset($dataRow['thu']))        $this->thu        = $dataRow['thu'];
    if (isset($dataRow['fri']))        $this->fri        = $dataRow['fri'];
    if (isset($dataRow['sat']))        $this->sat        = $dataRow['sat'];
    if (isset($dataRow['from_time']))  $this->from_time  = $dataRow['from_time'];
    if (isset($dataRow['to_time']))    $this->to_time    = $dataRow['to_time'];
    if (isset($dataRow['from_date']))  $this->from_date  = $dataRow['from_date'];
    if (isset($dataRow['to_date']))    $this->to_date    = $dataRow['to_date'];
    if (isset($dataRow['min']))        $this->min        = $dataRow['min'];
    if (isset($dataRow['max']))        $this->max        = $dataRow['max'];
    if (isset($dataRow['timestamp']))  $this->timestamp  = $dataRow['timestamp'];
    
    if (isset($dataRow['fhour']))      $this->fhour      = $dataRow['fhour'];
    if (isset($dataRow['thour']))      $this->thour      = $dataRow['thour'];
    if (isset($dataRow['fminute']))    $this->fminute    = $dataRow['fminute'];
    if (isset($dataRow['tminute']))    $this->tminute    = $dataRow['tminute'];    
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
  
	public static function loadAllForDay($db, $date, $day)
	{
    $dataset = $db->getByCondition(
      "program",
      "id, title, color, priority, production, sun, mon, tue, wed, thu, fri, sat, min, max, ".
        "HOUR(from_time) AS fhour, HOUR(to_time) AS thour, MINUTE(from_time) AS fminute, MINUTE(to_time) AS tminute",
      "production = 1 ".
        "AND (from_date <= '$date' OR from_date IS NULL) ".
        "AND (to_date   >= '$date' OR to_date IS NULL)   ".
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
  
  public static function loadByProductionTitle($db, $title)
  {	
    $object = new Program();
    $dataset = $db->getByCondition("program", "*", "production = 1 AND title='$title'");
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
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