//Railtour GPS data gatherer II
//Vitezslav Dostal | started 24.8.2018
//Hardware required: UbloxNeo & SIM800L
#include <SoftwareSerial.h>
#include <ESP8266WiFi.h>
#include <EEPROM.h>
#include <FS.h>
ADC_MODE(ADC_VCC);
#define gpsbaud 9600
#define beeper 0
#define gpstx 4
#define gpsrx 5
#define simbaud 38400
#define simtx 12
#define simrx 13
#define simreset 14

char sensor[20]          = "<from-eeprom>";           //Sensor indentification
char server[40]          = "<from-eeprom>";           //Processing server
char key[20]             = "<from-eeprom>";           //API write key
char wifiSSID[20]        = "<from-eeprom>";           //Wireless network SSID
char wifiPasswd[20]      = "<from-eeprom>";           //Wireless network password

#define cycles 240                                    //Measures stored in flash memory
#define packet 13                                     //Size of one measure in bytes
#define last 3120                                     //cycles * packet
#define flashSize 3121                                //cycles * pcaket + 1
#define modulo 20                                     //number of measures when sending is triggered
#define message 40                                    //number of measures in one sent message
#define bootstrap 0                                   //initial programming of GPS module
#define reportwifi 0                                  //report measures via WiFi
#define reportgprs 1                                  //report measures via GPRS
#define sleep 20                                      //sleep interval for GPS module [s]
#define interval 30                                   //interval between measures [s]
#define shortinterval 10                              //interval between measures after GPRS sent [s]

const char messages[] = {0, 0, 0, 0, 1, 1};           //map of chunks sent in one message

SoftwareSerial ublox(gpsrx, gpstx);
SoftwareSerial sim800(simrx, simtx);
uint8_t flash[flashSize];
String reply;
const unsigned char terminal[2] = { 0x1a, 0x00 };
const unsigned char UBX_HEADER[] = { 0xB5, 0x62 };

const char UBLOX_INIT[] PROGMEM = {
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0xF0, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01, 0x00, 0x24,   //GxGGA off
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0xF0, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01, 0x01, 0x2B,   //GxGLL off
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0xF0, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01, 0x02, 0x32,   //GxGSA off
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0xF0, 0x03, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01, 0x03, 0x39,   //GxGSV off
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0xF0, 0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01, 0x04, 0x40,   //GxRMC off
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0xF0, 0x05, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01, 0x05, 0x47,   //GxVTG off
  //0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0x01, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x17, 0xDC, //NAV-PVT off
  //0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0x01, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x12, 0xB9, //NAV-POSLLH off
  //0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0x01, 0x03, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x13, 0xC0, //NAV-STATUS off
  0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0x01, 0x07, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x18, 0xE1,   //NAV-PVT on
  //0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0x01, 0x02, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x13, 0xBE, //NAV-POSLLH on
  //0xB5, 0x62, 0x06, 0x01, 0x08, 0x00, 0x01, 0x03, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x14, 0xC5, //NAV-STATUS on
  //0xB5, 0x62, 0x06, 0x08, 0x06, 0x00, 0x64, 0x00, 0x01, 0x00, 0x01, 0x00, 0x7A, 0x12,             //Refresh 10Hz
  //0xB5, 0x62, 0x06, 0x08, 0x06, 0x00, 0xC8, 0x00, 0x01, 0x00, 0x01, 0x00, 0xDE, 0x6A,             //Refresh 5Hz
  //0xB5, 0x62, 0x06, 0x08, 0x06, 0x00, 0xE8, 0x03, 0x01, 0x00, 0x01, 0x00, 0x01, 0x39,             //Refresh 1Hz
};

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
    yield();
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

static void readUblox(unsigned long ms)
{
  unsigned long start = millis();
  do
  {
    if (processGPS()) return;
    yield;
  }
  while (millis() - start < ms);
}

void beep(int ms)
{
  pinMode(beeper, OUTPUT);
  delay(ms);
  pinMode(beeper, INPUT);
}

void resetModem()
{
  pinMode(simreset, OUTPUT);
  digitalWrite(simreset, LOW);
  delay(50);
  pinMode(simreset, INPUT_PULLUP);
}

void bootstrapUblox()
{
  for (int i = 0; i < sizeof(UBLOX_INIT); i++)
  {
    ublox.write(pgm_read_byte(UBLOX_INIT + i));
    delay(5);
  }
}

void sleepUblox()
{
  //UBX-RXM-PMREQ
  unsigned char data[] = {0xB5, 0x62, 0x02, 0x41, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00};
  unsigned long num = sleep * 1000;
  memcpy(&data[6], &num, 4);
  setChecksum(data, sizeof(data));

  for (int i = 0; i < sizeof(data); i++)
  {
    ublox.write(data[i]);
    delay(5);
  }

  Serial.print("Ublox sleep: ");
  Serial.println(sleep);
}

void sweetDreams(int period)
{
  Serial.print("ESP sleep: ");
  Serial.println(period);
  ESP.deepSleep(period * 1000000);
}

void setup()
{
  WiFi.mode(WIFI_OFF);
  memset(&flash, 255, flashSize);
  Serial.begin(115200);
  readMemory();
  ublox.begin(gpsbaud);
  sim800.begin(simbaud);
}

void loop()
{
  Serial.println();
  Serial.println("====================");

  readUblox(3000);
  if (pvt.fixType < 2) {
    readUblox(3000);
    Serial.println("Acquiring location...");
  }
  if (pvt.fixType < 2) {
    readUblox(3000);
    Serial.println("Acquiring location...");
  }
  if (pvt.fixType > 1)
  {
    SPIFFS.begin();
    loadFlashFile();
    updateFlashMemory();
    saveFlashFile();
    SPIFFS.end();

    if (sleep && pvt.numSV >= 10) sleepUblox();

    if ((flash[last]) % modulo == 1)
    {
      if (beeper) beep(100);
      if (reportwifi)
      {
        connectWifi();
        for (int i = 0; i < cycles / message; i++) if (messages[i]) postDataWifi(i);
        WiFi.mode(WIFI_OFF);
      }
      if (reportgprs)
      {
        if (connectGPRS()) postDataGPRS();
        disconnectGPRS();
        sweetDreams(shortinterval);
      }
    }
    else
    {
      if (beeper) beep(1);
    }
  }
  else
  {
    if (bootstrap) bootstrapUblox();
  }

  sweetDreams(interval);
}

bool connectGPRS()
{
  bool status;
  bool ciicr;

  processCommand("AT+IPR=" + String(simbaud), "", 5);
  processCommand("AT+CSCLK=0", "", 5);
  processCommand("AT+COPS?", "", 5);
  processCommand("AT+CGATT?", "", 5);
  processCommand("AT+CSQ", "", 5);

  status = processCommand("AT+CSTT=\"internet\",\"\",\"\"", "OK", 5);
  if (!status)
  {
    Serial.println("Modem reset");
    resetModem();
    delay(20000);
    processCommand("AT+IPR=" + String(simbaud), "", 5);
    processCommand("AT+CSCLK=0", "", 5);
    status = processCommand("AT+CSTT=\"internet\",\"\",\"\"", "OK", 5);
  }
  if (status) ciicr = processCommand("AT+CIICR", "OK", 5);
  if (status) delay(4000);
  if (status) status = processCommand("AT+CIPSTATUS", "IP GPRSACT", 5);
  if (ciicr) status = true;
  if (status) processCommand("AT+CIFSR", "", 5);
  if (status) processCommand("AT+CIPSTART=\"TCP\",\"" + String(server) + "\",80", "CONNECT OK", 5);
  if (status) delay(4000);
  return status;
}

void postDataGPRS()
{
  String params;
  params += String(key) + '|';
  params += String(sensor) + '|';
  params += String(ESP.getVcc()) + '|';

  String request;
  request += "POST /script/measure_add_gps2.php HTTP/1.1\r\n";
  request += "Host: " + String(server) + "\r\n";
  request += "User-Agent: ArduinoSIM800" + String(sensor) + "\r\n";
  request += "Connection: close\r\n";
  request += "Content-Type: application/x-www-form-urlencoded\r\n";
  request += "Content-Length: ";
  request += (params.length() + messagesInChunks() * message * packet);
  request += "\r\n\r\n";
  request += params;

  processCommand("AT", "OK", 1);
  processCommand("AT", "OK", 1);
  processCommand("AT+CIPQSEND=1", "", 1);
  processCommand("AT+CIPSEND=" + String(request.length() + messagesInChunks() * message * packet + 4), ">", 1);
  sim800.print(request);

  Serial.println("Sending data...");
  for (int cycle = 0; cycle < cycles ; cycle++)
  {
    if (!messages[cycle / message]) continue;
    yield();
    sim800.write((uint8_t*)flash + (((cycle + flash[last]) % cycles) * packet), packet);
  }
  sim800.println("\r\n\r\n");

  delay(3000);
  processCommand("AT+CIPSEND?", "", 1);
}

void disconnectGPRS()
{
  processCommand("AT+CIPCLOSE", "", 1);
  processCommand("AT+CIPSHUT", "", 1);
  processCommand("AT+CSCLK=2", "", 5);
}

void postDataWifi(int chunk)
{
  WiFiClient client;
  Serial.print("Chunk sent: ");
  Serial.println(chunk + 1);

  if (client.connect(server, 80))
  {
    String params;
    params += String(key) + '|';
    params += String(sensor) + '|';
    params += String(ESP.getVcc()) + '|';

    String request;
    request += "POST /script/measure_add_gps2.php HTTP/1.1\r\n";
    request += "Host: " + String(server) + "\r\n";
    request += "User-Agent: ArduinoWiFi" + String(sensor) + "\r\n";
    request += "Connection: close\r\n";
    request += "Content-Type: application/x-www-form-urlencoded\r\n";
    request += "Content-Length: ";
    request += (params.length() + message * packet);
    request += "\r\n\r\n";
    request += params;
    client.print(request);

    int from = (((flash[last]) + (chunk * message)) % cycles) * packet;
    int len = message * packet;
    if (from + len <= last)
    {
      client.write((uint8_t*)flash + from, len);
    }
    else
    {
      client.write((uint8_t*)flash + from, last - from);
      client.write((uint8_t*)flash, len - (last - from));
    }
    client.println("\r\n\r\n");
  }
  client.stop();
}

void formatFlashFile()
{
  SPIFFS.begin();
  SPIFFS.format();
}

void updateFlashMemory()
{
  unsigned long dt_time =
    pvt.second
    + pvt.minute * 60
    + pvt.hour   * 3600;
  unsigned long dt_date =
    pvt.day
    + pvt.month       * 31
    + (pvt.year - 2000) * 372;
  unsigned long dt = dt_date * 100000 + dt_time;

  if (flash[last] < 0 || flash[last] >= cycles) flash[last] = 0;
  int pointer = flash[last] * packet;

  memcpy(&flash[pointer + 0], &dt, 4);
  memcpy(&flash[pointer + 4], &pvt.numSV, 1);
  memcpy(&flash[pointer + 5], &pvt.lon, 4);
  memcpy(&flash[pointer + 9], &pvt.lat, 4);

  Serial.print("Cycle: ");
  Serial.print(flash[last]);
  Serial.print(" lat: ");
  Serial.print(pvt.lat);
  Serial.print(" lng: ");
  Serial.print(pvt.lon);
  Serial.print(" sat: ");
  Serial.println(pvt.numSV);

  flash[last]++;
  if (flash[last] < 0 || flash[last] >= cycles) flash[last] = 0;
}

void saveFlashFile()
{
  File f = SPIFFS.open("/log.txt", "w+");
  if (!f)
  {
    Serial.println("Writing to log.txt failed.");
    SPIFFS.format();
    File f = SPIFFS.open("/log.txt", "w+");
    if (!f) return;
  }
  f.write(flash, flashSize);
  f.close();
}

void loadFlashFile()
{
  File f = SPIFFS.open("/log.txt", "r");
  if (!f)
    Serial.println("Reading from log.txt failed.");
  else
    f.read(flash, flashSize);
  f.close();
}

template <class T> int EEPROM_readAnything(int ee, T& value)
{
  byte* p = (byte*)(void*)&value;
  unsigned int i;
  for (i = 0; i < sizeof(value); i++)
    *p++ = EEPROM.read(ee++);
  return i;
}

void readMemory()
{
  struct config {
    char  sensor[20];
    char  key[20];
    char  server[40];
    char  wifiSSID[20];
    char  wifiPasswd[20];
  } memory;

  EEPROM.begin(512);
  EEPROM_readAnything(360, memory);
  if (sensor[0]          == '<') strcpy(sensor, memory.sensor);
  if (key[0]             == '<') strcpy(key, memory.key);
  if (server[0]          == '<') strcpy(server, memory.server);
  if (wifiSSID[0]        == '<') strcpy(wifiSSID, memory.wifiSSID);
  if (wifiPasswd[0]      == '<') strcpy(wifiPasswd, memory.wifiPasswd);
}

void connectWifi()
{
  WiFi.begin(wifiSSID, wifiPasswd);
  while (WiFi.status() != WL_CONNECTED)
  {
    Serial.println("Connecting to WiFi...");
    delay(500);
  }
}

String readModem()
{
  String reply;
  unsigned long lastTime;

  if (sim800.available()) {
    lastTime = millis();
    while (millis() < lastTime + 100) {
      yield();
      while (sim800.available() > 0) {
        yield();
        char c = sim800.read();
        reply += c;
      }
    }
  }
  if (reply.length() > 0)
  {
    Serial.println(reply);
    return reply;
  }
  return "";
}

bool processCommand(String command, String expect, int timeout)
{
  reply = "";
  unsigned long processTime;

  Serial.print(">>> ");
  Serial.print(command);
  Serial.println(" >>>");
  sim800.println(command);

  processTime = millis();
  while (millis() < processTime + timeout * 1000)
  {
    yield();
    reply = readModem();
    if (reply.indexOf(expect) != -1) return true;
  }
  return false;
}

int messagesInChunks()
{
  int total = 0;
  for (int i = 0; i < cycles / message; i++) if (messages[i]) total += 1;
  return total;
}
