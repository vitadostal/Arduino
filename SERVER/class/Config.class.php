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
define("GPS_CLASS_BAT", "A7_BAT");
define("TIME_STEP", 5);

class Config
{
  public static $dbhost = DB_HOST;
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
  public static $gpsclassbat = GPS_CLASS_BAT;
  
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
  public static $devicelist = array();
  public static $mindate = array();
  public static $maxdateMeasures = array();
  public static $maxdateCheckpoints = array();
  public static $timezone = ".000+02:00";
  public static $minsat = 5;
}

//Sensors can be redefined here
Config::$devicemap = array(
  "RTC01" => "RTR01",
  "RTC02" => "RTR02",
  "RTC03" => "RTR03",
  "RTC04" => "RTR04",
  "RTC05" => "RTR05",
  "RTC06" => "RTR06",
  "RTC07" => "RTR07",
  "RTC08" => "RTR08",
  "RTC09" => "RTR09",
  "RTC10" => "RTR10",
  "RTC11" => "RTR11",
  "RTC12" => "RTR12",
  "RTC13" => "RTR13",
  "RTC14" => "RTR14",
  "RTC15" => "RTR15",
  "RTC16" => "RTR16",
  "RTC17" => "RTR17",
  "RTC18" => "RTR18",
  "RTC19" => "RTR19",
  "RTC20" => "RTR20",
  "RTC21" => "RTR21",
  "RTC22" => "RTR22",
  "RTC23" => "RTR23",
  "RTC24" => "RTR24",
  "RTC25" => "RTR25",
  "RTC26" => "RTR26",
  "RTC27" => "RTR27",
  "RTC28" => "RTR28",
  "RTC29" => "RTR29",
  "RTC30" => "RTR30",
  "RTC31" => "RTR31",
  "RTC32" => "RTR32",
  "RTC33" => "RTR33",
  "RTC34" => "RTR34",
  "RTC35" => "RTR35",

  "RTD01" => "RTR51",
  "RTD02" => "RTR52",
  "RTD03" => "RTR53",
  "RTD04" => "RTR54",
  "RTD05" => "RTR55",
  "RTD06" => "RTR56",
  "RTD07" => "RTR57",
  "RTD08" => "RTR58",
  "RTD09" => "RTR59",
  "RTD10" => "RTR60",
  "RTD11" => "RTR61",
  "RTD12" => "RTR62",
  "RTD13" => "RTR63",
  "RTD14" => "RTR64",
  "RTD15" => "RTR65",
  "RTD16" => "RTR66",
  "RTD17" => "RTR67",
  "RTD18" => "RTR68",
  "RTD19" => "RTR69",
  "RTD20" => "RTR70",
  "RTD21" => "RTR71",
  "RTD22" => "RTR72",
  "RTD23" => "RTR73",
);

//Default sensor list can be redefined here
Config::$devicelist = array(
  "CZ" => array(
    "RTR01", "RTR02", "RTR03", "RTR04", "RTR05", "RTR06", "RTR07", "RTR08", "RTR09", "RTR10",
    "RTR11", "RTR12", "RTR13", "RTR14", "RTR15", "RTR16", "RTR17", "RTR18", "RTR19", "RTR20",
    "RTR21", "RTR22", "RTR23", "RTR24", "RTR25", "RTR26", "RTR27", "RTR28", "RTR29", "RTR30",
    "RTR31", "RTR32", "RTR33", "RTR34", "RTR35"
  ),
  "DE" => array(
    "RTR51", "RTR52", "RTR53", "RTR54", "RTR55", "RTR56", "RTR57", "RTR58", "RTR59", "RTR60",
    "RTR61", "RTR62", "RTR63", "RTR64", "RTR65", "RTR66", "RTR67", "RTR68", "RTR69", "RTR70",
    "RTR71", "RTR72", "RTR73"
  )
);

Config::$mindate[2019]            = "2019-08-05 09:00:00";
Config::$maxdateMeasures[2019]    = "2019-08-09 20:00:00";
Config::$maxdateCheckpoints[2019] = "2019-08-09 20:00:00";

Config::$mindate[2020]            = "2020-07-27 09:00:00";
Config::$maxdateMeasures[2020]    = "2020-07-31 16:00:00";
Config::$maxdateCheckpoints[2020] = "2020-07-31 16:00:00";

Config::$mindate[2021]            = "2021-08-02 09:00:00";
Config::$maxdateMeasures[2021]    = "2021-08-06 16:00:00";
Config::$maxdateCheckpoints[2021] = "2021-08-06 16:00:00";
