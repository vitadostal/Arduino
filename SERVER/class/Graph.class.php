<?php

class Graph 
{		
	public $graph;
	public $description;
	public $unit;
	public $from;
	public $to;

	public function fromDataRow(array $dataRow)
	{
    $this->graph       = $dataRow['graph'];
    $this->description = $dataRow['description'];
    $this->unit        = $dataRow['unit'];
    $this->from        = $dataRow['from'];
    $this->to          = $dataRow['to'];
	}

	public function toDataRow()
	{
    $dataRow = array();
    $dataRow['graph']       = $this->graph;
    $dataRow['description'] = $this->description;
    $dataRow['unit']        = $this->unit;
    $dataRow['from']        = $this->from;
    $dataRow['to']          = $this->to;
    return $dataRow;
	}
  
	public static function loadAll($db, $skipfirst = false)
	{
		$dataset = $db->getByCondition("graph", "*");
		$result = array();

		foreach($dataset as $row)
		{
			if ($skipfirst) {$skipfirst = false; continue;}
      $object = new Graph();
      $object->fromDataRow($row);      
      $result[$object->graph] = $object;
		}		
		return $result;	
	}

  public static function load($db, $graph)
  {	
    $object = new Graph();
    $dataset = $db->getById("graph", "graph", $graph);
    
    if ($dataset != null)
      $object->fromDataRow($dataset[0]);
    else $object = null;
    
    return $object;
  }

}