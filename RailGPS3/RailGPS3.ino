//Railtour GPS data gatherer III
//Vitezslav Dostal | started 19.5.2019
//Hardware required: Attiny 84A & UbloxNeo & SIM800L
#include <SoftwareSerial.h>
#include <EEPROM.h>
#define serbaud 9600
#define sertx 5
#define serrx 4
#define gpsbaud 4800
#define gpstx 9
#define gpsrx 10
#define simbaud 4800
#define simtx 1
#define simrx 2
#define simreset 0

#define cycles 39                                          //Measures stored in flash memory
#define packet 13                                          //Size of one measure in bytes
#define last 507                                           //cycles * packet
#define modulo 5                                           //number of measures when sending is triggered
#define sleepsat 8                                         //number of satellites to enable GPS sleeping
#define sleep 50                                           //sleep interval for GPS module [s]
#define interval 60                                        //interval between measures [s]

const char sensor[] PROGMEM = "";                          //Sensor indentification
const char server[] PROGMEM = "";                          //Processing server
const char key[]    PROGMEM = "";                          //API write key

const char c0[]     PROGMEM = "AT";
const char c1[]     PROGMEM = "AT+IPR=4800";               //The speed must be set also here!
const char c2[]     PROGMEM = "AT+CBC";
const char c3[]     PROGMEM = "AT+CSTT=\"internet\",\"\",\"\"";
const char c4[]     PROGMEM = "AT+CIICR";
const char c5[]     PROGMEM = "AT+CIPSTATUS";
const char c6[]     PROGMEM = "AT+CIFSR";
const char c7[]     PROGMEM = "AT+CIPSEND=730";            //The size must match precisely!
const char c8[]     PROGMEM = "AT+CIPQSEND=1";
const char c9[]     PROGMEM = "AT+CIPCLOSE";
const char c10[]    PROGMEM = "AT+CIPSHUT";
const char c11[]    PROGMEM = "AT+CSCLK=2";
const char c12[]    PROGMEM = "AT+CIPSTART=\"TCP\",\"";
const char c16[]    PROGMEM = "POST /script/measure_add_gps2.php HTTP/1.1";
const char c17[]    PROGMEM = "\r\n";
const char c18[]    PROGMEM = "Host: ";
const char c19[]    PROGMEM = "User-Agent: ArduinoSIM800";
const char c20[]    PROGMEM = "Connection: close";
const char c21[]    PROGMEM = "Content-Type: application/x-www-form-urlencoded";
const char c22[]    PROGMEM = "Content-Length: ";
const char c23[]    PROGMEM = "|";
const char c24[]    PROGMEM = "Ublox sleep: ";
const char c25[]    PROGMEM = "Attiny sleep: ";
const char c26[]    PROGMEM = "Cycle: ";
const char c27[]    PROGMEM = " lat: ";
const char c28[]    PROGMEM = " lng: ";
const char c29[]    PROGMEM = " sat: ";
const char c30[]    PROGMEM = ">>> ";
const char c31[]    PROGMEM = "Modem reset";
const char c32[]    PROGMEM = "AT+CIPSEND?";
const char c33[]    PROGMEM = "AT+CIPCLOSE";
const char c34[]    PROGMEM = "00";
const char c35[]    PROGMEM = "4200";
const char c36[]    PROGMEM = "\",80";
const char c37[]    PROGMEM = "AT+CPOWD=1";
const char c38[]    PROGMEM = "No GPS";

const char *const string_table[] PROGMEM = {c0, c1, c2, c3, c4, c5, c6, c7, c8, c9, c10, c11, c12, sensor, server, key,
                                            c16, c17, c18, c19, c20, c21, c22, c23, c24, c25, c26, c27, c28, c29, c30,
                                            c31, c32, c33, c34, c35, c36, c37, c38
                                           };
const unsigned char UBX_HEADER[] = { 0xB5, 0x62 };
char buffer[64];
char voltage[4];
bool fail = false;
bool modify = false;
bool success = false;
byte current = 0;
byte comma = 0;

SoftwareSerial serial(serrx, sertx);
SoftwareSerial ublox(gpsrx, gpstx);
SoftwareSerial sim800(simrx, simtx);

struct NAV_PVT {
  unsigned char cls;
  unsigned char id;
  unsigned short len;
  unsigned long iTOW;          // GPS time of week of the navigation epoch (ms)

  unsigned short year;         // Year (UTC)
  unsigned char month;         // Month, range 1..12 (UTC)
  unsigned char day;           // Day of month, range 1..31 (UTC)
  unsigned char hour;          // Hour of day, range 0..23 (UTC)
  unsigned char minute;        // Minute of hour, range 0..59 (UTC)
  unsigned char second;        // Seconds of minute, range 0..60 (UTC)
  char valid;                  // Validity Flags (see graphic below)
  unsigned long tAcc;          // Time accuracy estimate (UTC) (ns)
  long nano;                   // Fraction of second, range -1e9 .. 1e9 (UTC) (ns)
  unsigned char fixType;       // GNSSfix Type, range 0..5
  char flags;                  // Fix Status Flags
  unsigned char reserved1;     // reserved
  unsigned char numSV;         // Number of satellites used in Nav Solution

  long lon;                    // Longitude (deg)
  long lat;                    // Latitude (deg)
  long height;                 // Height above Ellipsoid (mm)
  long hMSL;                   // Height above mean sea level (mm)
  unsigned long hAcc;          // Horizontal Accuracy Estimate (mm)
  unsigned long vAcc;          // Vertical Accuracy Estimate (mm)

  long velN;                   // NED north velocity (mm/s)
  long velE;                   // NED east velocity (mm/s)
  long velD;                   // NED down velocity (mm/s)
  long gSpeed;                 // Ground Speed (2-D) (mm/s)
  long heading;                // Heading of motion 2-D (deg)
  unsigned long sAcc;          // Speed Accuracy Estimate
  unsigned long headingAcc;    // Heading Accuracy Estimate
  unsigned short pDOP;         // Position dilution of precision
  short reserved2;             // Reserved
  unsigned long reserved3;     // Reserved
};

NAV_PVT pvt;

void calcChecksum(unsigned char* CK) {
  memset(CK, 0, 2);
  for (int i = 0; i < (int)sizeof(NAV_PVT); i++) {
    CK[0] += ((unsigned char*)(&pvt))[i];
    CK[1] += CK[0];
  }
}

void setChecksum(unsigned char* data, int length) {
  data[length - 2] = 0;
  data[length - 1] = 0;
  for (int i = 2; i < length - 2; i++) {
    data[length - 2] += data[i];
    data[length - 1] += data[length - 2];
  }
}

bool processGPS() {
  static int fpos = 0;
  static unsigned char checksum[2];
  const int payloadSize = sizeof(NAV_PVT);

  while ( ublox.available() ) {
    byte c = ublox.read();
    if ( fpos < 2 ) {
      if ( c == UBX_HEADER[fpos] )
        fpos++;
      else
        fpos = 0;
    }
    else {
      if ( (fpos - 2) < payloadSize )
        ((unsigned char*)(&pvt))[fpos - 2] = c;

      fpos++;

      if ( fpos == (payloadSize + 2) ) {
        calcChecksum(checksum);
      }
      else if ( fpos == (payloadSize + 3) ) {
        if ( c != checksum[0] )
          fpos = 0;
      }
      else if ( fpos == (payloadSize + 4) ) {
        fpos = 0;
        if ( c == checksum[1] ) {
          return true;
        }
      }
      else if ( fpos > (payloadSize + 4) ) {
        fpos = 0;
      }
    }
  }
  return false;
}

static void readUblox(int ms)
{
  ublox.listen();
  unsigned long start = millis();
  do
  {
    if (processGPS()) return;
    delay(5);
  }
  while (millis() < start + ms);
}

void sleepUblox()
{
  //UBX-RXM-PMREQ
  unsigned char data[] = {0xB5, 0x62, 0x02, 0x41, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00};
  unsigned long num = sleep;
  num = num * 1000;
  memcpy(&data[6], &num, 4);
  setChecksum(data, sizeof(data));

  for (int i = 0; i < sizeof(data); i++)
  {
    ublox.write(data[i]);
    delay(5);
  }

  prepare(24); serial.print(buffer); //Ublox sleep:
  serial.println(sleep);
}

void sweetDreams(unsigned long int period)
{
  prepare(25); serial.print(buffer); //Attiny sleep:
  serial.println(period);
  delay(period * 1000);
}

void setup()
{
  serial.begin(serbaud);
  serial.println();
  ublox.begin(gpsbaud);
  sim800.begin(simbaud);
}

void loop()
{
  readUblox(3000);
  if (pvt.fixType < 2) {
    prepare(38); serial.println(buffer); //GPS lost
    readUblox(3000);
  }
  if (pvt.fixType < 2) {
    prepare(38); serial.println(buffer); //GPS lost
    readUblox(3000);
  }
  if (pvt.fixType >= 2)
  {
    //Successful measure
    updateFlashMemory(1);

    //Sleep Ublox when a lot of satellites found
    if (sleep && pvt.numSV >= sleepsat) sleepUblox();
  }
  else
  {
    //Unsuccessful measure
    updateFlashMemory(0);
  }

  //Send results
  if (current % modulo == 1)
  {
    gprs();
    sweetDreams(interval - 10);
  }
  else
  {
    sweetDreams(interval);
  }
}

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

void updateFlashMemory(byte realdata)
{
  unsigned long dt_time = pvt.hour;
  dt_time *= 3600;
  dt_time += pvt.minute * 60;
  dt_time += pvt.second;
  unsigned long dt_date = pvt.year - 2000;
  dt_date *= 372;
  dt_date += pvt.month * 31;
  dt_date += pvt.day;
  unsigned long dt = dt_date * 100000 + dt_time;

  if (current < 0 || current >= cycles) current = 0;
  unsigned int pointer = current;
  pointer *= packet;

  if (realdata)
  {
    EEPROM_writeAnything(pointer + 0, dt);
    EEPROM_writeAnything(pointer + 4, pvt.numSV);
    EEPROM_writeAnything(pointer + 5, pvt.lon);
    EEPROM_writeAnything(pointer + 9, pvt.lat);
  }
  else
  {
    EEPROM_writeAnything(pointer + 0, dt);
    EEPROM_writeAnything(pointer + 4, 0);
    EEPROM_writeAnything(pointer + 5, 0);
    EEPROM_writeAnything(pointer + 9, 0);
  }

  prepare(26); serial.print(buffer); //Cycle:
  serial.print(current);
  prepare(27); serial.print(buffer); //lat:
  serial.print(pvt.lat);
  prepare(28); serial.print(buffer); //lng:
  serial.print(pvt.lon);
  prepare(29); serial.print(buffer); //sat:
  serial.println(pvt.numSV);

  current++;
  if (current < 0 || current >= cycles) current = 0;
}

void prepare(byte which) {
  strcpy_P(buffer, (char *)pgm_read_word(&(string_table[which])));
}

void gprs() {
  sim800.listen();
  prepare(0);  sim800.println(buffer); delay(500);

  prepare(0);  communicate(); //AT
  prepare(1);  communicate(); //AT+IPR=9600
  prepare(2);  communicate(); //AT+CBC
  if (comma < 50)
  {
    voltage[0] = buffer[comma + 1];
    voltage[1] = buffer[comma + 2];
    voltage[2] = buffer[comma + 3];
    voltage[3] = buffer[comma + 4];
  }
  prepare(3);  communicate(); //AT+CSTT="internet","",""
  prepare(4);  communicate(); //AT+CIICR
  prepare(5);  communicate(); //AT+CIPSTATUS
  prepare(6);  communicate(); //AT+CIFSR
  modify = true;
  prepare(12); communicate(); //AT+CIPSTART="TCP","",80
  modify = false;
  if (!fail) delay (3000);
  prepare(0);  communicate(); //AT
  prepare(0);  communicate(); //AT
  prepare(0);  communicate(); //AT
  prepare(8);  communicate(); //AT+CIPQSEND=1
  prepare(7);  communicate(); //AT+CIPSEND=size
  if (!fail) {
    delay(3000);
    trasmit();
    delay(3000);
  }
  prepare(32); communicate(); //AT+CIPSEND?
  prepare(33); communicate(); //AT+CIPCLOSE
  prepare(10); communicate(); //AT+CIPSHUT
  prepare(11); communicate(); //AT+CSCLK=2
  //prepare(37); communicate(); //AT+CPOWD=1
  if (!success) fail = true;

  if (fail)
  {
    fail = false;
    prepare(31); serial.println(buffer); //Modem reset
    pinMode(simreset, OUTPUT);
    digitalWrite(simreset, LOW);
    delay(50);
    pinMode(simreset, INPUT_PULLUP);
  }
}

void communicate()
{
  if (!fail) {
    sendCommand();
    receiveCommand();
  }
}

void sendCommand()
{
  serial.print(">>> ");
  serial.print(buffer);
  sim800.print(buffer);

  if (modify)
  {
    prepare(14); serial.print(buffer); sim800.print(buffer);
    prepare(36); serial.print(buffer); sim800.print(buffer);
  }

  serial.println();
  sim800.println();
}

void receiveCommand()
{
  unsigned long stamp;
  success = false;
  char thischar = 0;
  stamp = millis();
  byte i = 1;

  while (!sim800.available() && millis() < stamp + 5000) {
    delay (50);
  }
  if (millis() > stamp + 5000) fail = true;
  delay (100);

  while (sim800.available()) {
    thischar = sim800.read();
    serial.write(thischar);

    buffer[i] = thischar;
    if (buffer[i - 1] == 'O' && buffer[i] == 'K') success = true;
    if (buffer[i] == ',') comma = i;

    i++;
    if (i >= 60) i = 1;
  }
  serial.println();
}

void trasmit()
{
  prepare(16); sim800.print(buffer); //POST /script/measure_add_gps2.php HTTP/1.1
  prepare(17); sim800.print(buffer); //LINE
  prepare(18); sim800.print(buffer); //Host:
  prepare(14); sim800.print(buffer); //SERVER
  prepare(17); sim800.print(buffer); //LINE
  prepare(19); sim800.print(buffer); //User-Agent: Arduinosim800
  prepare(13); sim800.print(buffer); //SENSOR
  prepare(17); sim800.print(buffer); //LINE
  prepare(20); sim800.print(buffer); //Connection: close
  prepare(17); sim800.print(buffer); //LINE
  prepare(21); sim800.print(buffer); //Content-Type: application/x-www-form-urlencoded
  prepare(17); sim800.print(buffer); //LINE
  prepare(22); sim800.print(buffer); //Content-Length:
  sim800.print(cycles * packet + 12 + 5 + 4 + 2 + 4 * 1); //Contet + Key + Sensor + Voltage + Battery + Delimiters
  prepare(17); sim800.print(buffer); //LINE
  prepare(17); sim800.print(buffer); //LINE

  prepare(15); sim800.print(buffer); //KEY
  prepare(23); sim800.print(buffer); //|
  prepare(13); sim800.print(buffer); //SENSOR
  prepare(23); sim800.print(buffer); //|
  sim800.print(voltage[0]);
  sim800.print(voltage[1]);
  sim800.print(voltage[2]);
  sim800.print(voltage[3]);
  prepare(23); sim800.print(buffer); //|
  prepare(34); sim800.print(buffer); //00
  prepare(23); sim800.print(buffer); //|

  byte data;
  for (int address = 0; address < cycles * packet ; address++) {
    data = EEPROM.read(address);
    sim800.write(data);
  }

  prepare(17); sim800.print(buffer); //LINE
  prepare(17); sim800.print(buffer); //LINE
}