<?php

//Load constants DB_LOGIN, DB_PASSWD, API_KEY, PRG_PASSWD,
//TEMP_SENSOR_OUT, TEMP_CLASS_OUT, HMDT_SENSOR_OUT, HMDT_CLASS_OUT, PRES_SENSOR_OUT, PRES_CLASS_OUT
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
}