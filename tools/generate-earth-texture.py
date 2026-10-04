#!/usr/bin/env python3
"""Generate the locally bundled Earth texture used by the physics scene.

Country geometry comes from Natural Earth 1:110m (public domain). The generated
texture is deliberately schematic: it prioritizes legibility on a small WebGL
globe and gives Vietnam a distinct treatment.
"""

from __future__ import annotations

import io
import json
import urllib.request
from pathlib import Path

from PIL import Image, ImageDraw


SOURCE = "https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_admin_0_countries.geojson"
ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "assets" / "v2" / "images" / "earth-vietnam-map.webp"
WIDTH, HEIGHT = 2048, 1024


def point(coordinate: list[float]) -> tuple[float, float]:
    longitude, latitude = coordinate[:2]
    return ((longitude + 180.0) / 360.0 * WIDTH, (90.0 - latitude) / 180.0 * HEIGHT)


def polygons(geometry: dict) -> list[list[list[float]]]:
    if geometry["type"] == "Polygon":
        return geometry["coordinates"]
    if geometry["type"] == "MultiPolygon":
        return [ring for polygon in geometry["coordinates"] for ring in polygon]
    return []


def main() -> None:
    with urllib.request.urlopen(SOURCE, timeout=30) as response:
        countries = json.load(io.TextIOWrapper(response, encoding="utf-8"))

    image = Image.new("RGB", (WIDTH, HEIGHT), "#cbe5df")
    draw = ImageDraw.Draw(image)

    for latitude in range(-60, 90, 30):
        y = point([0, latitude])[1]
        draw.line((0, y, WIDTH, y), fill="#b4d3ce", width=2)
    for longitude in range(-150, 180, 30):
        x = point([longitude, 0])[0]
        draw.line((x, 0, x, HEIGHT), fill="#b4d3ce", width=2)

    palette = ("#7aaea7", "#82b7ae", "#6fa39e", "#8bbcb2")
    vietnam_feature = None
    for index, feature in enumerate(countries["features"]):
        properties = feature.get("properties", {})
        code = properties.get("ADM0_A3") or properties.get("ISO_A3")
        is_vietnam = code == "VNM" or properties.get("ADMIN") == "Vietnam"
        if is_vietnam:
            vietnam_feature = feature
            continue
        fill = palette[index % len(palette)]
        for ring in polygons(feature["geometry"]):
            if len(ring) >= 3:
                draw.polygon([point(value) for value in ring], fill=fill, outline="#eaf3ed", width=2)

    if vietnam_feature:
        for ring in polygons(vietnam_feature["geometry"]):
            if len(ring) >= 3:
                draw.polygon([point(value) for value in ring], fill="#d92724", outline="#ffd34e", width=5)

    for longitude, latitude, radius in ((108.2, 16.0, 8), (112.0, 16.5, 7), (114.0, 10.0, 7)):
        x, y = point([longitude, latitude])
        draw.ellipse((x - radius, y - radius, x + radius, y + radius), fill="#ffd84d", outline="#a21f1b", width=3)

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    image.save(OUTPUT, "WEBP", quality=88, method=6)
    print(f"Generated {OUTPUT.relative_to(ROOT)} ({OUTPUT.stat().st_size:,} bytes)")


if __name__ == "__main__":
    main()
