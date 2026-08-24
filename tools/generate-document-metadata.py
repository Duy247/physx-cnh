#!/usr/bin/env python3
"""Prepare page counts and repository addition dates for public documents."""

from __future__ import annotations

import json
import subprocess
from datetime import datetime, timezone
from pathlib import Path

import fitz

ROOT = Path(__file__).resolve().parents[1]
PHYSICS = ROOT / "physics"
CATALOG = PHYSICS / "catalog" / "public-snapshot.json"
OUTPUT = PHYSICS / "catalog" / "document-metadata.json"


def git(*args: str) -> str:
    return subprocess.run(
        ["git", "-C", str(ROOT), *args],
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
    ).stdout


def tracked_blobs() -> dict[str, str]:
    blobs: dict[str, str] = {}
    for line in git("ls-tree", "-r", "HEAD", "physics").splitlines():
        metadata, separator, filename = line.partition("\t")
        fields = metadata.split()
        if separator and len(fields) >= 3 and filename.startswith("physics/"):
            blobs[filename.removeprefix("physics/")] = fields[2]
    return blobs


def addition_dates() -> dict[str, str]:
    dates: dict[str, str] = {}
    current_date = ""
    history = git("log", "--reverse", "--format=@@%aI", "--name-status", "--find-renames", "--", "physics")
    for line in history.splitlines():
        if line.startswith("@@"):
            current_date = line[2:].split("T", 1)[0]
            continue
        fields = line.split("\t")
        if not current_date or len(fields) < 2:
            continue
        status = fields[0]
        if status.startswith("R") and len(fields) >= 3:
            old_path, new_path = fields[1], fields[2]
            inherited = dates.get(old_path.removeprefix("physics/"), current_date)
            if new_path.startswith("physics/"):
                dates.setdefault(new_path.removeprefix("physics/"), inherited)
        elif fields[1].startswith("physics/"):
            dates.setdefault(fields[1].removeprefix("physics/"), current_date)
    return dates


def main() -> int:
    catalog = json.loads(CATALOG.read_text(encoding="utf-8"))
    previous: dict[str, dict[str, object]] = {}
    if OUTPUT.exists():
        previous = json.loads(OUTPUT.read_text(encoding="utf-8")).get("documents", {})

    blobs = tracked_blobs()
    dates = addition_dates()
    prepared: dict[str, dict[str, object]] = {}
    refreshed = 0
    reused = 0
    failures: list[str] = []

    for document in catalog["documents"]:
        relative = document["file"]["path"]
        source = PHYSICS.joinpath(*Path(relative).parts)
        source_stat = source.stat()
        blob = blobs.get(relative) or f"worktree:{source_stat.st_size}:{source_stat.st_mtime_ns}"
        cached = previous.get(relative, {})
        added_at = str(cached.get("addedAt") or dates.get(relative) or datetime.fromtimestamp(source_stat.st_mtime, timezone.utc).date().isoformat())
        pages: int | None = None

        if document["format"] == "pdf":
            if cached.get("blob") == blob and isinstance(cached.get("pages"), int):
                pages = int(cached["pages"])
                reused += 1
            else:
                try:
                    with fitz.open(source) as pdf:
                        pages = pdf.page_count
                    if pages < 1:
                        raise ValueError("PDF contains no pages")
                    refreshed += 1
                except Exception as error:
                    failures.append(f"{relative}: {error}")

        prepared[relative] = {"blob": blob, "pages": pages, "addedAt": added_at}

    result = {
        "version": 1,
        "generatedAt": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
        "documents": prepared,
    }
    temporary = OUTPUT.with_suffix(".json.tmp")
    temporary.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    temporary.replace(OUTPUT)

    print(f"Document metadata: {refreshed} PDFs inspected, {reused} unchanged, {len(failures)} failed")
    for failure in failures:
        print(f"FAILED {failure}")
    return 1 if failures else 0


if __name__ == "__main__":
    raise SystemExit(main())
