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
  
	public static function loadClassNews($db, $class, $sensor, $count)
	{
    $dataset = $db->getByCondition(
      "measure",
      "id, timestamp, DATE_FORMAT(timestamp,'%d.%m.%Y') AS date, TIME(timestamp) AS time, sensor, class, field, value1, value2, value3, text1, text2, text3",
      "sensor='".$sensor."' AND class='".$class."'",
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
  
	public static function loadClassNewsLastMinutes($db, $class, $sensors, $minutes)
	{
    $minutes = mysqli_real_escape_string($db->conn, $minutes);
    
    $dataset = $db->getByCondition(
      "measure",
      "id, timestamp, DATE_FORMAT(timestamp,'%d.%m.%Y') AS date, TIME(timestamp) AS time, sensor, class, field, value1, value2, value3, text1, text2, text3",
      "class='$class' AND timestamp > DATE_SUB(NOW(), INTERVAL $minutes MINUTE) AND ". Database::whereArray("sensor", $sensors),      
      "timestamp DESC, sensor ASC, field ASC");     

		$result = array();
		foreach($dataset as $row)
		{
			$object = new Measure();
      $object->fromDataRow($row);
      $result[$object->id] = $object;
		}
		return $result;
	}
  
	public static function loadClassNewsPeriod($db, $class, $sensors, $from, $to)
	{
    $from = mysqli_real_escape_string($db->conn, $from);
    $to = mysqli_real_escape_string($db->conn, $to);

    $dataset = $db->getByCondition(
      "measure",
      "id, timestamp, DATE_FORMAT(timestamp,'%d.%m.%Y') AS date, TIME(timestamp) AS time, sensor, class, field, value1, value2, value3, text1, text2, text3",
      "class='$class' AND timestamp >= '$from' AND timestamp <= '$to' AND ". Database::whereArray("sensor", $sensors),      
      "timestamp DESC, sensor ASC, field ASC");     

		$result = array();
		foreach($dataset as $row)
		{
			$object = new Measure();
      $object->fromDataRow($row);
      $result[$object->id] = $object;
		}
		return $result;
	}
  
	public static function loadClassNewsLatest($db, $class, $sensors)
	{
    $whereArray = array();
    foreach ($sensors as $sensor)
    {
      $whereArray[] = "class='$class' AND sensor='$sensor'";  
    }
    
    $dataset = $db->getUnionByCondition(
      "measure",
      "id, timestamp, DATE_FORMAT(timestamp,'%d.%m.%Y') AS date, TIME(timestamp) AS time, sensor, class, field, value1, value2, value3, text1, text2, text3",
      $whereArray,      
      "timestamp DESC LIMIT 1");     

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
    $result = array();

    //When today: Also part of yesterday is taken into account
    if ($date == date("Y-m-d")) $result = self::datasetGraphYesterday($result, $db, $displays, $group); 

    $dataset = $db->getByCondition(
      "measure",     
      "HOUR(timestamp) as hour, MINUTE(timestamp) DIV $group as minute, sensor, class, ROUND(AVG(value1), 1) as value",
      "(". Display::whereDisplay($displays). ") AND date(timestamp)='".$date."' GROUP BY sensor, class, hour, minute" 
    );
		
		foreach($dataset as $row)
		{
      $row['yesterday'] = false;
      $result[$row['sensor']][$row['class']][] = $row;
		}
    
		return $result;
  }
  
  public static function datasetGraphYesterday($result, $db, $displays, $group = 10)
  {
    $dataset = $db->getByCondition(
      "measure",     
      "HOUR(timestamp) as hour, MINUTE(timestamp) DIV $group as minute, sensor, class, ROUND(AVG(value1), 1) as value",
      "(". Display::whereDisplay($displays). ") ".
      "AND DATE(timestamp) = DATE(NOW() - INTERVAL 1 DAY) AND TIME(timestamp) >= TIME(NOW()) ".
      "GROUP BY sensor, class, hour, minute" 
    );
		
		foreach($dataset as $row)
		{
      $row['yesterday'] = true;
      $result[$row['sensor']][$row['class']][] = $row;
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
      1
    );

    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }
  
  public static function loadClassHistory($db, $class, $sensors, $date)
  {
    $dataset = $db->getByCondition(
      "measure",
      "id, timestamp, HOUR(timestamp) as hour, MINUTE(timestamp) as minute, DATE(timestamp) AS date, TIME(timestamp) AS time, sensor, class, value1, value2, value3, text1, text2, text3", 
      "class='".$class."' AND DATE(timestamp)='$date' AND ". Database::whereArray("sensor", $sensors),
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