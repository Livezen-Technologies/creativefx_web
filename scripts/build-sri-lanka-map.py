#!/usr/bin/env python3
"""Turn Natural Earth's Sri Lanka polygons into an SVG path for the site.

Source: Natural Earth 1:10m Admin 0 Countries, which is public domain —
https://www.naturalearthdata.com/about/terms-of-use/ — so the geometry can be
committed and served directly.

    curl -sSLo /tmp/ne10.json https://raw.githubusercontent.com/nvkelso/\
natural-earth-vector/master/geojson/ne_10m_admin_0_countries.geojson
    python3 scripts/build-sri-lanka-map.py /tmp/ne10.json

Writes resources/data/sri-lanka.json: the island as SVG path data in a
1000-unit-wide viewBox, plus the bounding box the site needs to place a pin
from a latitude and longitude.

Longitude is scaled by cos(mid-latitude) so the island keeps its true
proportions — at 7.9°N that is a 1% correction, small but free.
"""

import json
import math
import sys
from pathlib import Path

WIDTH = 1000.0
# Ramer-Douglas-Peucker tolerance in degrees. 0.004° is ~440 m, well under one
# screen pixel at the size this renders, and takes the coastline from 757
# points to something a page can carry without thinking about it.
TOLERANCE = 0.004
# Rings smaller than this are sandbars and reefs, not islands worth drawing.
MIN_RING_POINTS = 12


def perpendicular_distance(pt, start, end):
    (x, y), (x1, y1), (x2, y2) = pt, start, end
    dx, dy = x2 - x1, y2 - y1
    if dx == 0 and dy == 0:
        return math.hypot(x - x1, y - y1)
    return abs(dy * x - dx * y + x2 * y1 - y2 * x1) / math.hypot(dx, dy)


def simplify(points, tolerance):
    if len(points) < 3:
        return points
    worst, index = 0.0, 0
    for i in range(1, len(points) - 1):
        d = perpendicular_distance(points[i], points[0], points[-1])
        if d > worst:
            worst, index = d, i
    if worst <= tolerance:
        return [points[0], points[-1]]
    return simplify(points[:index + 1], tolerance)[:-1] + simplify(points[index:], tolerance)


def main(source: str) -> None:
    data = json.loads(Path(source).read_text())

    feature = next(
        f for f in data['features']
        if f['properties'].get('ADM0_A3') == 'LKA'
    )
    geometry = feature['geometry']
    rings = (geometry['coordinates'] if geometry['type'] == 'Polygon'
             else [ring for polygon in geometry['coordinates'] for ring in polygon])

    rings = [r for r in rings if len(r) >= MIN_RING_POINTS]
    rings.sort(key=len, reverse=True)

    lons = [c[0] for r in rings for c in r]
    lats = [c[1] for r in rings for c in r]
    west, east = min(lons), max(lons)
    south, north = min(lats), max(lats)

    scale = WIDTH / (east - west)
    lon_squeeze = math.cos(math.radians((north + south) / 2))
    height = (north - south) * scale / lon_squeeze

    def project(lon, lat):
        return ((lon - west) * scale,
                (north - lat) * scale / lon_squeeze)

    paths = []
    kept = 0
    for ring in rings:
        simplified = simplify([tuple(c[:2]) for c in ring], TOLERANCE)
        kept += len(simplified)
        pts = [project(lon, lat) for lon, lat in simplified]
        d = 'M' + 'L'.join(f'{x:.1f} {y:.1f}' for x, y in pts) + 'Z'
        paths.append(d)

    out = {
        'source': 'Natural Earth 1:10m Admin 0 Countries (public domain)',
        'viewBox': f'0 0 {WIDTH:.0f} {height:.1f}',
        'width': WIDTH,
        'height': round(height, 1),
        # Everything the front end needs to turn a coordinate into a point.
        'bounds': {'west': west, 'east': east, 'south': south, 'north': north},
        'lonSqueeze': lon_squeeze,
        'paths': paths,
    }

    target = Path('resources/data/sri-lanka.json')
    target.write_text(json.dumps(out, separators=(',', ':')))
    print(f'{target}: {len(paths)} rings, {kept} points '
          f'(from {sum(len(r) for r in rings)}), viewBox {out["viewBox"]}, '
          f'{target.stat().st_size / 1024:.1f} KB')


if __name__ == '__main__':
    main(sys.argv[1] if len(sys.argv) > 1 else '/tmp/ne10.json')
