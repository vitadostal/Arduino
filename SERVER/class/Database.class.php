<?php

class Database
{
  public $conn;

  private $servername = "localhost";	
  private $dbname;
	private $username;
	private $password;
   
  public function connect()
  {
    $this->loadCredentials();
    
  	$this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
  	if ($this->conn->connect_error)
    {
      die("Connection failed: " . $this->conn->connect_error);
  	}
    
    $this->conn->set_charset("utf8");
  }

	public function getById($table, $idName, $idValue)
	{
		$table    = mysqli_real_escape_string($this->conn, $table);
		$idName   = mysqli_real_escape_string($this->conn, $idName);
		$idValue  = mysqli_real_escape_string($this->conn, $idValue);
		
		$q = "SELECT * FROM $table WHERE $idName='$idValue'";

		$result = $this->conn->query($q);		
		return $result->fetch_all(MYSQLI_ASSOC);
	}
	
	public function getByIds($table, $idName, $idValue, $idName2, $idValue2)
	{
		$table    = mysqli_real_escape_string($this->conn, $table);
		$idName   = mysqli_real_escape_string($this->conn, $idName);
		$idValue  = mysqli_real_escape_string($this->conn, $idValue);
		$idName2  = mysqli_real_escape_string($this->conn, $idName2);
		$idValue2 = mysqli_real_escape_string($this->conn, $idValue2);
		
		$q = "SELECT * FROM $table WHERE $idName='$idValue' AND $idName2='$idValue2'";

		$result = $this->conn->query($q);		
		return $result->fetch_all(MYSQLI_ASSOC);
	}
  
	public function getByCondition($table, $columns, $where = false, $order = false, $limit = false)
	{
    $table   = mysqli_real_escape_string($this->conn, $table);

    $q = "SELECT $columns FROM $table ";
    if ($where !== false) $q .= "WHERE $where ";
    if ($order !== false) $q .= "ORDER BY $order ";
    if ($limit !== false) $q .= "LIMIT 0,$limit ";
    
		$result = $this->conn->query($q);
		return $result->fetch_all(MYSQLI_ASSOC);
	}

  public static function prepareData($data)
  {
    if ($data === null) return 'NULL';
    return '"'.$data.'"';
  }

  public static function whereArray($column, $array)
  {
    if (empty($array)) return '1=1';
    $q = '(0=1';
    foreach ($array as $key=>$val) $q .= " OR $column = '".$val."'";
    $q .= ')';	  
    return $q;
  }

  private function loadCredentials()
  {
    $this->dbname = Config::$dbname;
    $this->username = Config::$dbuser;
    $this->password = Config::$dbpasswd;
  }

}