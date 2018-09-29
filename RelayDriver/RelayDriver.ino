//Control device with relay
//Vitezslav Dostal | started 28.9.2018
#include <ESP8266WiFi.h>
#define red         1
#define yellow      3
#define blue        5
#define green       4
#define button     14

char wifiSSID[20]   = "";
char wifiPasswd[20] = "";
char* server        = "";
char* filename      = "/fetch/power.txt";
int interval        = 30;

int manual = 2;
int power = 0;
unsigned long timer = 0;
byte status[16] = {3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3};

void turnOff(byte pin)
{
  if (status[pin] != 0)
  {
    status[pin] = 0;
    pinMode(pin, INPUT);
  }
}

void turnOn(byte pin)
{
  if (status[pin] != 1)
  {
    status[pin] = 1;
    pinMode(pin, OUTPUT);
    digitalWrite(pin, LOW);
  }
}

void setup()
{
  turnOff(button);

  turnOn(red);
  connectWifi();
  turnOff(red);

  turnOn(green);

  timer = millis();
}

void loop()
{
  if (digitalRead(button) == HIGH) handleButton();

  unsigned long now = millis();
  if ((now - timer > interval * 1000) || (now < timer))
  {
    timer = now;
    evaluate();
  }
}

void handleButton()
{
  manual++;
  if (manual > 2) manual = 0;

  switch (manual)
  {
    case 0: turnOn(yellow); turnOn(red); break;
    case 1: turnOn(yellow); turnOff(red); break;
    case 2: turnOff(yellow); break;
  }

  delay(500);
  timer = millis();
}

void evaluate()
{
  turnOff(green);
  switch (manual)
  {
    case 0:
      turnOff(blue); break;
    case 1:
      turnOn(blue); break;
    case 2:
      String data = readFile();
      if (data.indexOf("<ON>") != -1) {turnOn(blue); break;}
      if (data.indexOf("<OFF>") != -1) {turnOff(blue); break;}
      turnOn(red);
      delay(500);
      turnOff(red);
      break;
  }
  turnOn(green);
}

void connectWifi()
{
  WiFi.mode(WIFI_AP_STA);
  WiFi.begin(wifiSSID, wifiPasswd);
  while (WiFi.status() != WL_CONNECTED) delay(500);
}

String readFile()
{
  String line;
  WiFiClient client;

  if (client.connect(server, 80))
  {
    client.println("GET " + String(filename) + " HTTP/1.1");
    client.println("Host: " + String(server));
    client.println("User-Agent: ArduinoWiFi/heating");
    client.println("Connection: close");
    client.println();
    delay(100);
    while (client.available())
    {
      line = client.readStringUntil('\r');
    }
  }
  client.stop();

  return line;
}
