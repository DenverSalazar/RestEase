var json_border_1 = {
  "type": "FeatureCollection",
  "name": "border_1",
  "crs": {
    "type": "name",
    "properties": {
      "name": "urn:ogc:def:crs:OGC:1.3:CRS84"
    }
  },
  "features": [
    {
      "type": "Feature",
      "properties": { "borderID": "1" },
      "geometry": {
        "type": "MultiLineString",
        "coordinates": [
          [
            [121.223430, 13.883150], // left
            [121.223430, 13.883600], // left
            [121.224250, 13.883600], // right (slightly reduced)
            [121.224250, 13.883150], // right (slightly reduced)
            [121.223430, 13.883150]
          ]
        ]
      }
    }
  ]
}
