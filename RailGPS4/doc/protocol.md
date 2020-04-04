# Communication Protocol

The data packet is encoded as binary stream, which consists of several
sections. Sections are of variable length and are separated by `|` delimiter:

```
KEY | SENSOR | VOLTAGE | FLAGS | GPS-DATA
```

where:

* KEY (string) - used for simple authentication
* SENSOR (string) - identification of the sensor
* VOLTAGE (float encoded in string) - current voltage on device battery
* FLAGS (?) - bytes of bit flags, only one byte is used currently, bit layout:
  * bit 0 - alarm was activated in the past
  * bit 1 - alarm was activated right now - transmitted only once in message
    that is sent on alarm activation
  * bit 2 - unused
  * bit 3 - unused
  * bit 4 - unused
  * bit 5 - unused
  * bit 6 - unused
  * bit 7 - unused
* GPS-DATA (binary) - block of gps measurements, see following chapter

GPS measurements are stored as 13 byte blocks without any delimiter:

```
MESSUREMENT-1 MEASUREMENT-2 ... MEASUREMENT-n
```

where `n` have to be computed as `length(GPS-DATA) / 13` (13 is length of GPS measurement)

GPS measurement:

* 4 bytes (Integer) - proprietary timestamp (not unixtimestap yet, will change soon)
* 1 byte (Integer)  - satelites count
* 4 bytes (Float) - Longitude (little endian)
* 4 bytes (Float) - Latitude (little endian)
