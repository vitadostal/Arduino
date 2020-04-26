//Railtour GPS data gatherer IV
//Vitezslav Dostal | started 30.10.2019
//Hardware required: Attiny 1634 & AT24C32 & UbloxNeo & SIM800L (FW 1418B05SIM800L24)

// Bit manipulation macros
#define BIT_SET(byte,nbit)   byte |=  (1 << nbit)
#define BIT_CLEAR(byte,nbit) byte &= ~(1 << nbit)
#define BIT_FLIP(byte,nbit)  byte ^=  (1 << nbit)
#define BIT_CHECK(byte,nbit) byte &   (1 << nbit)

#define GPSBAUD 4800
#define SIMBAUD 9600
#define SERBAUD 9600
#define BTNSEND 3
#define SIMRESET 2
#define BTNMEASURE 0
#define GPSTX 4
#define GPSRX 5
#define FLASHSCL 6
#define FLASHSDA 7
#define BTSSAT 100

#ifndef CYCLES
#define CYCLES 69                                          //Measures stored in flash memory
#endif

#define PACKET 13                                          //Size of one measure in bytes
#define PACKETS CYCLES * PACKET                            //Size of all measures in bytes
#define MPACKET 32                                         //Size of one measure in flash memory in bytes
#define MSHIFT 0                                           //Flash memory shift in bytes

#ifndef MODULO
#define MODULO 3                                           //Number of measures followed by data sending
#endif

#ifndef SLEEPSAT
#define SLEEPSAT 8                                         //Number of satellites to enable GPS sleeping
#endif

#ifndef BEFORE
#define BEFORE 15                                          //Wake Ublox before measure [s]
#endif

#ifndef INTERVAL
#define INTERVAL 121                                       //Interval between measures [s]
#endif

#ifndef GPSMODULE
#define GPSMODULE 1                                        //Listen to GPS module
#endif

#ifndef BTSCHECK
#define BTSCHECK 1                                         //Use SIM module capabilities to compute position (triangulation)
#endif

#ifndef SENSOR
#define SENSOR ""                                          //Sensor identification
#endif

#ifndef SERVER
#define SERVER ""                                          //Processing server URL
#endif

#ifndef SERVERPORT
#define SERVERPORT "80"                                    //Processing server port
#endif

#ifndef SERVERPATH
#define SERVERPATH "/script/measure_add_gps2.php"          //Processing script path
#endif

#ifndef APIKEY
#define APIKEY ""                                          //API write key
#endif

#define SDA_PORT PORTA                                     //AT24C32 SDA port
#define SCL_PORT PORTA                                     //AT24C32 SCL port
#define SDA_PIN 1                                          //AT24C32 SDA port pin
#define SCL_PIN 2                                          //AT24C32 SCL port pin

#define FLAGS_ALARM_PAST 0                                 //Position of flag bit - alarm was activated in the past
#define FLAGS_ALARM_NOW 1                                  //Position of flag bit - alarm was activated right now

const char sensor[] PROGMEM = SENSOR;                      //Sensor indentification
const char server[] PROGMEM = SERVER;                      //Processing server
const char key[]    PROGMEM = APIKEY;                      //API write key

const int  address  PROGMEM = 0x50;                        //Memory chip address

#include <SoftWire.h>
#include <SoftwareSerial.h>

const char c0[]     PROGMEM = "AT";
const char c1[]     PROGMEM = "AT+IPR=";
const char c2[]     PROGMEM = "AT+CBC";
const char c3[]     PROGMEM = "AT+CSTT=\"internet\",\"\",\"\"";
const char c4[]     PROGMEM = "AT+CIICR";
const char c5[]     PROGMEM = "AT+CIPSTATUS";
const char c6[]     PROGMEM = "AT+CIFSR";
const char c8[]     PROGMEM = "AT+CIPQSEND=1";
const char c9[]     PROGMEM = "AT+CIPCLOSE";
const char c10[]    PROGMEM = "AT+CIPSHUT";
const char c11[]    PROGMEM = "AT+CSCLK=2";
const char c12[]    PROGMEM = "AT+CIPSTART=\"TCP\",\"";
const char c16[]    PROGMEM = "POST " SERVERPATH " HTTP/1.1";
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
const char c35[]    PROGMEM = "4200";
const char c36[]    PROGMEM = "\"," SERVERPORT;
const char c37[]    PROGMEM = "AT+CPOWD=1";
const char c38[]    PROGMEM = "No GPS";
const char c39[]    PROGMEM = "Measure button";
const char c40[]    PROGMEM = "Send button";
const char c41[]    PROGMEM = "Iteration: ";
const char c42[]    PROGMEM = "AT+SAPBR=3,1,\"Contype\",\"GPRS\"";
const char c43[]    PROGMEM = "AT+SAPBR=3,1,\"APN\",\"internet\"";
const char c44[]    PROGMEM = "AT+SAPBR=1,1";
const char c45[]    PROGMEM = "AT+SAPBR=2,1";
const char c46[]    PROGMEM = "AT+CLBS=4,1";
const char c47[]    PROGMEM = "AT+SAPBR=0,1";
const char c48[]    PROGMEM = "Same coordinates";
const char c49[]    PROGMEM = "Scanning flash memory";
const char c50[]    PROGMEM = "Next cycle: ";
const char c51[]    PROGMEM = ": ";
const char c52[]    PROGMEM = " time: ";
const char c53[]    PROGMEM = "AT+CGMR";
const char c54[]    PROGMEM = "AT+CLBSCFG=1,3,\"lbs-simcom.com:3002\"";
const char c55[]    PROGMEM = "AT+CLBSCFG=0,3";
const char c56[]    PROGMEM = "AT+CIPGSMLOC=2,1";
const char c57[]    PROGMEM = "Timestamp: ";

const unsigned char UBX_HEADER[] = { 0xB5, 0x62 };
const unsigned long wait = INTERVAL;
char memory[13];
char buffer[64];
char temp[10];
char voltage[4];
bool fail = false;
bool modify = false;
bool success = false;
bool signal = false;
bool btnMeasureLast = false;
bool btnSendLast = false;
byte analyze = 0;
bool analyzed = false;
byte current = 0;
byte iterator = 0;
byte comma = 0;
long lastlat = 0;
long lastlon = 0;
unsigned long timer;
unsigned int limit = 10000;
char delim[2];

SoftwareSerial ublox(GPSRX, GPSTX);
SoftWire Wire1 = SoftWire();

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

byte flags;

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
  if (!GPSMODULE) return;

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
  unsigned long num = INTERVAL;
  num -= BEFORE;
  num = num * 1000;
  memcpy(&data[6], &num, 4);
  setChecksum(data, sizeof(data));

  for (unsigned int i = 0; i < sizeof(data); i++)
  {
    ublox.write(data[i]);
    delay(5);
  }

  load((char*)&c24); Serial1.print(buffer); //Ublox sleep:
  Serial1.println(INTERVAL - BEFORE);
}

void sweetDreams(unsigned long int period)
{
  load((char*)&c25); Serial1.print(buffer); //Attiny sleep:
  Serial1.println(period);
  delay(period * 1000);
}

void setup()
{
  memset(&memory, 255, PACKET);
  delim[0] = 0x1a;
  delim[1] = 0x00;
  if (BTNMEASURE) pinMode(BTNMEASURE, INPUT_PULLUP);
  if (BTNSEND) pinMode(BTNSEND, INPUT_PULLUP);
  btnMeasureLast = true;
  btnSendLast = true;
  pinMode(FLASHSCL, INPUT_PULLUP);
  pinMode(FLASHSDA, INPUT_PULLUP);
  Wire1.begin();
  Serial1.begin(SERBAUD);
  Serial1.println();
  ublox.begin(GPSBAUD);
  Serial.begin(SIMBAUD);
  updateCurrent();
  measure();
}

void loop()
{
  delay(100);

  //Early measure
  if (BTNMEASURE) {
    if (!btnMeasureLast) {
      if (digitalRead(BTNMEASURE) == HIGH) {
        btnMeasureLast = true;
        load((char*)&c39); Serial1.println(buffer); //Measure button pressed
        ublox.write(0xFF);
        delay(10000);
        measure();
      }
    } else {
      if (digitalRead(BTNMEASURE) == LOW) {
        btnMeasureLast = false;
      }
    }
  }

  //Early send
  if (BTNSEND) {
    if (!btnSendLast) {
      if (digitalRead(BTNSEND) == HIGH) {
        btnSendLast = true;
        BIT_SET(flags,FLAGS_ALARM_PAST);
        BIT_SET(flags,FLAGS_ALARM_NOW);
        load((char*)&c40); Serial1.println(buffer); //Send button pressed
        gprs();
      }
    } else {
      if (digitalRead(BTNSEND) == LOW) {
        btnSendLast = false;
      }
    }
  }

  if (millis() - timer > wait * 1000) measure();
}

void measure()
{
  timer = millis();

  iterator++;
  Serial1.println();
  load((char*)&c41); Serial1.print(buffer); //Iteration:
  Serial1.println(iterator);
  if (iterator < 0 || iterator >= MODULO) iterator = 0;

  readUblox(3000);
  if (pvt.fixType < 2) {
    load((char*)&c38); Serial1.println(buffer); //No GPS
    readUblox(3000);
  }
  if (pvt.fixType < 2) {
    load((char*)&c38); Serial1.println(buffer); //No GPS
    readUblox(3000);
  }
  if (pvt.fixType >= 2)
  {
    //Successful measure
    if (!repeatingCoordinates())
    {
      updateFlashMemory(pvt.lon, pvt.lat);

      //Sleep Ublox when a lot of satellites found
      if (SLEEPSAT > 0 && pvt.numSV >= SLEEPSAT) sleepUblox();
    }
    else
    {
      load((char*)&c48); //Same coordinates
      Serial1.println(buffer);
    }
  }

  //Send results
  if (iterator % MODULO == 0) gprs();
}

void updateFlashMemory(long lon, long lat)
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

  if (current < 0 || current >= CYCLES) current = 0;

  memcpy(&memory[0], &dt, 4);
  memcpy(&memory[4], &pvt.numSV, 1);
  memcpy(&memory[5], &lon, 4);
  memcpy(&memory[9], &lat, 4);

  writeMemoryPacket(current);

  load((char*)&c26); Serial1.print(buffer); //Cycle:
  Serial1.print(current);
  load((char*)&c27); Serial1.print(buffer); //lat:
  Serial1.print(lat);
  load((char*)&c28); Serial1.print(buffer); //lng:
  Serial1.print(lon);
  load((char*)&c52); Serial1.print(buffer); //time:
  Serial1.print(dt);
  load((char*)&c29); Serial1.print(buffer); //sat:
  Serial1.println(pvt.numSV);

  signal = true;
  current++;
  if (current < 0 || current >= CYCLES) current = 0;
}

void load(char* which) {
  strcpy_P(buffer, which);
}

void loadAtPosition(const char* what, byte position) {
  sprintf(temp, "%d", what);
  memcpy(&buffer[position], &temp, strlen(temp));
  memcpy(&buffer[position] + strlen(temp), '\0', 1);
}

void gprs() {
  Serial1.println();
  load((char*)&c0);  Serial.println(buffer); delay(500);

  load((char*)&c0); communicate(); //AT
  load((char*)&c53); communicate(); //AT-ver
  load((char*)&c1); loadAtPosition(SIMBAUD, 7); communicate(); //AT+IPR=4800
  load((char*)&c2); communicate(); //AT+CBC
  if (comma < 50)
  {
    voltage[0] = buffer[comma + 1];
    voltage[1] = buffer[comma + 2];
    voltage[2] = buffer[comma + 3];
    voltage[3] = buffer[comma + 4];
  }
  if (!signal && BTSCHECK)
  {
    load((char*)&c42); communicate(); //AT+SAPBR=3,1,"Contype","GPRS"
    load((char*)&c43); communicate(); //AT+SAPBR=3,1,"APN","internet"
    load((char*)&c44); communicate(); //AT+SAPBR=1,1
    load((char*)&c45); communicate(); //AT+SAPBR=2,1
    load((char*)&c55); analyze = 1; communicate(); //AT+CLBSCFG=0,3

    for (byte i = 0; i < 7; i++)
    {
      load((char*)&c56); communicate(); //AT+CIPGSMLOC=2,1
      load((char*)&c0); analyze = 2; communicate(); //AT
      if (analyzed) break;
    }

    if (analyzed) for (byte i = 0; i < 7; i++)
    {
      load((char*)&c46); limit = 30000; communicate(); //AT+CLBS=4,1
      load((char*)&c0); analyze = 3; limit = 30000; communicate(); //AT
      if (analyzed) break;
    }

    load((char*)&c47); communicate(); //AT+SAPBR=0,1
  }
  signal = false;
  load((char*)&c3); communicate(); //AT+CSTT="internet","",""
  load((char*)&c4); communicate(); //AT+CIICR
  load((char*)&c5); communicate(); //AT+CIPSTATUS
  load((char*)&c6); communicate(); //AT+CIFSR
  modify = true;
  load((char*)&c12); communicate(); //AT+CIPSTART="TCP","",80
  modify = false;
  if (!fail) delay (3000);
  load((char*)&c0); communicate(); //AT
  load((char*)&c0); communicate(); //AT
  load((char*)&c0); communicate(); //AT
  load((char*)&c8); communicate(); //AT+CIPQSEND=1
  //157 is a magic constant that covers all http protocol headers etc.
  sprintf(buffer, "AT+CIPSEND=%d", PACKETS + 157 + strlen(SERVER) + strlen(SERVERPATH) + strlen(SENSOR) + strlen(APIKEY));
  communicate(); //AT+CIPSEND=size  
  if (!fail) {
    delay(2000);
    trasmit();
    delay(2000);
  }
  load((char*)&c32); communicate(); //AT+CIPSEND?
  load((char*)&c33); communicate(); //AT+CIPCLOSE
  load((char*)&c10); communicate(); //AT+CIPSHUT
  load((char*)&c11); communicate(); //AT+CSCLK=2
  //load((char*)&c37); communicate(); //AT+CPOWD=1
  if (!success) fail = true;

  if (fail)
  {
    fail = false;
    load((char*)&c31); Serial1.println(buffer); //Modem reset
    pinMode(SIMRESET, OUTPUT);
    digitalWrite(SIMRESET, LOW);
    delay(50);
    pinMode(SIMRESET, INPUT_PULLUP);
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
  Serial1.print(">>> ");
  Serial1.print(buffer);
  Serial.print(buffer);

  if (modify)
  {
    load((char*)&server); Serial1.print(buffer); Serial.print(buffer);
    load((char*)&c36); Serial1.print(buffer); Serial.print(buffer);
  }

  Serial1.println();
  Serial.println();
}

void receiveCommand()
{
  unsigned long stamp;
  success = false;
  char thischar = 0;
  stamp = millis();
  byte i = 1;
  byte j = 0;
  buffer[0] = '#';

  while (!Serial.available() && millis() < stamp + limit);
  if (millis() >= stamp + limit)
  {
    fail = true;
  }
  else
  {
    for (j = 0; j < 20; j++) {
      while (Serial.available()) {
        thischar = Serial.read();
    
        buffer[i] = thischar;
        if (buffer[i - 1] == 'O' && buffer[i] == 'K') success = true;
        if (buffer[i] == ',') comma = i;
    
        i++;
        if (i > 62) i = 1;
      }
      delay(10);
    }
  
    buffer[i] = delim[1];
    Serial1.write(buffer);
  
    if (analyze == 1) BTSServer();
    if (analyze == 2) BTSDateTime();
    if (analyze == 3) BTSLocation();
  }

  analyze = 0;
  limit = 10000;
  Serial1.println();
}

void trasmit()
{
  load((char*)&c16); Serial.print(buffer); //POST /script/measure_add_gps2.php HTTP/1.1
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c18); Serial.print(buffer); //Host:
  load((char*)&server); Serial.print(buffer); //SERVER
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c19); Serial.print(buffer); //User-Agent: ArduinoSIM800
  load((char*)&sensor); Serial.print(buffer); //SENSOR
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c20); Serial.print(buffer); //Connection: close
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c21); Serial.print(buffer); //Content-Type: application/x-www-form-urlencoded
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c22); Serial.print(buffer); //Content-Length:
  Serial.print(PACKETS + strlen(APIKEY) + strlen(SENSOR) + 4 + 1 + 4); // Content + Key + Sensor + Voltage + Flags + Delimiters
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c17); Serial.print(buffer);

  load((char*)&key); Serial.print(buffer); //KEY
  load((char*)&c23); Serial.print(buffer); //Separator |
  load((char*)&sensor); Serial.print(buffer); //SENSOR
  load((char*)&c23); Serial.print(buffer); //Separator |
  Serial.print(voltage[0]);
  Serial.print(voltage[1]);
  Serial.print(voltage[2]);
  Serial.print(voltage[3]);
  load((char*)&c23); Serial.print(buffer); //Separator |
  Serial.write(flags); BIT_CLEAR(flags,FLAGS_ALARM_NOW);
  Serial.print(delim[1]);
  load((char*)&c23); Serial.print(buffer); //Separator |

  Serial1.print(buffer);
  for (byte i = 0; i < CYCLES; i++)
  {
    readMemoryPacket(i);
    for (byte j = 0; j < PACKET; j++) Serial.write(memory[j]);
    Serial1.print(i);
    Serial1.print(buffer);
  }
  Serial1.println();

  load((char*)&c17); Serial.print(buffer); //LINE
  load((char*)&c17); Serial.print(buffer); //LINE
}

void BTSServer()
{
  for (byte i = 0; i <= 30; i++)
  {
    if (search(i, "c4a.com", 7))
    {
      load((char*)&c54); communicate(); //AT+CLBSCFG=1,3,"lbs-simcom.com:3002"
    }
  }
}

void BTSDateTime()
{
  analyzed = false;
  for (byte i = 0; i <= 30; i++)
  {
    if (search(i, "GSMLOC: 0,", 10))
    {
      pvt.year   = convert(i + 10, 4);
      pvt.month  = convert(i + 15, 2);
      pvt.day    = convert(i + 18, 2);
      pvt.hour   = convert(i + 21, 2);
      pvt.minute = convert(i + 24, 2);
      pvt.second = convert(i + 27, 2);
      pvt.numSV  = BTSSAT;

      Serial1.println();
      Serial1.println();
      load((char*)&c57); Serial1.print(buffer); //Timestamp:
      Serial1.print(pvt.day);
      Serial1.print(".");
      Serial1.print(pvt.month);
      Serial1.print(".");
      Serial1.print(pvt.year);
      Serial1.print(" ");      
      Serial1.print(pvt.hour);
      Serial1.print(":");      
      Serial1.print(pvt.minute);
      Serial1.print(":");      
      Serial1.print(pvt.second);      
      Serial1.println();

      analyzed = true;
      break;
    }
  }
}

void BTSLocation()
{
  analyzed = false;
  for (byte i = 0; i <= 30; i++)
  {
    if (search(i, "+CLBS: 0,", 9))
    {
      Serial1.println();
      Serial1.println();
      updateFlashMemory(convert(i + 9, 9) * 10, convert(i + 19, 9) * 10);
      memcpy(&buffer, 0, sizeof(buffer));

      analyzed = true;
      break;
    }
  }
}

bool search(byte from, char what[], byte size)
{
  for (byte i = 0; i < size; i++)
  {
    if (buffer[from + i] != what[i]) return false;
  }
  return true;
}

long convert(byte pos, byte len)
{
  long res = 0;
  for (byte i = 0; i < len; i++)
  {
    if (buffer[pos + i] == '.') continue;
    if (i != 0) res *= 10;
    res += (buffer[pos + i] & 0xf);
  }
  return res;
}

bool repeatingCoordinates()
{
  bool repeat = false;
  if (pvt.lat == lastlat && pvt.lon == lastlon) repeat = true;
  lastlat = pvt.lat;
  lastlon = pvt.lon;
  return repeat;
}

word memoryAddress(byte where)
{
  return where * MPACKET + MSHIFT;
}

byte highAddressByte(word address)
{
  byte BYTE_1;
  BYTE_1 = address >> 8;
  return BYTE_1;
}

byte lowAddressByte(word address)
{
  byte BYTE_1;
  byte BYTE_2;
  BYTE_1 = address >> 8;
  BYTE_2 = address - (BYTE_1 << 8);
  return BYTE_2;
}

void writeMemoryPacket(byte where)
{
  Wire1.beginTransmission(address);
  Wire1.write(highAddressByte(memoryAddress(where)));
  Wire1.write(lowAddressByte(memoryAddress(where)));

  for (byte i = 0; i < PACKET; i++) {
    Wire1.write(memory[i]);
  }

  Wire1.endTransmission();
  delay(10);
}

void readMemoryPacket(byte where)
{
  Wire1.beginTransmission(address);
  Wire1.write(highAddressByte(memoryAddress(where)));
  Wire1.write(lowAddressByte(memoryAddress(where)));
  Wire1.endTransmission();

  Wire1.requestFrom(address, PACKET);

  for (byte i = 0; i < PACKET; i++) {
    memory[i] = Wire1.read();
  }
}

unsigned long displayMemoryPacket(byte where)
{
  unsigned long dt;
  unsigned char sat;
  long lon;
  long lat;

  readMemoryPacket(where);
  memcpy(&dt,  &memory[0], 4);
  memcpy(&sat, &memory[4], 1);
  memcpy(&lon, &memory[5], 4);
  memcpy(&lat, &memory[9], 4);

  load((char*)&c26); Serial1.print(buffer); //Cycle:
  Serial1.print(where);
  load((char*)&c27); Serial1.print(buffer); //lat:
  Serial1.print(lat);
  load((char*)&c28); Serial1.print(buffer); //lng:
  Serial1.print(lon);
  load((char*)&c52); Serial1.print(buffer); //time:
  Serial1.print(dt);
  load((char*)&c29); Serial1.print(buffer); //sat:
  Serial1.println(sat);

  return dt;
}

void displayAllMemoryPackets()
{
  for (byte i = 0; i < CYCLES; i++) displayMemoryPacket(i);
}

void updateCurrent()
{
  unsigned long dt;
  unsigned long old = 0;

  load((char*)&c49); Serial1.println(buffer); //Scanning flash memory

  for (byte i = 0; i < CYCLES; i++)
  {
    dt = displayMemoryPacket(i);
    if (dt == 4294967295)
    {
      current = i;
      break;
    }
    if (dt > old)
    {
      current = i + 1;
      old = dt;
    }
  }

  if (current < 0 || current >= CYCLES) current = 0;

  load((char*)&c50); Serial1.print(buffer); //Next cycle:
  Serial1.println(current);
}
