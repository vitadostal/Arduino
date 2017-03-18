<?php

  include "../../private_html/arduino.php";

	//$servername = "localhost";
	//$dbname = "";
	//$username = "";
	//$password = "";
  
  //$apikey = "";
  //$thermopass = "";  
  
  $poweron = "Manuálně ZAPNUTO";
  $poweroff = "Manuálně VYPNUTO";

  $hysteresis = 0.5;
  $defaultmax = 22;
  
	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
  
  $conn->set_charset("utf8");