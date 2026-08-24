#!/usr/bin/env python3
"""Incrementally render lightweight first-page WebP covers for public PDFs."""

from __future__ import annotations

import argparse
import json
import os
from concurrent.futures import ProcessPoolExecutor, as_completed
from pathlib import Path

import fitz
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "physics" / "catalog" / "public-snapshot.json"
OUTPUT = ROOT / "assets" / "v2" / "covers"
PHYSICS = ROOT / "physics"


def render(job: tuple[str, str, str, int, int, bool]) -> dict[str, object]:
    slug, source_name, output_name, width, quality, force = job
    source, output = Path(source_name), Path(output_name)
    if output.exists() and not force and output.stat().st_mtime >= source.stat().st_mtime:
        return {"slug": slug, "status": "skipped", "bytes": output.stat().st_size}
    try:
        with fitz.open(source) as pdf:
            if pdf.page_count < 1:
                raise ValueError("PDF contains no pages")
            page = pdf.load_page(0)
            scale = max(width / page.rect.width, 0.05)
            pixmap = page.get_pixmap(matrix=fitz.Matrix(scale, scale), colorspace=fitz.csRGB, alpha=False)
        image = Image.frombytes("RGB", (pixmap.width, pixmap.height), pixmap.samples)
        output.parent.mkdir(parents=True, exist_ok=True)
        image.save(output, "WEBP", quality=quality, method=6)
        return {"slug": slug, "status": "created", "bytes": output.stat().st_size}
    except Exception as error:
        return {"slug": slug, "status": "failed", "error": str(error)}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--width", type=int, default=180)
    parser.add_argument("--quality", type=int, default=42)
    parser.add_argument("--workers", type=int, default=max(1, min(4, os.cpu_count() or 1)))
    parser.add_argument("--force", action="store_true")
    args = parser.parse_args()
    catalog = json.loads(CATALOG.read_text(encoding="utf-8"))
    jobs = []
    for document in catalog["documents"]:
        if document["format"] != "pdf":
            continue
        source = PHYSICS.joinpath(*Path(document["file"]["path"]).parts)
        jobs.append((document["slug"], str(source), str(OUTPUT / f"{document['slug']}.webp"), args.width, args.quality, args.force))
    results = []
    with ProcessPoolExecutor(max_workers=args.workers) as executor:
        futures = [executor.submit(render, job) for job in jobs]
        for future in as_completed(futures):
            results.append(future.result())
    failures = [item for item in results if item["status"] == "failed"]
    created = sum(item["status"] == "created" for item in results)
    skipped = sum(item["status"] == "skipped" for item in results)
    print(f"Cover job complete: {created} created, {skipped} unchanged, {len(failures)} failed")
    for failure in failures:
        print(f"FAILED {failure['slug']}: {failure['error']}")
    return 1 if failures else 0


if __name__ == "__main__":
    raise SystemExit(main())
