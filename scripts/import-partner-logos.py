#!/usr/bin/env python3
"""Import partner logos from data URLs, raw base64 files, or HTTP URLs -> logo.webp."""

from __future__ import annotations

import base64
import subprocess
import sys
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PARTNERS = ROOT / "src/assets/images/partners"
DATA_DIR = Path(__file__).resolve().parent / "logo-data"


def to_webp(folder: str, source: Path) -> None:
    out_dir = PARTNERS / folder
    out_dir.mkdir(parents=True, exist_ok=True)
    out = out_dir / "logo.webp"
    tmp = out_dir / "_logo_import_tmp"
    if source != tmp:
        tmp.write_bytes(source.read_bytes())
    subprocess.run(
        [
            "ffmpeg",
            "-y",
            "-hide_banner",
            "-loglevel",
            "error",
            "-i",
            str(tmp),
            "-vf",
            "scale=512:512:force_original_aspect_ratio=decrease",
            "-c:v",
            "libwebp",
            "-quality",
            "92",
            str(out),
        ],
        check=True,
    )
    tmp.unlink(missing_ok=True)
    print(f"  OK {out.relative_to(ROOT)} ({out.stat().st_size // 1024} KB)")


def load_source(path: Path) -> Path:
    text = path.read_text(encoding="utf-8").strip()
    tmp = path.with_suffix(path.suffix + ".decoded")
    if text.startswith("data:"):
        payload = text.split(",", 1)[1]
    else:
        payload = text
    tmp.write_bytes(base64.b64decode(payload))
    return tmp


def import_url(folder: str, url: str) -> None:
    out_dir = PARTNERS / folder
    out_dir.mkdir(parents=True, exist_ok=True)
    tmp = out_dir / "_logo_import_tmp"
    urllib.request.urlretrieve(url, tmp)
    to_webp(folder, tmp)


def main() -> int:
    mapping = {
        "lilly": ("lilly", DATA_DIR / "lilly.b64"),
        "pepco": ("Pepco", DATA_DIR / "pepco.b64"),
        "kik": ("KiK", DATA_DIR / "kik.b64"),
        "zora": ("Zora", DATA_DIR / "zora.b64"),
    }
    for name, (folder, b64_file) in mapping.items():
        if not b64_file.is_file():
            print(f"  SKIP {name}: missing {b64_file.name}", file=sys.stderr)
            continue
        print(name)
        decoded = load_source(b64_file)
        try:
            to_webp(folder, decoded)
        finally:
            decoded.unlink(missing_ok=True)

    print("bgskara (URL)")
    import_url("BG Skara", "https://karlovowaypark.bg/wp-content/uploads/2024/07/bgskara.png")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
