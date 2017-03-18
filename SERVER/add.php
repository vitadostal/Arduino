<?php

  include "dbase.php";

	if (isset ($_POST['key']) != $apikey) exit();
	if (isset ($_POST['sensor'])) $sensor = '"'.mysqli_real_escape_string($conn, $_POST['sensor']).'"'; else exit();
	if (isset ($_POST['value1'])) $value1 = '"'.mysqli_real_escape_string($conn, $_POST['value1']).'"'; else $value1 = 'NULL';
	if (isset ($_POST['value2'])) $value2 = '"'.mysqli_real_escape_string($conn, $_POST['value2']).'"'; else $value2 = 'NULL';
	if (isset ($_POST['value3'])) $value3 = '"'.mysqli_real_escape_string($conn, $_POST['value3']).'"'; else $value3 = 'NULL';
	if (isset ($_POST['value4'])) $value4 = '"'.mysqli_real_escape_string($conn, $_POST['value4']).'"'; else $value4 = 'NULL';
	if (isset ($_POST['value5'])) $value5 = '"'.mysqli_real_escape_string($conn, $_POST['value5']).'"'; else $value5 = 'NULL';
	if (isset ($_POST['text1']))  $text1  = '"'.mysqli_real_escape_string($conn, $_POST['text1']).'"';  else $text1  = 'NULL';
	if (isset ($_POST['text2']))  $text2  = '"'.mysqli_real_escape_string($conn, $_POST['text2']).'"';  else $text2  = 'NULL';
	if (isset ($_POST['text3']))  $text3  = '"'.mysqli_real_escape_string($conn, $_POST['text3']).'"';  else $text3  = 'NULL';

	$sql = "INSERT INTO measure (sensor, value1, value2, value3, value4, value5, text1, text2, text3)
	VALUES ($sensor, $value1, $value2, $value3, $value4, $value5, $text1, $text2, $text3)";

	if ($conn->query($sql) !== TRUE) {
	    echo "Error: " . $sql . "<br>" . $conn->error;
	}

	$conn->close();
  
?> 