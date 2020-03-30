# RailGPS4

See [specification of communication protocol](doc/protocol.md)

# How to build and flash

## In platform.io:

Compile without any customizations

```
pio run
```

Compile with customizations:
```
export PLATFORMIO_BUILD_FLAGS='-DSERVER=\"some.domain.net\" -DSERVERPATH=\"/api\"'
pio run
```

Compile and flash to connected device:
```
pio run -t upload
```
