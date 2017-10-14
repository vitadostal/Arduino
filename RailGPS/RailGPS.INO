//Railtour GPS data gatherer
//Vitezslav Dostal | started 10.06.2017
#include <SoftwareSerial.h>
#include <EEPROM.h>
#include <ESP8266WiFi.h>
#include <WiFiManager.h>
#include "SSD1306Brzo.h"
//-----------------------------------------------------------
const byte rxPin     =  5;   //GPS module serial line
const byte txPin     =  4;   //GPS module serial line
const byte resetPin  = 12;   //GPS module UCC
const byte powerPin  = 14;   //GPS module power on pin
const byte setupPin  = 13;   //Enable WiFi and download configuration
const byte dispData  =  0;   //SSD1306 sDATA
const byte dispClock =  2;   //SSD1306 sCLOCK

const byte test   = false;   //use predefined GPS value, skip GPS module

int interval_fast    =  5;   //number of second, read GPS
int interval_slow    = 30;   //number of second, read GPS
int fail_interval    =  2;   //number of minutes, wake up from sleep after failure
int succ_attempt     = 10;   //measure taken into account
int last_attempt     = 60;   //number of seconds allowed after first reading
int sat_enough       =  5;   //number of satellites which is considered fine
int maxOperatingTime =  5;   //number of minutes when device is automatically turned off

      String sensor          = "<from-eeprom>";           //Sensor indentification
const char*  server          = "arduino.vitadostal.cz";   //Processing server
      String key             = "<from-eeprom>";           //API write key
const String firmware        = "v1.03 14 Oct 2017";       //Firmware version
      byte   wifiReporting   = 255; //255 = <from-eeprom> //WiFi reporting enabled
      byte   gsmReporting    = 255; //255 = <from-eeprom> //GSM reporting enabled
      byte   succ_interval   = 255; //255 = <from-eeprom> //Next measure (in minutes)
const char*  update_path     = "/firmware";               //Firmware update path
      String sleepmode_path  = "/fetch/railgps.txt";      //Settings path
      String update_username = "admin";                   //Firmware update login
      String update_password = "admin";                   //Firmware update password

unsigned long operatingTime;
unsigned long foundTime;

bool fast = true;
bool set = false;
byte found = 0;
char end_c[2];

float lat, lng;
int sat;
String parts[20];
String dispBuffer[7] = {"", "", "", "", "", "", ""};
unsigned long lastTime;

SoftwareSerial gpsSerial (rxPin, txPin);
SSD1306Brzo display(0x3c, dispData, dispClock);

//-----------------------------------------------------------
void setup()
{  
  operatingTime = millis();
  
  end_c[0] = 0x1a;
  end_c[1] = '\0';

  display.init();
  display.flipScreenVertically();

  startSerials();
  readMemory();
  startLogo();

  pinMode(resetPin, OUTPUT); digitalWrite(resetPin, LOW);
  pinMode(powerPin, OUTPUT); digitalWrite(powerPin, LOW);
  
  pinMode(setupPin, INPUT);
  if (digitalRead(setupPin) == LOW) processSetup();
  
  powerOnGPS();

  waitForGPS();
  //terminateGPS();
  initGPS(interval_fast);
}

void loop()
{
  readSerial();
  testValidity(readGPS());
}

//-----------------------------------------------------------
void startSerials()
{
  Serial.begin(9600);
  gpsSerial.begin(9600);
}

void startLogo()
{
  Serial.println();
  Serial.println();
  Serial.println();
  Serial.println("     /----  /---\\  /---- ");
  Serial.println("     |      |   |  |      AiThinker A7 GSM/GPRS/GPS Module");
  Serial.println("===  | --\\  |---/  \\---\\  ================================  ===");
  Serial.println("     |   |  |          |  RailGPS " + firmware);
  Serial.println("     \\---/  |      ----/  " + sensor + " interval " + succ_interval + " min");
  Serial.println();
  displayMessage("RailGPS " + firmware); 
  displayMessage(sensor + " interval " + succ_interval + " min");
}

void powerOnGPS()
{
  Serial.println("Powering on A7 GPS...");
  displayMessage("Powering on A7 GPS...");
  digitalWrite(powerPin, HIGH);
  delay(3000);
  digitalWrite(powerPin, LOW);
  Serial.println("Powered on.");
  displayMessage("Powered on.");  
}

void powerOffGPS()
{
  Serial.println("Powering off A7 GPS...");
  displayMessage("Powering off A7 GPS...");  
  //processCommand("AT+CPOF", "OK", 3, 1);
  pinMode(resetPin, INPUT_PULLUP);
  //pinMode(resetPin, INPUT);
  //digitalWrite(resetPin, LOW);
  Serial.println("Powered off.");
  displayMessage("Powered off.");
}

void deepSleep(int mins)
{
  displayMessage("Deep sleep mode!");
  ESP.deepSleep((mins * 60) * 1000000);
}

void waitForGPS()
{
  processCommand("AT", "OK", 1000000, 1);
}

void initGPS(int sec)
{
  processCommand("AT+GPS=1", "OK", 1000000, 1);  
  processCommand("AT+GPSRD=" + String(sec), "OK", 5, 1);
  displayMessage("Polling GPS module...");
}

void terminateGPS()
{
  processCommand("AT+GPS=0", "OK", 1000000, 1);
}

String readSerial()
{
  String data = "";

  if (Serial.available()) {
    lastTime = millis();
    while (millis() < lastTime + 100) {
      while (Serial.available() >0) {
        char c = Serial.read();
        data += c;
      }
    }
  }
  if (data.length() >0) {
    gpsSerial.println(data);
    Serial.println(">>>>sent>>>>>>>>>>>>>>>>>>>>");
    Serial.print(data);
    //Serial.println();
    return data;
  }

  return "";
}

String readGPS()
{
  String data = "";

  if (gpsSerial.available()) {
    lastTime = millis();
    while (millis() < lastTime + 1000) {
      while (gpsSerial.available() >0) {
        char c = gpsSerial.read();
        data += c;
      }
    }    
  }
  if (data.length() >0) {
    Serial.println("====received================");
    Serial.print(data);
    //Serial.println();
    return data;
  }

  return "";
}

void testValidity(String answer)
{
  int begin, end;
  String satelliteCount;
  String txt;

  //Send prepared data
  if (test)
  {
    terminateGPS();
    postData(49.29547, 17.40901, -1);
    powerOffGPS();
    deepSleep(succ_interval);
  }  

  if (millis() >= operatingTime + maxOperatingTime * 60 * 1000)
  {
    terminateGPS();    
    powerOffGPS();
    deepSleep(fail_interval);
  } 

  begin = answer.indexOf("+GPSRD:$GPGGA");
  if (begin != -1)
  {
    end = answer.indexOf("*", begin);
    if (end != -1)
    {
      stringParts(answer.substring(begin, end + 3));
      satelliteCount = parts[7];
    }    
  }

  begin = answer.indexOf("$GPRMC");
  if (begin != -1)
  {
    end = answer.indexOf("*", begin);
    if (end != -1)
    {      
      int latD, lngD;
      float latM, lngM;

      stringParts(answer.substring(begin, end + 3));

      //Only for eastern Europe, otherwise must be changed
      if (parts[4] == "N" && parts[6] == "E")
      {
        txt = parts[3].substring(0, 2); latD = txt.toInt();
        txt = parts[5].substring(0, 3); lngD = txt.toInt();

        txt = parts[3].substring(2, parts[3].length()); latM = txt.toFloat();
        txt = parts[5].substring(3, parts[5].length()); lngM = txt.toFloat();

        lat = latD + latM/60;
        lng = lngD + lngM/60;

        sat = satelliteCount.toInt();

        found++;
        
        Serial.println("****gps*found***************");
        Serial.print("#");
        Serial.print(found);
        Serial.print("     ");
        Serial.print (lat, 5);
        Serial.print(" N, ");
        Serial.print (lng, 5);
        Serial.print(" E     ");
        Serial.print (sat);
        Serial.println(" sat");
        displayMessage("#" + String(found) + " " + String(lat) + "N " + String(lng) + "E " + String(sat) + "sat");

        Serial.println();
        foundTime = millis();

        //if (fast) initGPS(interval_slow);
        //fast = false;
                
        if (found >= succ_attempt || satelliteCount.toInt() >= sat_enough)
        {
          terminateGPS();
          if (gsmReporting) postData(lat, lng, sat);
          if (wifiReporting) postDataWifi(lat, lng, sat);
          powerOffGPS();
          deepSleep(succ_interval);
        }
      }
    }
  }

  if (found >= 1 && (millis() >= foundTime + 60 * 1000))
  {
    terminateGPS();
    if (gsmReporting) postData(lat, lng, sat);
    if (wifiReporting) postDataWifi(lat, lng, sat);
    powerOffGPS();
    deepSleep(succ_interval);
  }  
}

/*
$GPRMC,123519,A,4807.038,N,01131.000,E,022.4,084.4,230394,003.1,W*6A

Where:
     RMC          Recommended Minimum sentence C
     123519       Fix taken at 12:35:19 UTC
     A            Status A=active or V=Void.
     4807.038,N   Latitude 48 deg 07.038' N
     01131.000,E  Longitude 11 deg 31.000' E
     022.4        Speed over the ground in knots
     084.4        Track angle in degrees True
     230394       Date - 23rd of March 1994
     003.1,W      Magnetic Variation
     *6A          The checksum data, always begins with *
*/

void stringParts(String coord)
{
  int pos;
  int last = 0;
  int length = coord.length();

  String part;
  int item = 0;

  for(pos = 0; pos < length; pos++)
  {
    if (coord.charAt(pos) == ',' /*|| pos == length - 1*/)
    {
      //if (pos == length - 1) pos++;
      part = coord.substring(last, pos);
      parts[item] = part;
      //Serial.print(item);
      //Serial.print(":");
      //Serial.println(part);
      last = pos + 1;
      item++;
    }
  }
}

void setupWifi()
{
  WiFiManager wifiManager;
  wifiManager.setTimeout(300);
  wifiManager.autoConnect("RailGPS Access");
}

void postDataWifi(float lat, float lng, int sat)
{
  displayMessage("Connecting to Wifi...");
  setupWifi();
  displayMessage("WiFi network: " + WiFi.SSID());

  Serial.println("Connecting to server " + String(server) + "...");
  WiFiClient client;
  if (client.connect(server, 80))
  {
    Serial.println("Server connected.");

    String params;
    params += "key=" + String(key);
    params += "&sensor=" + String(sensor);
    if (!isnan(lat)) params += "&class1=A7_LAT&value1="  + String(roundFloat(lat, 5), 5); //Latitude
    if (!isnan(lng)) params += "&class2=A7_LONG&value2=" + String(roundFloat(lng, 5), 5); //Longitude
    if (!isnan(sat)) params += "&class3=A7_SAT&value3="  + String(sat);                   //Satellite

    //POST request
    String request;
    request += "POST /script/measure_add.php HTTP/1.1\r\n";
    request += "Host: " + String(server) + "\r\n";
    request += "User-Agent: ArduinoWiFi" + sensor + "/1.1\r\n";
    request += "Connection: close\r\n";
    request += "Content-Type: application/x-www-form-urlencoded\r\n";
    request += "Content-Length: ";
    request += (params.length() + 4);
    request += "\r\n\r\n";
    request += params;
    request += "\r\n\r\n";
    client.println(request);
    Serial.println("Data sent.");
  }
  client.stop();

  displayMessage("Packet sent over WiFi.");
}

void postData(float lat, float lng, int sat)
{
  displayMessage("Connecting to internet...");
  
  bool status;
  
              status = processCommand("AT+CGDCONT=1,\"IP\",\"internet\",\"0.0.0.0\",0,0", "OK", 5, 5);
  if (status) status = processCommand("AT+CMEE=1", "OK", 5, 5);
  if (status) status = processCommand("AT+CGATT=1", "OK", 5, 5);
  if (status) status = processCommand("AT+CGACT=1,1", "OK", 5, 5);
  if (status) status = processCommand("AT+CIPCLOSE", "", 1, 1);
  if (status) status = processCommand("AT+CIPSTART=\"TCP\",\"" + String(server) + "\",80", "CONNECT OK", 5, 5);
  if (status) status = processCommand("AT+CIPSEND", ">", 5, 5);
  
  if (status)
  {  
    String params;
    params += "key=" + String(key);
    params += "&sensor=" + String(sensor);
    if (!isnan(lat)) params += "&class1=A7_LAT&value1="  + String(roundFloat(lat, 5), 5); //Latitude
    if (!isnan(lng)) params += "&class2=A7_LONG&value2=" + String(roundFloat(lng, 5), 5); //Longitude
    if (!isnan(sat)) params += "&class3=A7_SAT&value3="  + String(sat);                   //Satellite
  
    //POST request
    String request;
    request += "POST /script/measure_add.php HTTP/1.1\r\n";
    request += "Host: " + String(server) + "\r\n";
    request += "User-Agent: A7-GPRS/" + String(sensor) + "\r\n";
    request += "Connection: close\r\n";
    request += "Content-Type: application/x-www-form-urlencoded\r\n";
    request += "Content-Length: ";
    request += (params.length() + 4);
    request += "\r\n\r\n";
    request += params;
    request += "\r\n\r\n";
    gpsSerial.print(request);
    gpsSerial.println(end_c);
    gpsSerial.println(end_c);
    gpsSerial.println(end_c);
  
    processCommand("", "OK", 5, 1);
    //processCommand("AT+CIPCLOSE", "OK", 3, 1);

    displayMessage("Packet sent over GSM.");
  }
}

bool processCommand(String command, String expect, int attempts, int period)
{
  String reply;
  int i;
  unsigned long processTime;
  
  for (i = 0; i <= attempts; i++)
  {
    Serial.println();
    Serial.println(">>>>sent>>>>>>>>>>>>>>>>>>>>");
    Serial.println(command);
    gpsSerial.println(command);
    
    processTime = millis();
    while (millis() < processTime + period * 1000)
    {    
      readSerial();
      reply = readGPS();
      if (reply.indexOf(expect) != -1) return true;
    }
  }

  return false;
}

float roundFloat(float t, int pwr)
{
  int i = round (t * pow(10, pwr));
  t = i / pow(10.0, pwr);
  return t;
}

template <class T> int EEPROM_readAnything(int ee, T& value)
{
  byte* p = (byte*)(void*)&value;
  unsigned int i;
  for (i = 0; i < sizeof(value); i++)
    *p++ = EEPROM.read(ee++);
  return i;
}

template <class T> int EEPROM_writeAnything(int ee, const T& value)
{
   const byte* p = (const byte*)(const void*)&value;
   int i;
   for (i = 0; i < sizeof(value); i++)
       EEPROM.write(ee++, *p++);
   return i;
}

void readMemory()
{
  struct config
  {
    char  sleepModePath[20];
    char  sensor[20];
    char  key[20];           
    char  adminLogin[20];
    char  adminPasswd[20];
    char  wifiLogin[20];
    char  wifiPasswd[20];
    char  serialUsed;
    char  displayUsed;
    char  dallasUsed;
    char  dhtUsed;
    char  dhtType;
    char  bmeUsed;
    char  outsideUsed;
    char  outsidePath[20];
    char  wifiReporting;
    char  gsmReporting;
    char  succ_interval;
    char  sleepMode;
  } memory; 
  
  EEPROM.begin(512);
  EEPROM_readAnything(340, memory);
  if (sleepmode_path  == "<from-eeprom>") sleepmode_path  = memory.sleepModePath;
  if (sensor          == "<from-eeprom>") sensor          = memory.sensor;
  if (key             == "<from-eeprom>") key             = memory.key;
  if (update_username == "<from-eeprom>") update_username = memory.adminLogin;
  if (update_password == "<from-eeprom>") update_password = memory.adminPasswd;  
  if (wifiReporting   == 255)             wifiReporting   = byte(memory.wifiReporting);
  if (gsmReporting    == 255)             gsmReporting    = byte(memory.gsmReporting);
  if (succ_interval   == 255)             succ_interval   = byte(memory.succ_interval);
}

void writeMemory(String sensor, String key, byte succ_interval, byte gsmReporting, byte wifiReporting)
{
  struct config
  {
    char  sensor[20];
    char  key[20];           
  } memory;

  sensor.toCharArray(memory.sensor, 20);
  sensor.toCharArray(memory.key, 20);
  EEPROM_writeAnything(360, memory);  
  EEPROM.write(508 - 1, wifiReporting);
  EEPROM.write(509 - 1, gsmReporting);
  EEPROM.write(510 - 1, succ_interval);
  EEPROM.commit();
  set = true;  
}

String mac2String(byte ar[]) {
  String s;
  for (byte i = 0; i < 6; ++i)
  {
    char buf[3];
    sprintf(buf, "%2X", ar[i]);
    s += buf;
    if (i < 5) s += ':';
  }
  return s;
}

void processSetup()
{ 
  Serial.println("Entering setup mode...");
  displayMessage("Entering setup mode...");
  setupWifi();
  
  byte mac[6];
  String macAdr;
  macAdr = mac2String(WiFi.macAddress(mac));
  
  Serial.println();
  Serial.println("WiFi connected.");
  Serial.println("WiFi network (SSID): " + WiFi.SSID());
  displayMessage("WiFi network: " + WiFi.SSID());
  Serial.println("MAC address: " + macAdr);
  displayMessage("MAC address: " + macAdr);

  downloadConfig(macAdr);

  //Signalize successful write to EEPROM
  if (set)
  {
    Serial.println();
    Serial.println("Signalizing success by powering on GPS module...");
    displayMessage("Download successful.");
    powerOnGPS();
  }

  //Sleep forever = 1 day
  Serial.println();
  Serial.println("Sweet dreams!");
  displayMessage("Deep sleep mode!");
  ESP.deepSleep((24 * 60 * 60) * 1000000);
}

void downloadConfig(String macAdr)
{
  String line, oldline, part;
  
  WiFiClient client;
  if (client.connect(server, 80))
  {
    //GET request
    client.println("GET " + String(sleepmode_path) + " HTTP/1.1");
    client.println("Host: " + String(server));
    client.println("User-Agent: ArduinoWiFi/1.1");
    client.println("Connection: close");
    client.println();

    //Use response
    delay(100);
    while(client.available())
    {
      line = client.readStringUntil('\r');
    }
  }
  client.stop();
  
  line.remove(0, 1);

  do
  {
    part = line.substring(0, line.indexOf(";"));
    if (part.equals(macAdr))
    {
      line = line.substring(line.indexOf(";")+1);
      sensor = line.substring(0, line.indexOf(";"));
      
      line = line.substring(line.indexOf(";")+1);
      succ_interval = line.substring(0, line.indexOf(";")).toInt();

      line = line.substring(line.indexOf(";")+1);
      gsmReporting = line.substring(0, line.indexOf(";")).toInt();

      line = line.substring(line.indexOf(";")+1);
      wifiReporting = line.substring(0, line.indexOf(";")).toInt();

      writeMemory(sensor, key, succ_interval, gsmReporting, wifiReporting);
      return;  
    }
    oldline = line;
    line = line.substring(line.indexOf(";")+1);
  }
  while (oldline != line);
}

void displayMessage(String text)
{
  for (int i = 0; i < 6; i++) dispBuffer[i] = dispBuffer[i+1];  
  dispBuffer[6] = text;
  display.clear();
  display.setFont(ArialMT_Plain_10);
  display.setTextAlignment(TEXT_ALIGN_LEFT);
  display.drawString(0,  0, dispBuffer[6]);
  display.drawString(0, 16, dispBuffer[5]);
  display.drawString(0, 25, dispBuffer[4]);
  display.drawString(0, 34, dispBuffer[3]);
  display.drawString(0, 43, dispBuffer[2]);
  display.drawString(0, 52, dispBuffer[1]);
  display.display();
}
