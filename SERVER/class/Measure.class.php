<?php

class Measure 
{		
	public $id;
	public $timestamp;
	public $date;
	public $time;
	public $hour;
	public $minute;
  public $sensor;
	public $field;
	public $class;
	public $value1;
	public $value2;
	public $value3;
	public $text1;
	public $text2;
	public $text3;

	public function fromDataRow(array $dataRow)
	{
    if (isset($dataRow['id']))        $this->id        = $dataRow['id'];
    if (isset($dataRow['timestamp'])) $this->timestamp = $dataRow['timestamp'];
    if (isset($dataRow['date']))      $this->date      = $dataRow['date'];
    if (isset($dataRow['time']))      $this->time      = $dataRow['time'];
    if (isset($dataRow['hour']))      $this->hour      = $dataRow['hour'];
    if (isset($dataRow['minute']))    $this->minute    = $dataRow['minute'];
    if (isset($dataRow['sensor']))    $this->sensor    = $dataRow['sensor'];
    if (isset($dataRow['field']))     $this->field     = $dataRow['field'];
    if (isset($dataRow['class']))     $this->class     = $dataRow['class'];
    if (isset($dataRow['value1']))    $this->value1    = $dataRow['value1'];
    if (isset($dataRow['value2']))    $this->value2    = $dataRow['value2'];
    if (isset($dataRow['value3']))    $this->value3    = $dataRow['value3'];
    if (isset($dataRow['text1']))     $this->text1     = $dataRow['text1'];
    if (isset($dataRow['text2']))     $this->text2     = $dataRow['text2'];
    if (isset($dataRow['text3']))     $this->text3     = $dataRow['text3'];    
	}

	public function toDataRow()
	{
    $dataRow = array();
    $dataRow['id']        = $this->id;
    $dataRow['timestamp'] = $this->timestamp;
    $dataRow['date']      = $this->date;
    $dataRow['time']      = $this->time;
    $dataRow['sensor']    = $this->sensor;
    $dataRow['field']     = $this->field;
    $dataRow['class']     = $this->class;
    $dataRow['value1']    = $this->value1;
    $dataRow['value2']    = $this->value2;
    $dataRow['value3']    = $this->value3;
    $dataRow['text1']     = $this->text1;
    $dataRow['text2']     = $this->text2;
    $dataRow['text3']     = $this->text3;
    return $dataRow;
	}
  
  public static function load($db, $id)
  {	
    $object = new Measure();
    $dataset = $db->getById("measure", "id", $id);
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }

	public static function loadNews($db, $sensors, $count)
	{
    $dataset = $db->getByCondition(
      "measure",
      "id, timestamp, DATE(timestamp) AS date, TIME(timestamp) AS time, sensor, class, field, value1, value2, value3, text1, text2, text3",
      Database::whereArray("sensor", $sensors),
      "timestamp DESC, sensor ASC, field ASC", $count);     

		$result = array();
		foreach($dataset as $row)
		{
			$object = new Measure();
      $object->fromDataRow($row);
      $result[$object->id] = $object;
		}
		return $result;
	}
  
  public static function datasetGraph($db, $displays, $date, $group = 10)
  {
     $dataset = $db->getByCondition(
      "measure",     
      "HOUR(timestamp) as hour, MINUTE(timestamp) DIV $group as minute, sensor, class, ROUND(AVG(value1), 1) as value",
      "(". Display::whereDisplay($displays). ") AND date(timestamp)='".$date."' GROUP BY sensor, class, hour, minute" 
    );
		
    $result = array();
		foreach($dataset as $row)
		{
      $result[$row['sensor']][$row['class']][$row['hour']][$row['minute']] = $row['value'];
		}
		return $result;
  }
  
  public static function loadLastMeasure($db, $sensor, $class)
  {
    $object = new Measure();
    $dataset = $db->getByCondition(
      "measure",
      "*", 
      "sensor='$sensor' AND class='$class'",
      "timestamp DESC",
      "1" );

    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }  

  public static function loadHistory($db, $sensors, $date)
  {
    $dataset = $db->getByCondition(
      "measure",
      "id, timestamp, HOUR(timestamp) as hour, MINUTE(timestamp) as minute, DATE(timestamp) AS date, TIME(timestamp) AS time, sensor, class, value1, value2, value3, text1, text2, text3", 
      "DATE(timestamp)='$date' AND ". Database::whereArray("sensor", $sensors),
      "sensor, timestamp, field");

		$result = array();
		foreach($dataset as $row)
		{
			$object = new Measure();
      $object->fromDataRow($row);
      $result[$object->id] = $object;
		}
		return $result;
  }

}