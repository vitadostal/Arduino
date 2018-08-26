//Power on/off/restart PC
//Connect computer power switch/reset switch via relays to Arduino Yun Board
//Vitezslav Dostal | started 20.05.2017

#include <Console.h>
const int relayPower = 13; //Relay IN
const int relayReset = 12; //Relay IN
int incomingByte;

void setup()
{
  Bridge.begin();
  Console.begin();

  pinMode(relayPower,    OUTPUT);
  digitalWrite(relayPower,  LOW);
  
  pinMode(relayReset,    OUTPUT);
  digitalWrite(relayReset,  LOW);

  while (!Console){};
  Console.println("You're connected to the Arduino Yun Console");
  Console.println("Please enter P for POWER ON/OFF ");
  Console.println("             R for RESET ");
  Console.println("             S for SHUTDOWN ");
  Console.println("             H for HELP ");
}

void loop()
{
  if (Console.available() > 0)
  {
    incomingByte = Console.read();
    if (incomingByte == 'P')
    {
      digitalWrite(relayPower, HIGH);
      delay(100);
      digitalWrite(relayPower, LOW);
    }
    if (incomingByte == 'R')
    {
      digitalWrite(relayReset, HIGH);
      delay(500);
      digitalWrite(relayReset, LOW);
    }    
    if (incomingByte == 'S')
    {
      digitalWrite(relayPower, HIGH);
      delay(10000);
      digitalWrite(relayPower, LOW);
    }
    if (incomingByte == 'H')
    {
      Console.println("Please enter P for POWER ON/OFF ");
      Console.println("             R for RESET ");
      Console.println("             S for SHUTDOWN ");
      Console.println("             H for HELP ");
    }    
  }
  
  delay(100);
}
