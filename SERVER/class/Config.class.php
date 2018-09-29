<?php

//Load constants DB_LOGIN, DB_PASSWD, API_KEY, PRG_PASSWD,
//               SENSOR_OUT, TEMP_CLASS_OUT, HMDT_SENSOR_OUT, HMDT_CLASS_OUT, PRES_SENSOR_OUT, PRES_CLASS_OUT, SENSOR_IN, TEMP_CLASS_IN,
//               GOOGLE_MAPS_KEY, IP_ADDRESS
include "/home/vdostal/private_html/arduino.php";

//Inline constatns
define("WRONG_PASSWD", "!PASSWD");
define("POWER_ON", "Manuálně ZAPNUTO");
define("POWER_OFF", "Manuálně VYPNUTO");
define("DEF_MAX", 22);
define("HYSTERESIS", 0.5);
define("GPS_CLASS", "A7_LAT");
define("GPS_CLASS_LONG", "A7_LONG");
define("GPS_CLASS_SAT", "A7_SAT");
define("GPS_CLASS_VCC", "A7_VCC");
define("TIME_STEP", 5);

class Config
{
  public static $dbname = DB_LOGIN;
  public static $dbuser = DB_LOGIN;
  public static $dbpasswd = DB_PASSWD;      
  public static $key = API_KEY;
  public static $passwd = PRG_PASSWD;
  
  public static $wrongpasswd = WRONG_PASSWD;
  public static $poweron = POWER_ON;
  public static $poweroff = POWER_OFF;

  public static $defaultmax = DEF_MAX;
  public static $hysteresis = HYSTERESIS;
  
  public static $gpsclass = GPS_CLASS;
  public static $gpsclasslong = GPS_CLASS_LONG;
  public static $gpsclasssat = GPS_CLASS_SAT;
  public static $gpsclassvcc = GPS_CLASS_VCC;
  
  public static $tempsensor_in = TEMP_SENSOR_IN;
  public static $tempclass_in = TEMP_CLASS_IN;
	public static $tempsensor_out = TEMP_SENSOR_OUT;
  public static $tempclass_out = TEMP_CLASS_OUT;
	public static $hmdtsensor_out = HMDT_SENSOR_OUT;
  public static $hmdtclass_out = HMDT_CLASS_OUT;
	public static $pressensor_out = PRES_SENSOR_OUT;
  public static $presclass_out = PRES_CLASS_OUT;

  public static $gmapskey = GOOGLE_MAPS_KEY;
  public static $ipaddress = IP_ADDRESS;
  public static $devicemap = array();
}

//Sensors can me redefined here
Config::$devicemap = array(
  "RTD01" => "RTR01",
  "RTD02" => "RTR02",
  "RTD03" => "RTR03",
  "RTD04" => "RTR04",
  "RTD05" => "RTR05",
  "RTD06" => "RTR06",
  "RTD07" => "RTR07",
  "RTD08" => "RTR08",
  "RTD09" => "RTR09",
  "RTD10" => "RTR10",
  "RTD11" => "RTR11",
  "RTD12" => "RTR12",
  "RTD13" => "RTR13",
  "RTD14" => "RTR14",
  "RTD15" => "RTR15",
  "RTD16" => "RTR16",
  "RTD17" => "RTR17",
  "RTD18" => "RTR18",
  "RTD19" => "RTR19",
  "RTD20" => "RTR20",
);