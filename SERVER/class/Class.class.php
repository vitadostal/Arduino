<?php

class XClass 
{		
	public $class;
	public $hardware;
	public $description;
	public $unit;

	public function fromDataRow(array $dataRow)
	{
    $this->class       = $dataRow['class'];
    $this->hardware    = $dataRow['hardware'];
    $this->description = $dataRow['description'];
    $this->unit        = $dataRow['unit'];
	}

	public function toDataRow()
	{
    $dataRow = array();
    $dataRow['class']       = $this->class;
    $dataRow['hardware']    = $this->hardware;
    $dataRow['description'] = $this->description;
    $dataRow['unit']        = $this->unit;
    return $dataRow;
	}
  
	public static function loadAll($db)
	{	
		$dataset = $db->getByCondition("class", "*");
		$result = array();

		foreach($dataset as $row)
		{
			$object = new XClass();
      $object->fromDataRow($row);      
      $result[$object->class] = $object;
		}		
		return $result;	
	}  

  public static function load($db, $class)
  {	
    $object = new XClass();
    $dataset = $db->getById("class", "class", $class);
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }

}