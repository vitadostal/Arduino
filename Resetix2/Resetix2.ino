//Power on/off/restart PC via SIM800L module
//Connect computer power switch/reset switch via relays to Arduino Yun Board
//Vitezslav Dostal | started 25.12.2018

#include <Console.h>
#include <SoftwareSerial.h>
//Warning: _SS_MAX_RX_BUFF must be set to 128 in SoftwareSerial library

SoftwareSerial sim800(10, 11); //RX, TX
const int relayReset = 12; //Relay IN
const int relayPower = 13; //Relay IN
String reply;
int counterSMS = 0;
int counterSetup = 0;

void setup()
{
  Bridge.begin();
  Console.begin();
  sim800.begin(9600);

  pinMode(relayPower,    OUTPUT);
  digitalWrite(relayPower,  LOW);

  pinMode(relayReset,    OUTPUT);
  digitalWrite(relayReset,  LOW);

  Console.println("Setting baud rate...");
  sim800.println("AT+IPR=9600");
  updateSerial();

  Console.println("Setting SMS text format...");
  sim800.println("AT+CMGF=1");
  updateSerial();

  Console.println("Disabling SMS notifications...");
  sim800.println("AT+CNMI=0,0");
  updateSerial();

  Console.println("Deleting all SMS messages...");
  sim800.println("AT+CMGD=1,4");
  updateSerial();
}

void loop()
{
  counterSMS++;
  if (counterSMS > 20)
  {
    counterSMS = 0;
    Console.println("Looking for new SMS messages...");
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

    Console.println("Setting baud rate...");
    sim800.println("AT+IPR=9600");
    updateSerial();

    Console.println("Setting SMS text format...");
    sim800.println("AT+CMGF=1");
    updateSerial();

    Console.println("Disabling SMS notifications...");
    sim800.println("AT+CNMI=0,0");
    updateSerial();
  }
}

void resetEvent(String msg)
{
  Console.println(msg);
  reply = "";
  sim800.println("AT+CMGD=1,4");
  updateSerial();
}

void updateSerial()
{
  delay(500);
  while (Console.available())
  {
    sim800.write(Console.read());
  }
  while (sim800.available())
  {
    reply = sim800.readString();
    Console.println(reply);
  }
}
