//New board initializer
//Chip ESP8266
//Vitezslav Dostal | started 03.03.2017

#include <EEPROM.h>
#include "Timer.h"
#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPUpdateServer.h>
#include <ESP8266mDNS.h>
#include <WiFiManager.h>

const char* sensor = "<5char>";
const char* key    = "<enter>";
const char* login  = "admin";
const char* passwd = "<enter>";
      char* host   = "initial";
const int   offset = 360;

ESP8266WebServer httpServer(80);
ESP8266HTTPUpdateServer httpUpdater;

Timer t;
int address = 0;
byte value, value1, value2, value3, value4, value5, value6, value7, value8, value9, value10;

template <class T> int EEPROM_writeAnything(int ee, const T& value)
{
    const byte* p = (const byte*)(const void*)&value;
    unsigned int i;
    for (i = 0; i < sizeof(value); i++)
          EEPROM.write(ee++, *p++);
    return i;
}

template <class T> int EEPROM_readAnything(int ee, T& value)
{
    byte* p = (byte*)(void*)&value;
    unsigned int i;
    for (i = 0; i < sizeof(value); i++)
          *p++ = EEPROM.read(ee++);
    return i;
}

struct config_t
{
    char  sensor[20];
    char  key[20];           
    char  login[20];
    char  passwd[20];
} memory, data;

void setup()
{
  Serial.begin(115200);
  EEPROM.begin(512);
  
  read();
  write();

  setupWifi();
  setupWebServer(host);

  t.every(500, showMemory, 0);
}

void write()
{
  strcpy(memory.sensor, sensor);
  strcpy(memory.key,    key);
  strcpy(memory.login,  login);
  strcpy(memory.passwd, passwd);
  EEPROM_writeAnything(offset, memory);
  EEPROM.commit();
}

void read()
{
  EEPROM_readAnything(offset, data);
  Serial.println("=====================");
  Serial.println("1   5    10   15   20");
  Serial.println("=====================");
  Serial.println(data.sensor);
  Serial.println(data.key);
  Serial.println(data.login);
  Serial.println(data.passwd);
  Serial.println("=====================");
}

void setupWifi()
{
  WiFiManager wifiManager;
  wifiManager.setTimeout(300);
  wifiManager.autoConnect("ESP8266 Initialization");
}

void setupWebServer(char host[])
{
  MDNS.begin(host);
  httpUpdater.setup(&httpServer, "/");
  httpServer.begin();
  MDNS.addService("http", "tcp", 80);
  Serial.printf("For firmware update use http://%s.local in your browser\n", host);
}

void loop()
{
  t.update();
  httpServer.handleClient();  
}

void showMemory(void* context)
{ 
  value1  = EEPROM.read(address+0);
  value2  = EEPROM.read(address+1);
  value3  = EEPROM.read(address+2);
  value4  = EEPROM.read(address+3);
  value5  = EEPROM.read(address+4);
  value6  = EEPROM.read(address+5);
  value7  = EEPROM.read(address+6);
  value8  = EEPROM.read(address+7);
  value9  = EEPROM.read(address+8);
  value10 = EEPROM.read(address+9);

  Serial.print(address);
  Serial.print("\t");
  Serial.print("|");
  Serial.print("\t");
  Serial.print(value1, DEC);
  Serial.print("\t");
  Serial.print(value2, DEC);
  Serial.print("\t");
  Serial.print(value3, DEC);
  Serial.print("\t");
  Serial.print(value4, DEC);
  Serial.print("\t");
  Serial.print(value5, DEC);
  Serial.print("\t");
  Serial.print(value6, DEC);
  Serial.print("\t");
  Serial.print(value7, DEC);
  Serial.print("\t");
  Serial.print(value8, DEC);
  Serial.print("\t");
  Serial.print(value9, DEC);
  Serial.print("\t");
  Serial.print(value10, DEC);
  Serial.println();

  address = address + 10;
  
  if (address >= 512)
  {
    address = 0;
    read();
  }
}
