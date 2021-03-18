//Railtour GPS data gatherer V
//Vitezslav Dostal | started 19.08.2020
//Hardware required: Attiny 3216 & AT24C128 & 74HC4052D & UbloxNeo & SIM800L (FW 1418B05SIM800L24)

#include <SoftwareSerial.h>
#include <Wire.h>

#define DEVBAUD  9600
#define SERBAUD 38400
#define FW "FIRMWARE 2021-03-18/C"
#define MINDATETIME  777550000
#define MAXDATETIME 4294967295

#define BTNSEND    0
#define BTNMEASURE 0
#define CONRX      3
#define SIMRESET   4
#define MULTIPLEX  5
#define GPSRX      6
#define GPSTX      7
#define FLASHSDA   8
#define FLASHSCL   9
#define SENSOR6   10
#define SENSOR5   11
#define SENSOR4   12
#define SENSOR3   13
#define CONTX     14
#define SENSOR2   15
#define SENSOR1   16

#ifndef CYCLES
#define CYCLES 1024                                        //Measures stored in flash memory
#endif

#ifndef PRIMARY
#define PRIMARY 26                                         //Number of measures sent (out of all CYCLES)
#endif

#ifndef SECONDARY
#define SECONDARY 0                                        //Another number of measures sent, however, only every STEP-th measure is sent 
#endif

#ifndef TERTIARY
#define TERTIARY 67                                        //Number of measures sent since the last successful cycle
#endif

#ifndef STEP
#define STEP 4                                             //Step used by SECONDARY
#endif

#define PACKET 13                                          //Size of one measure in bytes
#define MPACKET 16                                         //Size of one measure in flash memory in bytes
#define MSHIFT 0                                           //Flash memory shift in bytes

#ifndef MODULO
#define MODULO 4                                           //Number of measures followed by data sending
#endif

#ifndef SLEEPSAT
#define SLEEPSAT 8                                         //Number of satellites to enable GPS sleeping
#endif

#ifndef BEFORE
#define BEFORE 12                                          //Wake Ublox before measure [s]
#endif

#ifndef INTERVAL
#define INTERVAL 61                                        //Interval between measures [s]
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
#define SERVERPATH "/script/measure_add_gps.php"           //Processing script path
#endif

#ifndef APIKEY
#define APIKEY ""                                          //API write key
#endif

#define FLAGS_ALARM_PAST 0                                 //Position of flag bit - alarm was activated in the past
#define FLAGS_ALARM_NOW 1                                  //Position of flag bit - alarm was activated right now

#define BTSSAT 100
#define RESET_DURATION 50

//Bit manipulation macros
#define BIT_SET(byte,nbit)   byte |=  (1 << nbit)
#define BIT_CLEAR(byte,nbit) byte &= ~(1 << nbit)
#define BIT_FLIP(byte,nbit)  byte ^=  (1 << nbit)
#define BIT_CHECK(byte,nbit) byte &   (1 << nbit)

const char server[] PROGMEM = SERVER;                      //Processing server
const char key[]    PROGMEM = APIKEY;                      //API write key
const int  address  PROGMEM = 0x50;                        //Memory chip address

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
const char c13[]    PROGMEM = " ";
const char c14[]    PROGMEM = "/";
const char c15[]    PROGMEM = "Voltage: ";
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
const char c41[]    PROGMEM = " last: ";
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
const char c58[]    PROGMEM = "+CBC: 0,XX,";
const char c59[]    PROGMEM = "c4a.com";
const char c60[]    PROGMEM = "GSMLOC: 0,XXXX/XX/XX,XX:XX:";
const char c61[]    PROGMEM = "+CLBS: 0,XX.XXXXXX,XX.XXXXXX,";
const char c62[]    PROGMEM = "+CBC: 0,X,";
const char c63[]    PROGMEM = "+CBC: 0,XXX,";
const char c64[]    PROGMEM = "+CIPSEND: ";
const char c65[]    PROGMEM = "Transmitted: ";
const char c66[]    PROGMEM = "AT+CIPACK=?";
const char c67[]    PROGMEM = "^";
const char c68[]    PROGMEM = " ignored";
const char c69[]    PROGMEM = ":";
const char c70[]    PROGMEM = ".";
const char c71[]    PROGMEM = " date: ";
const char c72[]    PROGMEM = "Last successful sent: ";

char sensor[] = SENSOR;
const unsigned char UBX_HEADER[] = { 0xB5, 0x62 };
const unsigned long wait = INTERVAL;
char memory[13];
char buffer[64];
char extra[32];
char temp[10];
unsigned int voltage;
unsigned int transmitted;
bool fail = false;
bool modify = false;
bool success = false;
bool successSent = false;
bool successClose = false;
bool signal = false;
bool btnMeasureLast = false;
bool btnSendLast = false;
byte analyze = 0;
bool analyzed = false;
byte flags;
int current = 0;
int last = CYCLES - 1;
int one;
int two;
int packets;
bool firstrun = true;
byte tact = 0;
byte iterator = 0;
long lastlat = 0;
long lastlon = 0;
unsigned long timer;
unsigned int limit = 10000;
char delim[2];

SoftwareSerial console(CONRX, CONTX);

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

  while ( Serial.available() ) {
    byte c = Serial.read();
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
    Serial.write(data[i]);
    delay(5);
  }

  load((char*)&c24); console.print(buffer); //Ublox sleep:
  console.println(INTERVAL - BEFORE);
}

void sweetDreams(unsigned long int period)
{
  load((char*)&c25); console.print(buffer); //Attiny sleep:
  console.println(period);
  delay(period * 1000);
}

void setup()
{
  memset(&memory, 255, PACKET);
  delim[0] = 0x1a;
  delim[1] = 0x00;
  if (BTNMEASURE) pinMode(BTNMEASURE, INPUT_PULLUP);
  if (BTNSEND) pinMode(BTNSEND, INPUT_PULLUP);
  pinMode(SENSOR1, INPUT_PULLUP);
  pinMode(SENSOR2, INPUT_PULLUP);
  pinMode(SENSOR3, INPUT_PULLUP);
  pinMode(SENSOR4, INPUT_PULLUP);
  pinMode(SENSOR5, INPUT_PULLUP);
  pinMode(SENSOR6, INPUT_PULLUP);
  pinMode(MULTIPLEX, OUTPUT);
  modemReset();
  connectUblox();
  loadSensor();
  btnMeasureLast = true;
  btnSendLast = true;
  Wire.begin();
  console.begin(SERBAUD);
  console.stopListening();
  console.println();
  console.println(FW);
  delay(1000);
  Serial.begin(DEVBAUD);
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
        load((char*)&c39); console.println(buffer); //Measure button pressed
        Serial.write(0xFF);
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
        BIT_SET(flags, FLAGS_ALARM_PAST);
        BIT_SET(flags, FLAGS_ALARM_NOW);
        load((char*)&c40); console.println(buffer); //Send button pressed
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
  console.println();
  console.print(sensor);
  load((char*)&c13); console.print(buffer);
  console.print(iterator);
  load((char*)&c14); console.print(buffer);
  console.println(MODULO);
  if (iterator < 0 || iterator >= MODULO) iterator = 0;

  readUblox(3000);
  if (pvt.fixType < 2) {
    load((char*)&c38); console.println(buffer); //No GPS
    readUblox(3000);
  }
  if (pvt.fixType < 2) {
    load((char*)&c38); console.println(buffer); //No GPS
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
      console.println(buffer);
    }
  }

  //Send results
  if (iterator % MODULO == 0) {
    connectModem();
    loadSensor();
    gprs();
    connectUblox();
  }
}

void updateFlashMemory(long lon, long lat)
{
  unsigned long dt_time = pvt.hour;
  dt_time *= 3600;
  dt_time += pvt.minute * 60;
  dt_time += pvt.second;
  unsigned long dt_date = pvt.year - 2000;
  dt_date *= 372;
  dt_date += (pvt.month - 1) * 31;
  dt_date += (pvt.day - 1);
  unsigned long dt = dt_date * 100000 + dt_time;

  if (current < 0 || current >= CYCLES) current = 0;

  memcpy(&memory[0], &dt, 4);
  memcpy(&memory[4], &pvt.numSV, 1);
  memcpy(&memory[5], &lon, 4);
  memcpy(&memory[9], &lat, 4);

  writeMemoryPacket(current);
  //displayMemoryPacket(current);

  load((char*)&c26); console.print(buffer); //Cycle:
  console.print(current);
  load((char*)&c41); console.print(buffer); //last:
  if (firstrun) {
    load((char*)&c67); //FR/
    console.print(buffer);
  }
  console.print(last);
  load((char*)&c27); console.print(buffer); //lat:
  console.print(lat);
  load((char*)&c28); console.print(buffer); //lng:
  console.print(lon);
  load((char*)&c52); console.print(buffer); //time:
  console.print(dt);
  load((char*)&c29); console.print(buffer); //sat:
  console.println(pvt.numSV);

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
  console.println();
  load((char*)&c0);  Serial.println(buffer); delay(500);

  load((char*)&c0); communicate(); //AT
  load((char*)&c0); communicate(); //AT
  load((char*)&c53); communicate(); //AT-ver
  if (!success) fail = true;

  if (!fail) {
    for (byte i = 0; i < 7; i++)
    {
      load((char*)&c2); analyze = 4; communicate(); //AT+CBC
      if (analyzed) break;
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
    if (!success) fail = true;
  }

  if (!fail) {
    load((char*)&c8); communicate(); //AT+CIPQSEND=1
    calculatePackets();
    sprintf(buffer, "AT+CIPSEND=%d", packets * PACKET + 158 + strlen(SERVER) + strlen(SERVERPATH) + strlen(sensor) + strlen(APIKEY));
    communicate(); //AT+CIPSEND=size
  }

  if (!fail) {
    delay(2000);
    trasmit();
    delay(2000);
  }

  load((char*)&c32); analyze = 5; communicate(); //AT+CIPSEND?
  load((char*)&c66); communicate(); //AT+CIPACK=?
  successSent = success;
  load((char*)&c33); communicate(); //AT+CIPCLOSE
  successClose = success;
  if (successSent && successClose) {
    last = two;
    load((char*)&c72); console.print(buffer); //Last successful sent:
    console.println(last);
    console.println();
  }
  load((char*)&c10); communicate(); //AT+CIPSHUT
  load((char*)&c11); communicate(); //AT+CSCLK=2
  if (!success) fail = true;

  if (fail)
  {
    fail = false;
    load((char*)&c31); console.println(buffer); //Modem reset
    modemReset();
  }
}

void modemReset() {
  pinMode(SIMRESET, OUTPUT);
  digitalWrite(SIMRESET, LOW);
  delay(RESET_DURATION);
  pinMode(SIMRESET, INPUT_PULLUP);
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
  console.print(">>> ");
  console.print(buffer);
  Serial.print(buffer);

  if (modify)
  {
    load((char*)&server); console.print(buffer); Serial.print(buffer);
    load((char*)&c36); console.print(buffer); Serial.print(buffer);
  }

  console.println();
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

        i++;
        if (i > 62) i = 1;
      }
      delay(10);
    }

    buffer[i] = delim[1];
    console.write(buffer);

    if (analyze == 1) BTSServer();
    if (analyze == 2) BTSDateTime();
    if (analyze == 3) BTSLocation();
    if (analyze == 4) Voltage();
    //if (analyze == 5) TransmissionStatus();
  }

  analyze = 0;
  limit = 10000;
  console.println();
}

void trasmit()
{
  load((char*)&c16); Serial.print(buffer); //POST /script/measure_add_gps2.php HTTP/1.1
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c18); Serial.print(buffer); //Host:
  load((char*)&server); Serial.print(buffer); //SERVER
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c19); Serial.print(buffer); //User-Agent: ArduinoSIM800
  Serial.print(sensor);
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c20); Serial.print(buffer); //Connection: close
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c21); Serial.print(buffer); //Content-Type: application/x-www-form-urlencoded
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c22); Serial.print(buffer); //Content-Length:
  Serial.print(packets * PACKET + strlen(APIKEY) + strlen(sensor) + 4 + 2 + 4); // Content + Key + Sensor + Voltage + Flags + Delimiters
  load((char*)&c17); Serial.print(buffer);
  load((char*)&c17); Serial.print(buffer);

  load((char*)&key); Serial.print(buffer); //KEY
  load((char*)&c23); Serial.print(buffer); //Separator |
  Serial.print(sensor);
  load((char*)&c23); Serial.print(buffer); //Separator |
  Serial.print(voltage);
  load((char*)&c23); Serial.print(buffer); //Separator |
  Serial.write(flags); BIT_CLEAR(flags, FLAGS_ALARM_NOW);
  Serial.print(delim[1]);
  load((char*)&c23); Serial.print(buffer); //Separator |
  console.print(buffer);

  transmitCommands(false);

  tact++;
  if (tact >= STEP) tact = 0;

  console.println();
  load((char*)&c17);
  for (int i = 0; i < 2; i++) Serial.print(buffer); //LINE
}

void calculatePackets() {
  packets = 0;
  bool run = firstrun;
  transmitCommands(true);
  firstrun = run;
}

void transmitCommands(bool simulation) {
  for (int i = CYCLES - 1; i >= CYCLES - PRIMARY; i--) one = transmitPacket(i, current, false, simulation);
  for (int i = CYCLES - PRIMARY - 1 - tact; i >= CYCLES - PRIMARY - SECONDARY * STEP; i -= STEP) transmitPacket(i, current, false, simulation);
  int i = 1; while (i != (TERTIARY + 1) && (((last + i) % CYCLES) != one || firstrun)) {
    two = transmitPacket(i, last, true, simulation);
    i++;
  }
}

int transmitPacket(int i, int base, bool testrun, bool simulation)
{
  int which = (i + base) % CYCLES;
  if (testrun && (which == CYCLES - 1)) firstrun = false;
  if (!simulation) {
    readMemoryPacket(which);
    for (byte j = 0; j < PACKET; j++) Serial.write(memory[j]);
    console.print(which);
    console.print(buffer);
  } else {
    packets++;
  }
  return which;
}

void Voltage()
{
  voltage = 0;
  analyzed = false;
  for (byte i = 0; i <= 30; i++)
  {
    strcpy_P(extra, c58); //"+CBC: 0,XX,"
    if (search(i)) voltage = convert(i + 11, 4);

    if (!voltage) {
      strcpy_P(extra, c62); //"+CBC: 0,X,"
      if (search(i)) voltage = convert(i + 10, 4);
    }

    if (!voltage) {
      strcpy_P(extra, c63); //"+CBC: 0,XXX,"
      if (search(i)) voltage = convert(i + 12, 4);
    }

    if (voltage)
    {
      console.println();
      load((char*)&c15); console.print(buffer); //Voltage:
      console.print(voltage);
      console.println();

      analyzed = true;
      break;
    }
  }
}

void BTSServer()
{
  for (byte i = 0; i <= 30; i++)
  {
    strcpy_P(extra, c59);
    if (search(i))
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
    strcpy_P(extra, c60);
    if (search(i))
    {
      pvt.year   = convert(i + 10, 4);
      pvt.month  = convert(i + 15, 2);
      pvt.day    = convert(i + 18, 2);
      pvt.hour   = convert(i + 21, 2);
      pvt.minute = convert(i + 24, 2);
      pvt.second = convert(i + 27, 2);
      pvt.numSV  = BTSSAT;

      console.println();
      load((char*)&c57); console.print(buffer); //Timestamp:
      console.print(pvt.day);
      console.print(".");
      console.print(pvt.month);
      console.print(".");
      console.print(pvt.year);
      console.print(" ");
      console.print(pvt.hour);
      console.print(":");
      console.print(pvt.minute);
      console.print(":");
      console.print(pvt.second);
      console.println();

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
    strcpy_P(extra, c61);
    if (search(i))
    {
      console.println();
      updateFlashMemory(convert(i + 9, 9) * 10, convert(i + 19, 9) * 10);
      memcpy(&buffer, 0, sizeof(buffer));

      analyzed = true;
      break;
    }
  }
}

void TransmissionStatus()
{
  transmitted = 0;
  analyzed = false;
  for (byte i = 0; i <= 30; i++)
  {
    strcpy_P(extra, c64); //+CIPSEND:
    if (search(i)) transmitted = convert(i + 10, 4);

    if (transmitted)
    {
      console.println();
      load((char*)&c65); console.print(buffer); //Transmitted:
      console.print(transmitted);
      console.println();

      analyzed = true;
      break;
    }
  }
}

bool search(byte from)
{
  for (byte i = 0; i < strlen(extra); i++)
  {
    if (extra[i] != 'X' && buffer[from + i] != extra[i]) return false;
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

word memoryAddress(int where)
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

void writeMemoryPacket(int where)
{
  Wire.beginTransmission(address);
  Wire.write(highAddressByte(memoryAddress(where)));
  Wire.write(lowAddressByte(memoryAddress(where)));

  for (byte i = 0; i < PACKET; i++) {
    Wire.write(memory[i]);
  }

  Wire.endTransmission();
  delay(10);
}

void readMemoryPacket(int where)
{
  Wire.beginTransmission(address);
  Wire.write(highAddressByte(memoryAddress(where)));
  Wire.write(lowAddressByte(memoryAddress(where)));
  Wire.endTransmission();

  Wire.requestFrom(address, PACKET);

  for (byte i = 0; i < PACKET; i++) {
    if (!Wire.available()) {
      delay(10);
    }
    memory[i] = Wire.read();
  }
}

unsigned long displayMemoryPacket(int where)
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

  unsigned long day = dt / 100000;
  unsigned long sec = dt - day * 100000;

  unsigned long year = day / 372;
  day = day - year * 372;
  unsigned long month = day / 31;
  day = day - month * 31;

  unsigned long hour = sec / 3600;
  sec = sec - hour * 3600;
  unsigned long min = sec / 60;
  sec = sec - min * 60;

  year += 2000;
  month++;
  day++;

  load((char*)&c26); console.print(buffer); //Cycle:
  console.print(where);
  load((char*)&c27); console.print(buffer); //lat:
  console.print(lat);
  load((char*)&c28); console.print(buffer); //lng:
  console.print(lon);
  load((char*)&c71); console.print(buffer); //date:
  load((char*)&c70);
  if (day < 10) printZero();
  console.print(day);
  console.print(buffer);
  if (month < 10) printZero();
  console.print(month);
  console.print(buffer);
  console.print(year);
  load((char*)&c52); console.print(buffer); //time:
  load((char*)&c69);
  if (hour < 10) printZero();
  console.print(hour);
  console.print(buffer);
  if (min < 10) printZero();
  console.print(min);
  console.print(buffer);
  if (sec < 10) printZero();
  console.print(sec);
  load((char*)&c29); console.print(buffer); //sat:
  if (sat < 10) printSpace();
  console.print(sat);

  if (dt < MINDATETIME) {
    dt = MAXDATETIME - 1;
    load((char*)&c68); console.print(buffer); //ignored
  }

  console.println();
  return dt;
}

void printZero() {
  console.print("0");
}

void printSpace() {
  console.print(" ");
}

void displayAllMemoryPackets() {
  for (int i = 0; i < CYCLES; i++) displayMemoryPacket(i);
}

void updateCurrent() {
  unsigned long dt;
  unsigned long old = MAXDATETIME;

  load((char*)&c49); console.println(buffer); //Scanning flash memory

  for (int i = 0; i < CYCLES; i++)
  {
    dt = displayMemoryPacket(i);
    if (dt == MAXDATETIME)
    {
      current = i;
      break;
    }
    if (dt < old)
    {
      current = i;
      old = dt;
    }
  }

  if (current < 0 || current >= CYCLES) current = 0;

  load((char*)&c50); console.print(buffer); //Next cycle:
  console.println(current);
}

void connectUblox() {
  digitalWrite(MULTIPLEX, LOW);
}

void connectModem() {
  digitalWrite(MULTIPLEX, HIGH);
}

byte identify() {
  byte sensor = 0;
  if (digitalRead(SENSOR6) == LOW) sensor += 1;
  if (digitalRead(SENSOR5) == LOW) sensor += 2;
  if (digitalRead(SENSOR4) == LOW) sensor += 4;
  if (digitalRead(SENSOR3) == LOW) sensor += 8;
  if (digitalRead(SENSOR2) == LOW) sensor += 16;
  if (digitalRead(SENSOR1) == LOW) sensor += 32;
  return sensor;
}

void loadSensor() {
  if (SENSOR == "") {
    byte id = identify();
    if (id > 9) sprintf(sensor, "RTC%d", id); else sprintf(sensor, "RTC0%d", id);
  }
}
