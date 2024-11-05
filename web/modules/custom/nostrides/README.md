# Nostrides

Proof of concept of a client which integrates [NIP-113 activity events](https://github.com/nostr-protocol/nips/pull/1423).
This NIP contains two event kinds:

1. `30100` (parameterized replaceable activity event)
2. `30101` (summary of an activity event, referenced by the d-tag)

This module provides a route at `/nostrides` to display a list of the latest events from several relays.

This module provides a route to `/nostrides/{event_id}` where a single event with kind `30100` is displayed extended with summary event info from a event kind `30101`.

A Khatru powered relay is used as a default relay to broadcast these events: `wss://khatru.nostrver.se`.

### TODO's

- [ ] Add kinds `30100` and `30101` to allowed kinds on https://khatru.nostrver.se
- [ ] Add route + form to create an activity event
- [ ] Extract data from uploaded GPX file
- [ ] Show route on a geo map of an activity (data is extracted from GPX file)

### NIP-XX for recorded GPS activities (GPX data)

Activity events are often recorded and saved in a GPX file (a light-weight XML data format). As an extension of NIP-113, this NIP proposes a solution to transmit that data in events with the following structure.

[GPX 1.1 Schema Documentation](https://www.topografix.com/GPX/1/1/)

### Track Segment

> A Track Segment holds a list of Track Points which are logically connected in order. To represent a single GPS track where GPS reception was lost, or the GPS receiver was turned off, start a new Track Segment for each continuous span of track data.

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

### Trackpoint

Every trackpoint (inside a track segment) is an event and is referenced to the track segment event kind 34300 with a d-tag.
> A Track Point holds the coordinates, elevation, timestamp, and metadata for a single point in a track.

```json
{
  "id": "",
  "kind": 34301,
  "pubkey": "",
  "content": "could be a geohash",
  "tags": [
    "d", "reference",
    "lat", "latitude",
    "lon", "longitude",
    "g", "geohash",
    "ele", "elevatiion height",
    "time", "unix timestamp"
  ],
  "sig": ""
}
```

Track point extensions:
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

The [data formated](https://www.topografix.com/gpx.asp) for the GPX file for a (by client Strava) recorded activity event is defined as follows:

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
