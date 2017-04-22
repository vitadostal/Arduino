<?php

class Display
{		
	public $sensor;
	public $class;
	public $suffix;
	public $color;
	public $graph;

	public function fromDataRow(array $dataRow)
	{
    $this->sensor = $dataRow['sensor'];
    $this->class  = $dataRow['class'];
    $this->suffix = $dataRow['suffix'];
    $this->color  = $dataRow['color'];
    $this->graph  = $dataRow['graph'];
	}

	public function toDataRow()
	{
    $dataRow = array();
    $dataRow['sensor'] = $this->class;
    $dataRow['class']  = $this->hardware;
    $dataRow['suffix'] = $this->description;
    $dataRow['color']  = $this->unit;
    $dataRow['graph']  = $this->graph;
    return $dataRow;
	}
  
	public static function loadAll($db)
	{	
    $sensorSet = Sensor::loadAll($db, true);  
    $classSet = XClass::loadAll($db);  
    $display = new Display();
    $result = array();
    
    foreach($sensorSet as $sensor)
    {
      foreach($classSet as $class)
      {
        $result[$sensor->sensor][$class->class] = $display; 
      }
    }
    
    $dataset = $db->getByCondition("display", "*");

		foreach($dataset as $row)
		{
			$object = new Display();
      $object->fromDataRow($row);
      $result[$object->sensor][$object->class] = $object;
		}
		return $result;
	}
  
	public static function loadBySensorsAndGraph($db, $sensors, $graph)
	{	
		$dataset = $db->getByCondition("display", "*",
      Database::whereArray("sensor", $sensors).
      " AND graph = '". $graph. "'");
		$result = array();

		foreach($dataset as $row)
		{
			$object = new Display();
      $object->fromDataRow($row);      
      $result[] = $object;
		}		
		return $result;	
	}  

  public static function load($db, $sensor, $class)
  {	
    $object = new Display();
    $dataset = $db->getByIds("display", "sensor", $sensor, "class", $class);
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }
  
  public static function whereDisplay($displays)
  {
    $str = '(1=0)';
    foreach($displays as $display)
    {
      $str .= ' OR ';
      $str .= '(sensor="'. $display->sensor. '" AND class="'. $display->class. '")';
    }
    return $str;
  }  

}