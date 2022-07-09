<?php

class Sensor 
{		
	public $sensor;
	public $comment;
	public $color;
	public $visible;
	public $implicit;

	public function fromDataRow(array $dataRow)
	{
    $this->sensor   = $dataRow['sensor'];
    $this->comment  = $dataRow['comment'];
    $this->color    = $dataRow['color'];
    $this->visible  = $dataRow['visible'];
    $this->implicit = $dataRow['implicit'];
	}

	public function toDataRow()
	{
    $dataRow = array();
    $dataRow['sensor']   = $this->sensor;
    $dataRow['comment']  = $this->comment;
    $dataRow['color']    = $this->color;
    $dataRow['visible']  = $this->visible;
    $dataRow['implicit'] = $this->implicit;
    return $dataRow;
	}
  
  public static function createSensor($name)
  {
    $sensor = new Sensor();
    $sensor->sensor = $name;   
    $sensor->comment = '<i>nepojmenovaný</i>';   
    return $sensor; 
  }
  
	public static function loadAll($db, $unnamed = false, $autocomment = false)
	{	
		if ($unnamed)
      $dataset = $db->getByCondition("sensor", "*");
    else
      $dataset = $db->getByCondition("sensor", "*", "comment != ''");
    
		$result = array();

		foreach($dataset as $row)
		{
			$object = new Sensor();
      $object->fromDataRow($row);        
      if ($autocomment && $object->comment == '') $object->comment = $object->sensor;
      $result[$object->sensor] = $object;
		}		
		return $result;	
	}
  
	public static function loadSelected($db, $sensors, $key = true)
	{	
		$dataset = $db->getByCondition("sensor", "*", "comment != '' AND ". Database::whereArray("sensor", $sensors));
		$result = array();

		foreach($dataset as $row)
		{
			$object = new Sensor();
      		$object->fromDataRow($row);
			if ($key)  
				$result[$object->sensor] = $object;
			else 
				$result[] = $object;
		}		
		return $result;	
	}
  
	public static function arrayAll($db)
	{	
		$dataset = $db->getByCondition("sensor", "sensor");
		$result = array();
		foreach($dataset as $row) $result[] = $row['sensor'];
		return $result;	
	}

	public static function arrayVisible($db)
	{	
		$dataset = $db->getByCondition("sensor", "sensor", "visible = 1 AND comment != ''");
		$result = array();
		foreach($dataset as $row) $result[] = $row['sensor'];
		return $result;	
	}
  
	public static function arrayImplicit($db)
	{	
		$dataset = $db->getByCondition("sensor", "sensor", "implicit = 1 AND comment != ''");
		$result = array();
		foreach($dataset as $row) $result[] = $row['sensor'];
		return $result;	
	}  
  
  public static function load($db, $sensor)
  {	
    $object = new Sensor();
    $dataset = $db->getById("sensor", "sensor", $sensor);
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }
  
  public static function remap($sensor)
  {
    if (isset(Config::$devicemap[$sensor])) return Config::$devicemap[$sensor];
    return $sensor;
  }
}