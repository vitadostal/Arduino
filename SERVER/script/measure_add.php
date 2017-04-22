<?php

  include "../class/Config.class.php";
  include "../class/Database.class.php";

  $database = new Database();
  $database->connect();
  
  if (isset ($_POST['key']) != Config::$key) exit();
  if (isset ($_POST['sensor'])) $sensor = '"'.mysqli_real_escape_string($database->conn, $_POST['sensor']).'"'; else exit();

  for ($field = 1; $field <= 10; $field++)
  {
    if (isset ($_POST['value'.$field])) $value[$field] = '"'.mysqli_real_escape_string($database->conn, trim($_POST['value'.$field])).'"'; else $value[$field] = null;
  	if (isset ($_POST['class'.$field])) $class[$field] = '"'.mysqli_real_escape_string($database->conn, trim($_POST['class'.$field])).'"'; else $class[$field] = null;
    if (isset ($_POST['text' .$field])) $text[$field]  = '"'.mysqli_real_escape_string($database->conn, trim($_POST['text' .$field])).'"'; else $text[$field]  = null;
  }
  if (isset ($_POST['textclass'])) $textclass = '"'.mysqli_real_escape_string($database->conn, trim($_POST['textclass'])).'"'; else $textclass = null;

  $sql = 'START TRANSACTION;';
  for ($field = 1; $field < 10; $field++)
  {
    if (isset($value[$field]) && $value[$field] != null)
    {
    	$sql .= "INSERT INTO measure (sensor, class, field, value1)
        VALUES ($sensor, $class[$field], $field, $value[$field]);";
    }
  }
  if (isset($textclass) && $textclass != null)
  {
  	$text[1] = Database::prepare_data($text[1]);
  	$text[2] = Database::prepare_data($text[2]);
   	$text[3] = Database::prepare_data($text[3]);
    $sql .= "INSERT INTO measure (sensor, class, field, text1, text2, text3)
      VALUES ($sensor, $textclass, 10, $text[1], $text[2], $text[3]);";
  }
  $sql .= 'COMMIT;';

  $database->conn->multi_query($sql);
  $database->conn->close();