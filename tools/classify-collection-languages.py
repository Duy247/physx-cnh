#!/usr/bin/env python3
"""Audit and assign the primary language of Magazine and Material PDFs.

The public catalog used to infer Vietnamese for every collection other than the
English books and olympiad archive.  That made English series in the two mixed
collections impossible to filter correctly.  This tool extracts enough text to
classify each PDF, writes the explicit manifest value, and leaves a reviewable
audit trail alongside the catalog.
"""

from __future__ import annotations

import json
import re
from collections import Counter
from pathlib import Path

import pymupdf


ROOT = Path(__file__).resolve().parent.parent
PHYSICS = ROOT / "physics"
CATALOG = PHYSICS / "catalog"
COLLECTIONS = ("materials-pho", "magazines")
PAGE_LIMIT = 6

# These are publication/author identifiers, rather than title guesses.  They
# are stable series whose PDFs are all English, including older scans whose
# text layer is absent or encoded with a legacy font.
ENGLISH_SERIES = ("materials/quantum/",)
ENGLISH_AUTHORS = {"kevin zhou"}

VIETNAMESE_WORDS = (
    " vật lý ", " bài ", " chương ", " trong ", " của ", " và ",
    " cho ", " với ", " các ", " được ", " một ", " những ",
    " bài tập ", " thí nghiệm ", " lời giải ", " tạp chí ",
)
ENGLISH_WORDS = (
    " the ", " and ", " of ", " to ", " in ", " for ", " with ",
    " physics ", " problem ", " solutions ", " solution ", " chapter ",
    " mechanics ", " electromagnetic ", " quantum ",
)
VIETNAMESE_DIACRITICS = re.compile(r"[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]", re.I)


def extract_text(path: Path) -> str:
    try:
        document = pymupdf.open(path)
    except Exception:
        return ""
    try:
        return "\n".join(page.get_text() for page in document[:PAGE_LIMIT])
    finally:
        document.close()


def count_terms(text: str, terms: tuple[str, ...]) -> int:
    normalized = re.sub(r"\s+", " ", text.lower())
    padded = f" {normalized} "
    return sum(padded.count(term) for term in terms)


def classify(item: dict) -> tuple[str, str, int]:
    file = item["file"]
    author = item.get("author", "").lower()
    if file.startswith(ENGLISH_SERIES):
        return "en", "known-English-series", 100
    if any(name in author for name in ENGLISH_AUTHORS):
        return "en", "known-English-author", 100

    text = extract_text(PHYSICS / file)
    vi_score = count_terms(text, VIETNAMESE_WORDS) + 2 * len(VIETNAMESE_DIACRITICS.findall(text))
    en_score = count_terms(text, ENGLISH_WORDS)

    if vi_score >= 8 and vi_score >= en_score * 2:
        return "vi", "pdf-text", min(99, vi_score)
    if en_score >= 8 and en_score >= vi_score * 2:
        return "en", "pdf-text", min(99, en_score)

    # Catalog titles and attributions are only a fallback for scanned or
    # poorly encoded PDFs.  Keep the low confidence in the audit JSON so these
    # decisions stay easy to revisit.
    fallback = f"{item.get('title', '')} {item.get('author', '')}".lower()
    vi_fallback = count_terms(fallback, VIETNAMESE_WORDS) + 2 * len(VIETNAMESE_DIACRITICS.findall(fallback))
    en_fallback = count_terms(fallback, ENGLISH_WORDS)
    if vi_fallback > en_fallback:
        return "vi", "catalog-fallback", vi_fallback
    if en_fallback > vi_fallback:
        return "en", "catalog-fallback", en_fallback
    return "vi", "manual-default", 0


def main() -> None:
    audit = {"version": 1, "scope": list(COLLECTIONS), "pageLimit": PAGE_LIMIT, "documents": []}
    totals = Counter()
    for collection in COLLECTIONS:
        manifest_path = CATALOG / f"{collection}.json"
        manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
        for item in manifest["items"]:
            language, method, confidence = classify(item)
            item["language"] = language
            totals[(collection, language)] += 1
            audit["documents"].append({
                "collection": collection,
                "file": item["file"],
                "language": language,
                "method": method,
                "confidence": confidence,
            })
        manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")

    audit["counts"] = {
        collection: {language: totals[(collection, language)] for language in ("en", "vi")}
        for collection in COLLECTIONS
    }
    (CATALOG / "collection-language-audit.json").write_text(
        json.dumps(audit, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    for collection in COLLECTIONS:
        counts = audit["counts"][collection]
        print(f"{collection}: {counts['en']} English, {counts['vi']} Vietnamese")


if __name__ == "__main__":
    main()
