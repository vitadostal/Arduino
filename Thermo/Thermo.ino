//Sends temperature and humidity over WiFi to specified server via POST request
//Chip ESP8266 + sensor DHT + sensor DALLAS + display SSD1306
//Vitezslav Dostal | started 27.01.2017

#include <EEPROM.h>
#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPUpdateServer.h>
#include <ESP8266mDNS.h>
#include <DHT.h>
#include <WiFiManager.h>
#include <DallasTemperature.h>
#include <OneWire.h>
#include "SSD1306Brzo.h"
#include "images.h"
#include "Timer.h"

      String sensor          = "<from-eeprom>";           //Sensor indentification
const String host_prefix     = "ESP8266";                 //Hostname prefix
const char*  server          = "arduino.vitadostal.cz";   //Processing server
      String key             = "<from-eeprom>";           //API write key
const String firmware        = "v1.09 / 18 Mar 2017" ;    //Firmware version
const int    offset          = 360;                       //EEPROM memory offset

const int    interval        = 60;                        //Next measure on success (in seconds)
const int    pause           = 0.1;                       //Next measure on error (in seconds)
const int    attempts        = 5;                         //Number of tries

const char*  update_path     = "/firmware";               //Firmware update path
      String update_username = "<from-eeprom>";           //Firmware update login
      String update_password = "<from-eeprom>";           //Firmware update password

      byte   serialUsed      = 255;                       //Console connected (255 means <from EEPROM>)
      byte   displayUsed     = 255;                       //Display connected
      byte   dallasUsed      = 255;                       //Dallas sensor connected
      byte   dhtUsed         = 255;                       //DHT sensor connected
      byte   dhtType         = 255;                       //DHT sensor used

#define DALLAS_PIN           0                            //Dallas DS18B20 DATA
#define DISPLAY_SDA          1                            //SSD1306 sDATA
#define DHT_PIN              2                            //DHT11 DATA
#define DISPLAY_SCL          3                            //SSD1306 sCLOCK
#define DISPLAY_ADDRESS      0x3c                         //SSD1306 128x64 display support only

ESP8266WebServer httpServer(80);
ESP8266HTTPUpdateServer httpUpdater;
DHT dht(0, 0);
SSD1306Brzo display(DISPLAY_ADDRESS, DISPLAY_SDA, DISPLAY_SCL);
OneWire oneWire(DALLAS_PIN);
DallasTemperature dallas(&oneWire);
Timer t;
unsigned long lastExecutionTime;
String host;

template <class T> int EEPROM_readAnything(int ee, T& value)
{
  byte* p = (byte*)(void*)&value;
  unsigned int i; 
  for (i = 0; i < sizeof(value); i++)
    *p++ = EEPROM.read(ee++);
  return i;
}

void setup() {    

  //Read memory
  readMemory();

  //Init serial line
  if ((!displayUsed || DISPLAY_SDA != 1) && serialUsed)
  {
    Serial.begin(115200);
    Serial.println();
    Serial.println();    
  }
  
  //Init display
  if (displayUsed)
  {
    Serial.println("Starting display...");
    display.init();
    display.flipScreenVertically();
    drawVD();
    delay(1000);
  }

  //Init Dallas
  if (dallasUsed) dallas.begin();
  if (displayUsed) drawProgressBar(20);  

  //Init DHT
  if (dhtUsed)
  {
    DHT* dht_replace = new DHT(DHT_PIN, dhtType);
    dht = *dht_replace;
    dht.begin();
  }
  if (displayUsed) drawProgressBar(30);  

  //Info memory
  infoMemory();
  if (displayUsed) drawProgressBar(40);  
  
  //Hostname
  host = host_prefix + sensor;
  host.toLowerCase();
  Serial.println("My hostname is " + host);
  if (displayUsed) drawProgressBar(50);
    
  //WiFi manager
  Serial.println("Starting WiFi manager...");
  if (displayUsed) drawWiFi();
  setupWifi();
  if (displayUsed) drawProgressBar(60);  

  //Server
  Serial.println("Starting WebServer...");
  setupWebServer();
  if (displayUsed) drawProgressBar(70);  

  //Timer
  t.every(interval * 1000, takeReading, 0);
  //t.every(  0.01 * 1000, handleClient, 0);
  if (displayUsed) t.every(1 * 1000, updateExecutionTime, 0);
  if (displayUsed) drawProgressBar(100);

  //Show data on display
  float t1, t2, t3, t, h;
  readSensors(t1, t2, t3, t, h);
  lastExecutionTime = millis();
}

void loop()
{
  t.update();
  httpServer.handleClient();
}

void handleClient(void* context)
{
  httpServer.handleClient();
}

void infoMemory()
{  
  //Total memory
  Serial.print("Total memory ");
  uint32 size = spi_flash_get_id();
  size = size / 1024;
  size = round(size);
  Serial.print(size);
  Serial.println(" kB");
    
  //Free space
  Serial.print("Free space available ");
  uint32_t space = ESP.getFreeSketchSpace();
  space = space / 1024;
  space = round(space);
  Serial.print(space);
  Serial.println(" kB");
  Serial.println();
}

void readMemory()
{
  struct config
  {
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
  } memory; 
  
  EEPROM.begin(512);
  EEPROM_readAnything(offset, memory);
  if (sensor          == "<from-eeprom>") sensor          = memory.sensor;
  if (key             == "<from-eeprom>") key             = memory.key;
  if (update_username == "<from-eeprom>") update_username = memory.adminLogin;
  if (update_password == "<from-eeprom>") update_password = memory.adminPasswd;
  if (serialUsed      == 255)             serialUsed      = bool(memory.serialUsed);
  if (displayUsed     == 255)             displayUsed     = bool(memory.displayUsed);
  if (dallasUsed      == 255)             dallasUsed      = bool(memory.dallasUsed);
  if (dhtUsed         == 255)             dhtUsed         = bool(memory.dhtUsed);
  if (dhtType         == 255)             dhtType         = byte(memory.dhtType);  

  //Defaults
  if (serialUsed      == 255)             serialUsed      = true;
  if (displayUsed     == 255)             displayUsed     = true;
  if (dallasUsed      == 255)             dallasUsed      = true;
  if (dhtUsed         == 255)             dhtUsed         = true;
  if (dhtType         == 255)             dhtType         = 11;
}

void setupWifi()
{
  WiFiManager wifiManager;
  wifiManager.setTimeout(300);
  wifiManager.autoConnect("ESP8266 Settings");
}

void setupWebServer()
{
  char login[20], passwd[20];
  
  MDNS.begin(host.c_str());
  httpUpdater.setup(&httpServer, update_path, update_username.c_str(), update_password.c_str());
  httpServer.on("/", HTTP_GET, []() {
    httpServer.sendHeader("Connection", "close");
    httpServer.sendHeader("Access-Control-Allow-Origin", "*");
    httpServer.send(200, "text/html", deviceStatus());
  });
  httpServer.begin();
  MDNS.addService("http", "tcp", 80);
}

void drawProgressBar(int progress) {
  display.clear();
  display.drawProgressBar(0, 52, 127, 10, progress);
  display.setFont(ArialMT_Plain_10);
  display.setTextAlignment(TEXT_ALIGN_CENTER);
  display.drawString(64, 39, String(progress) + "%");
  display.display();
  delay(100);
}

void drawBottomProgressBar(int progress) {
  display.drawProgressBar(0, 40, 127, 10, progress);
  display.display();
}

void drawValues(float t_dal, float h_dht)
{
  char show[10];
  String out;
  dtostrf(t_dal, 0, 1, show);
  if (!isnan(t_dal))
    out = String(show) + " °C";
  else
    out = "{ error }";

  /*if (!isnan(h_dht))
  {
    dtostrf(h_dht, 0, 0, show);
    out += " " + String(show);
    out += " %";
  }*/
  
  display.clear();
  display.setTextAlignment(TEXT_ALIGN_CENTER);
  display.setFont(ArialMT_Plain_24);
  display.drawString(64, 10, out);
  display.display();
}

void drawWiFi() {
  display.drawXbm(34, 0, WiFiLogoWidth, WiFiLogoHeight, WiFiLogoBits);
  display.display();
}

void drawVD() {
  display.clear();
  display.drawXbm(32, 0, vdWidth, vdHeight, vdBits);
  display.display();
}

void drawComputer() {
  display.clear();
  display.drawXbm(48, 16, computerWidth, computerHeight, computerBits);
  display.display();
}

float readSensors(float &t1, float &t2, float &t3, float &t, float &h)
{
  if (displayUsed) drawComputer();
  float tDisp = NAN;
  
  //Get data
  readSensorDallas (t1, t2, t3);
  readSensorDHT (t, h);
  
  //Show one value on display
  if (!isnan(t))  tDisp = t;
  if (!isnan(t3)) tDisp = t3;
  if (!isnan(t2)) tDisp = t2;
  if (!isnan(t1)) tDisp = t1;
  if (displayUsed) drawValues(tDisp, h);
  return tDisp;
}

void updateExecutionTime(void* context)
{
  int progress = round((float(millis() - lastExecutionTime) / ((float)interval * 10)) * 1.03);
  if (progress > 100) progress = 100;
  drawBottomProgressBar(progress);
}

void takeReading(void* context)
{
  lastExecutionTime = millis();
  
  Serial.println();
  float t1, t2, t3, t, h;
  readSensors(t1, t2, t3, t, h);

  //Push data to server
  Serial.println("Connecting to server " + String(server) + "...");
  WiFiClient client;
  if (client.connect(server, 80))
  {
    Serial.println("Server connected");

    String params;
    params += "key=" + key;
    params += "&sensor=" + sensor;
    if (!isnan(t1)) params += "&value1=" + String(roundTenth(t1)); //Dallas 1
    if (!isnan(t2)) params += "&value2=" + String(roundTenth(t2)); //Dallas 2
    if (!isnan(t3)) params += "&value3=" + String(roundTenth(t3)); //Dallas 3
    if (!isnan(t))  params += "&value4=" + String(t);  //DHT
    if (!isnan(h))  params += "&value5=" + String(h);  //DHT

    //POST request
    String request;
    request += "POST /add.php HTTP/1.1\n";
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
  Serial.println("Waiting for " + String(interval) + " seconds...");
}

String deviceStatus() {
  String info;

  info  = "<!DOCTYPE HTML><html><head><title>ESP8266 device</title><meta charset='utf-8' />";
  info += "<style>html{font-family: sans-serif}</style></head>";
  info += "<body><h1>ESP8266 device</h1>";

  info += ("<b>Sensor</b>: ");
  info += (sensor + " [<a href='//" + server + "/?sensor=" + sensor + "'>graph</a>]");
  info += ("<br />");

  info += ("<b>Hostname:</b> ");
  info += (host + ".local [<a href='//" + host + ".local'>open</a>]");
  info += ("<br />");

  info += ("<b>IP address:</b> ");
  info += (WiFi.localIP().toString());
  info += ("<br />");

  byte mac[6];

  info += ("<b>MAC address:</b> ");
  info += mac2String(WiFi.macAddress(mac));
  info += ("<br />");

  info += ("<b>WiFi network (SSID):</b> ");
  info += (WiFi.SSID());
  info += ("<br />");

  info += ("<b>Signal strength (RSSI):</b> ");
  info += (WiFi.RSSI());
  info += (" dBm");
  info += ("<br />");

  float t1, t2, t3, t, h;
  readSensors(t1, t2, t3, t, h);
  if (dallasUsed) info += ("<b>DS18B20 Readings:</b> " + String(t1) + "&#8451; " + String(t2) + "&#8451; " + String(t3) + "&#8451;<br />");
  if (dhtUsed)    info += ("<b>DHT" + String(dhtType) + " Readings:</b> " + String(t) + "&#8451; " + String(h) + "%<br />");

  info += ("<b>Firmware:</b> ");
  info += (firmware);
  info += (" [<a href='/firmware'>update</a>]");
  info += ("<br />");

  info += ("<b>Author:</b> ");
  info += ("vita@vitadostal.cz");
  info += ("<br />");
  info += ("<br />");

  info += ("<b>Schema</b><ul style='padding-top: 0; margin-top: 0;'>");
  info += ("<li><b>GPIO" + String(DALLAS_PIN) + ":</b> Dallas DS18B20 data &amp; pull-up resistor 4K7&#8486;</li>");
  info += ("<li><b>GPIO" + String(DISPLAY_SDA) + ":</b> SSD1306 display SDA (I2C serial data)</li>");
  info += ("<li><b>GPIO" + String(DHT_PIN) + ":</b> DHT" + String(dhtType) + " data &amp; pull-up resistor 10K&#8486;</li>");
  info += ("<li><b>GPIO" + String(DISPLAY_SCL) + ":</b> SSD1306 display SCL (I2C serial clock)</li>");
  info += ("</ul>");

  info += ("<img alt='Schema' src='//arduino.vitadostal.cz/img/ESP01.png' />");
  info += ("<br />");
  info += ("<img alt='Schema' src='//arduino.vitadostal.cz/img/ESP12E.png' />");
  info += ("<br />");
  info += ("<img alt='Schema' src='//arduino.vitadostal.cz/img/ESP12Eboard.png' />");
  info += ("</body></html>");

  return info;
}

void readSensorDHT(float &t, float &h)
{
  if (!dhtUsed) {t = h = NAN; return;}
  
  h = dht.readHumidity();
  t = dht.readTemperature();

  int i = 1;
  while (isnan(h) || isnan(t))
  {
    Serial.println("Failed to read from DHT sensor!");

    if (i == attempts) return;

    delay(pause * 1000);
    h = dht.readHumidity();
    t = dht.readTemperature();
    i++;
  }

  Serial.println("DHT | Measured data: " + String(t) + "°C " + String(h) + "%");
}

void readSensorDallas(float &t1, float &t2, float &t3)
{
  if (!dallasUsed) {t1 = t2 = t3 = NAN; return;}
  
  dallas.requestTemperatures();
  t1 = dallas.getTempCByIndex(0);
  t2 = dallas.getTempCByIndex(1);
  t3 = dallas.getTempCByIndex(2);

  if (t1 == -127) t1 = NAN;
  if (t2 == -127) t2 = NAN;
  if (t3 == -127) t3 = NAN;

  if (t1 == NAN)
  {
    Serial.println("Failed to read from Dallas sensor!");
    return;
  }  

  Serial.println("Dallas | Measured data: " + String(t1) + "°C " + String(t2) + "°C " + String(t3) + "°C");
}

float roundTenth(float t)
{  
  int i = round (t * 10);
  t = i / 10.0;
  return t;
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

