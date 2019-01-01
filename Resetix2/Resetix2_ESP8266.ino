//Power on/off/restart PC via SIM800L module
//Connect computer power switch/reset switch via relays to ESP8266
//Vitezslav Dostal | started 25.12.2018

#include <ESP8266WiFi.h>
#include <SoftwareSerial.h>
//Warning: _SS_MAX_RX_BUFF must be set to 128 in SoftwareSerial library

SoftwareSerial sim800(5, 4); //RX, TX
const int relayReset = 12; //Relay IN
const int relayPower = 14; //Relay IN
String reply;
int counterSMS = 0;
int counterSetup = 0;

void setup()
{
  WiFi.mode(WIFI_OFF);
  Serial.begin(115200);
  sim800.begin(9600);

  pinMode(relayPower,    OUTPUT);
  digitalWrite(relayPower,  LOW);

  pinMode(relayReset,    OUTPUT);
  digitalWrite(relayReset,  LOW);

  Serial.println("Setting baud rate...");
  sim800.println("AT+IPR=9600");
  updateSerial();

  Serial.println("Setting SMS text format...");
  sim800.println("AT+CMGF=1");
  updateSerial();

  Serial.println("Disabling SMS notifications...");
  sim800.println("AT+CNMI=0,0");
  updateSerial();

  Serial.println("Deleting all SMS messages...");
  sim800.println("AT+CMGD=1,4");
  updateSerial();
}

void loop()
{
  counterSMS++;
  if (counterSMS > 20)
  {
    counterSMS = 0;
    Serial.println("Looking for new SMS messages...");
    sim800.println("AT+CMGL=\"ALL\"");
  }

  updateSerial();

  if (reply.indexOf("PC POWER") != -1)
  {
    resetEvent("<<< TRIGGER POWER >>>");
    digitalWrite(relayPower, HIGH);
    delay(100);
    digitalWrite(relayPower, LOW);
  }

  if (reply.indexOf("PC RESET") != -1)
  {
    resetEvent("<<< TRIGGER RESET >>>");
    digitalWrite(relayReset, HIGH);
    delay(500);
    digitalWrite(relayReset, LOW);
  }

  if (reply.indexOf("PC SHUTDOWN") != -1)
  {
    resetEvent("<<< TRIGGER SHUTDOWN >>>");
    digitalWrite(relayPower, HIGH);
    delay(10000);
    digitalWrite(relayPower, LOW);
  }

  if (reply.length() > 64)
  {
    resetEvent("<<< CLEAR INBOX >>>");
  }  

  counterSetup++;
  if (counterSetup > 1000)
  {
    counterSetup = 0;

    Serial.println("Setting baud rate...");
    sim800.println("AT+IPR=9600");
    updateSerial();

    Serial.println("Setting SMS text format...");
    sim800.println("AT+CMGF=1");
    updateSerial();

    Serial.println("Disabling SMS notifications...");
    sim800.println("AT+CNMI=0,0");
    updateSerial();
  }
}

void resetEvent(String msg)
{
  Serial.println(msg);
  reply = "";
  sim800.println("AT+CMGD=1,4");
  updateSerial();
}

void updateSerial()
{
  delay(500);
  while (Serial.available())
  {
    sim800.write(Serial.read());
  }
  while (sim800.available())
  {
    reply = sim800.readString();
    Serial.println(reply);
  }
}
