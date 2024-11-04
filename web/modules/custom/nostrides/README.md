# Nostrides

Integrate NIP-113 activity events. This NIP contains two NIPs:

1. `30100` (parameterized replaceable activity event)
2. `30101` (summary of a activity event, referenced by the d-tag)


1. Create a route to /nostrides to display a list of the latest events from several relays.
2. Create a route to /nostrides/{event_id} where a single event with kind `30100` is displayed.

### NIP for GPX data with track segments (a recorded GPS activity)

Activity events are often recorded and saved in a GPX file (a light-weight XML data format). As an extension of NIP-113, this NIP proposes a solution to transmit that data in an event with the following structure:

```json
{
  "id": "",
  "kind": 34300,
  "pubkey": "",
  "content": "",
  "tags": [
    "name",
    "type",
    "started_at",
    "ended_at"
  ],
  "sig": ""
}
```

Every trackpoint (track segment) is an event and is referenced to a kind 34300 with a d-tag.
"A Track Point holds the coordinates, elevation, timestamp, and metadata for a single point in a track."

```json
{
  "id": "",
  "kind": 34301,
  "pubkey": "",
  "content": "could be a geohash",
  "tags": [
    "d",
    "lat",
    "lon",
    "ele",
    "time"
  ],
  "sig": ""
}
```

Track segment extensions:
- http://www.garmin.com/xmlschemas/GpxExtensions/v3
- https://www8.garmin.com/xmlschemas/TrackPointExtensionv2.xsd
  - atemp
  - wtemp
  - depth
  - hr
  - cad
  - speed
  - course
  - bearing

The [data formated](https://www.topografix.com/gpx.asp) for the GPX file for a recorded activity event is defined as follows:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<gpx xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd"
     creator="StravaGPX"
     version="1.1"
     xmlns="http://www.topografix.com/GPX/1/1"
     xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1"
     xmlns:gpxx="http://www.garmin.com/xmlschemas/GpxExtensions/v3">
  <metadata>
    <time>2024-10-10T06:59:54Z</time>
  </metadata>
  <trk>
    <name>Ochtendrit</name>
    <type>cycling</type>
    <trkseg>
      <trkpt lat="52.3659360" lon="4.9574370">
      <ele>-13.2</ele>
      <time>2024-10-10T06:59:54Z</time>
      <extensions>
        <gpxtpx:TrackPointExtension>
          <gpxtpx:atemp>9</gpxtpx:atemp>
        </gpxtpx:TrackPointExtension>
      </extensions>
    </trkseg>
    ...
    <trkpt lat="52.3858510" lon="4.8693930">
      <ele>-24.4</ele>
      <time>2024-10-10T07:23:00Z</time>
      <extensions>
        <gpxtpx:TrackPointExtension>
          <gpxtpx:atemp>10</gpxtpx:atemp>
        </gpxtpx:TrackPointExtension>
      </extensions>
    </trkpt>
  </trk>
</gpx>
```
