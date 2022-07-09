//Hardware required: T-Call V1.4 ESP32
#include <SoftwareSerial.h>
#define console Serial
#define DEVBAUD  9600
#define SERBAUD 38400
#define SIMPOWER   23
#define SIMRX      26
#define SIMTX      27
SoftwareSerial modem(SIMRX, SIMTX);

void setup() {
  pinMode(SIMPOWER, OUTPUT);
  digitalWrite(SIMPOWER, HIGH); 
  
  modem.begin(DEVBAUD);
  console.begin(SERBAUD);
}

void loop() {
  if (modem.available()) {
    console.write(modem.read());
  }

  if (console.available()) {
    modem.write(console.read());
  }
}

void updateSerial()
{
  delay(500);
  while (console.available()) 
  {
    modem.write(console.read());
  }
  while(modem.available()) 
  {
    console.write(modem.read());
  }
}
