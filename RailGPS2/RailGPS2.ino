//Railtour GPS data gatherer II
//Vitezslav Dostal | started 24.8.2018
#include <SoftwareSerial.h>
#include <WiFiManager.h>
#include <FS.h>
ADC_MODE(ADC_VCC);

static const int RXPin = 5, TXPin = 4;
SoftwareSerial ublox(RXPin, TXPin);

      String sensor          = "";   //Sensor indentification
const char*  server          = "";   //Processing server
      String key             = "";   //API write key

#define cycles 40
#define packet 13
#define last 520
#define flashSize 521
#define modulo 10
#define interval 10

char flash[flashSize];
const unsigned char UBX_HEADER[] = { 0xB5, 0x62 };

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
      if ( (fpos-2) < payloadSize )
        ((unsigned char*)(&pvt))[fpos-2] = c;

      fpos++;

      if ( fpos == (payloadSize+2) ) {
        calcChecksum(checksum);
      }
      else if ( fpos == (payloadSize+3) ) {
        if ( c != checksum[0] )
          fpos = 0;
      }
      else if ( fpos == (payloadSize+4) ) {
        fpos = 0;
        if ( c == checksum[1] ) {
          return true;
        }
      }
      else if ( fpos > (payloadSize+4) ) {
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
  pinMode(13, OUTPUT);
  delay(ms);
  pinMode(13, INPUT);  
}

void setup()
{
  WiFi.mode(WIFI_OFF);
  memset(&flash, '\0', flashSize);

  Serial.begin(115200);
  ublox.begin(9600);
}

void loop()
{  
  float lng = 0;
  float lat = 0;
  int sat = 0;

  Serial.println();
  Serial.println("START");

  readUblox(3000);
  if (pvt.fixType < 2) {readUblox(3000); Serial.println("FIX-1");}
  if (pvt.fixType < 2) {readUblox(3000); Serial.println("FIX-2");}
  if (pvt.fixType > 1)
  {    
    SPIFFS.begin();
    //Serial.println(flash);
    loadFlashFile();
    //Serial.println(flash);    
    updateFlashMemory();
    //Serial.println(flash);
    saveFlashFile();
      
    if ((flash[last]-48) % modulo == 4)
    {
      WiFiManager wifiManager;
      wifiManager.setTimeout(300);
      wifiManager.autoConnect("RailGPS");        
      postDataWifi();
      WiFi.mode(WIFI_OFF);      
      beep(100);
    }
    else
    {
      beep(1);
    }
  }
  else
  {
      /*SPIFFS.begin();
      loadFlashFile();
      
      WiFiManager wifiManager;
      wifiManager.setTimeout(300);
      wifiManager.autoConnect("RailGPS");        
      postDataWifi();
      WiFi.mode(WIFI_OFF);*/
  }

  Serial.println("SLEEP");
  ESP.deepSleep(interval * 1000000);
}

void postDataWifi()
{
  WiFiClient client;
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
    request += (params.length() + flashSize);
    request += "\r\n\r\n";
    request += params;
    client.print(request);
    client.print(flash);
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
  //memset(&flash, '-', flashSize);
  int pointer = (flash[last]-48) * packet;

  unsigned long dt_time =
      pvt.second
    + pvt.minute * 60
    + pvt.hour   * 3600;
  unsigned long dt_date =
      pvt.day
    + pvt.month       * 31
    + (pvt.year-2000) * 372;
  unsigned long dt = dt_date*100000 + dt_time;
  
  memcpy(&flash[pointer + 0], &dt, 4);
  memcpy(&flash[pointer + 4], &pvt.numSV, 1);
  memcpy(&flash[pointer + 5], &pvt.lon, 4);
  memcpy(&flash[pointer + 9], &pvt.lat, 4);

  flash[last]++;
  if (flash[last] < 48 || flash[last] > 48 + cycles-1) flash[last] = 48;  
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

  f.print(flash);
  f.close();
}

void loadFlashFile()
{
  String data;
  
  File f = SPIFFS.open("/log.txt", "r");
  if (!f)
  {
    Serial.println("Reading from log.txt failed.");
  }
  else
  {
    data = f.readBytes(flash, flashSize);
  }
  f.close();
}
