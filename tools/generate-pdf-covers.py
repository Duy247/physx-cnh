#!/usr/bin/env python3
"""Incrementally render lightweight first-page WebP covers for public PDFs."""

from __future__ import annotations

import argparse
import json
import os
from concurrent.futures import ProcessPoolExecutor, as_completed
from pathlib import Path

import fitz
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "physics" / "catalog" / "public-snapshot.json"
OUTPUT = ROOT / "assets" / "v2" / "covers"
PHYSICS = ROOT / "physics"


def archive_cover(output: Path, width: int, quality: int, competition: str, year: object, role: str | None, problem_number: object) -> None:
    height = round(width * 1.42)
    image = Image.new("RGB", (width, height), "#0b3540")
    draw = ImageDraw.Draw(image)
    draw.rectangle((0, 0, width, round(height * .2)), fill="#176b77")
    draw.rectangle((round(width * .1), round(height * .25), round(width * .9), round(height * .255)), fill="#d7b14b")
    font = ImageFont.load_default()
    lines = [competition, str(year), f"Problem {problem_number}" if problem_number else "Document", (role or "document").replace("_", " ").title()]
    y = round(height * .34)
    for index, line in enumerate(lines):
        box = draw.textbbox((0, 0), line, font=font)
        x = (width - (box[2] - box[0])) // 2
        draw.text((x, y), line, fill="#f8f4e9" if index < 3 else "#d7b14b", font=font)
        y += round(height * (.12 if index == 0 else .1))
    output.parent.mkdir(parents=True, exist_ok=True)
    image.save(output, "WEBP", quality=quality, method=6)


def render(job: tuple[str, str, str, int, int, bool, str | None, object, str | None, object]) -> dict[str, object]:
    slug, source_name, output_name, width, quality, force, competition, year, role, problem_number = job
    source, output = Path(source_name), Path(output_name)
    if output.exists() and not force and output.stat().st_mtime >= source.stat().st_mtime:
        return {"slug": slug, "status": "skipped", "bytes": output.stat().st_size}
    try:
        if competition and year:
            archive_cover(output, width, quality, competition, year, role, problem_number)
        else:
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
        jobs.append((document["slug"], str(source), str(OUTPUT / f"{document['slug']}.webp"), args.width, args.quality, args.force, document.get("competitionLabel"), document.get("year"), document.get("role"), document.get("problemNumber")))
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
