#!/usr/bin/env python3
"""Find verified PhOxiv combined-paper / Olympicos split-paper duplicates."""

from __future__ import annotations

import json
import re
from difflib import SequenceMatcher
from pathlib import Path

import fitz

ROOT = Path(__file__).resolve().parents[1]
MATERIALS = ROOT / "physics" / "materials"
AUTO_THRESHOLD = 0.85
REVIEW_THRESHOLD = 0.75


def text_for(path: Path) -> str:
    with fitz.open(path) as document:
        return "".join(character.lower() for page in document for character in page.get_text() if character.isalnum())


def source_file(path: Path) -> str:
    return path.relative_to(ROOT / "physics").as_posix()


def split_files(directory: Path, year: int, suffix: str) -> list[Path]:
    return sorted(directory.glob(f"*{year}_{suffix}[0-9]*.pdf"))


def main() -> None:
    matches: list[dict[str, object]] = []
    review: list[dict[str, object]] = []
    for mirror in sorted(MATERIALS.glob("*.olimpicos.net")):
        competition = mirror.name.split(".")[0]
        olympicos = mirror / "pdf"
        phoxiv = MATERIALS / "cdn.phoxiv.org" / "olympiads" / competition
        if not olympicos.is_dir() or not phoxiv.is_dir():
            continue
        for combined in sorted(phoxiv.glob("*_*.pdf")):
            parsed = re.fullmatch(r"(\d{4})_(T|T_S)", combined.stem, re.IGNORECASE)
            if not parsed:
                continue
            year, kind = int(parsed.group(1)), parsed.group(2).upper()
            suffix = "S" if kind.endswith("_S") else "Q"
            parts = split_files(olympicos, year, suffix)
            if not parts:
                continue
            combined_text = text_for(combined)
            split_text = "".join(text_for(part) for part in parts)
            if not combined_text or not split_text:
                continue
            similarity = SequenceMatcher(None, combined_text, split_text).ratio()
            record = {
                "competition": competition,
                "year": year,
                "category": "solution" if suffix == "S" else "problem",
                "canonicalFile": source_file(combined),
                "suppressedFiles": [source_file(part) for part in parts],
                "similarity": round(similarity, 4),
                "method": "normalized-pdf-text-sequence-similarity",
            }
            if similarity >= AUTO_THRESHOLD:
                matches.append(record)
            elif similarity >= REVIEW_THRESHOLD:
                review.append(record)
    print(json.dumps({
        "version": 1,
        "policy": {"automaticSimilarityThreshold": AUTO_THRESHOLD, "reviewSimilarityThreshold": REVIEW_THRESHOLD},
        "matches": matches,
        "reviewCandidates": review,
    }, indent=2))


if __name__ == "__main__":
    main()
