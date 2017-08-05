//Railtour GPS data gatherer
//Vitezslav Dostal | started 10.06.2017
#include <SoftwareSerial.h>
#include <ESP8266WiFi.h>
#include <WiFiManager.h>
//-----------------------------------------------------------
const byte rxPin = 5;
const byte txPin = 4;
const byte speakPin = 12;
const byte resetPin = 12;
const byte powerPin = 14;

int interval_fast = 5;  //number of second, read GPS
int interval_slow = 30; //number of second, read GPS
int interval      = 10; //number of minutes, wake up from sleep after success
int fail_interval = 3;  //number of minutes, wake up from sleep after failure
int suc_attempt   = 10; //measure taken into account
int last_attempt  = 60; //number of seconds allowed after first reading
int sat_enough    = 5;  //number of satellites which is considered fine
int maxOperatingTime = 5;

unsigned long operatingTime;
unsigned long foundTime;

bool fast = true;
byte found = 0;
char end_c[2];

float lat, lng;
int sat;

const char* key    = "<key>";
const char* sensor = "<sensor>";
const char* server = "arduino.vitadostal.cz";

SoftwareSerial gpsSerial (rxPin, txPin);
String parts[20];
unsigned long lastTime;

//-----------------------------------------------------------
void setup()
{  
  operatingTime = millis();
  
  end_c[0] = 0x1a;
  end_c[1] = '\0';

  pinMode(resetPin, OUTPUT); digitalWrite(resetPin, HIGH);
  pinMode(powerPin, OUTPUT); digitalWrite(powerPin, LOW);

  //setupWifi();
  startSerials();
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

  Serial.println();
}

void powerOnGPS()
{
  Serial.println("Powering on A7 GPS...");
  digitalWrite(powerPin, HIGH);
  delay(3000);
  digitalWrite(powerPin, LOW);
  Serial.println("Powered on.");
}

void powerOffGPS()
{
  //processCommand("AT+CPOF", "OK", 3, 1);
  Serial.println("Powering off A7 GPS...");
  digitalWrite(resetPin, LOW);
  Serial.println("Powered off.");
}

void waitForGPS()
{
  processCommand("AT", "OK", 1000000, 1);
}

void initGPS(int sec)
{
  processCommand("AT+GPS=1", "OK", 1000000, 1);  
  processCommand("AT+GPSRD=" + String(sec), "OK", 5, 1);
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

  if (millis() >= operatingTime + maxOperatingTime * 60 * 1000)
  {
    powerOffGPS();
    ESP.deepSleep((fail_interval * 60) * 1000000);
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
      //Test
      //postData(49.29547, 17.40901);
      
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

        Serial.println();
        foundTime = millis();

        //if (fast) initGPS(interval_slow);
        //fast = false;

        //tone(speakPin, 1760, 200);
        //delay(200);
        //noTone(speakPin);
                
        if (found >= suc_attempt || satelliteCount.toInt() >= sat_enough)
        {
          terminateGPS();
          postData(lat, lng, sat);

          powerOffGPS();
          ESP.deepSleep((interval * last_attempt) * 1000000);
        }
      }
    }
  }

  if (found >= 1 && (millis() >= foundTime + 60 * 1000))
  {
    terminateGPS();
    postData(lat, lng, sat);

    powerOffGPS();
    ESP.deepSleep((interval * 60) * 1000000);          
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
  wifiManager.autoConnect("ESP8266 Settings");
}

void postDataWifi(float lat, float lng, int sat)
{
  //Push data to server
  Serial.println("Connecting to server " + String(server) + "...");
  WiFiClient client;
  if (client.connect(server, 80))
  {
    Serial.println("Server connected");

    String params;
    params += "key=" + String(key);
    params += "&sensor=" + String(sensor);
    if (!isnan(lat)) params += "&class1=A7_LAT&value1="  + String(roundFloat(lat, 5), 5); //Latitude
    if (!isnan(lng)) params += "&class2=A7_LONG&value2=" + String(roundFloat(lng, 5), 5); //Longitude
    if (!isnan(sat)) params += "&class3=A7_SAT&value3="  + String(sat);                   //Satellite

    //POST request
    String request;
    request += "POST /script/measure_add.php HTTP/1.1\n";
    request += "Host: " + String(server) + "\n";
    request += "User-Agent: ArduinoWiFi/1.1\n";
    request += "Connection: close\n";
    request += "Content-Type: application/x-www-form-urlencoded\n";
    request += "Content-Length: ";
    request += (params.length() + 2);
    request += "\n\n";
    request += params;
    request += "\r\n\r\n";
    client.println(request);
    Serial.println("Data sent");
  }
  client.stop();
}

void postData(float lat, float lng, int sat)
{
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
    processCommand("AT+CIPCLOSE", "OK", 3, 1);
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
