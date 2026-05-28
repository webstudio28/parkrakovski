#!/usr/bin/env python3
"""Write logo .b64 files from embedded data URLs (run once to populate logo-data/)."""

from pathlib import Path

DATA_DIR = Path(__file__).resolve().parent / "logo-data"
DATA_DIR.mkdir(exist_ok=True)

# Paste full data:image/... strings below (one per partner).
PAYLOADS = {
    "lilly.b64": "",  # filled by runner
    "pepco.b64": "",
    "kik.b64": "",
    "zora.b64": "",
}

if __name__ == "__main__":
    for name, data in PAYLOADS.items():
        if not data:
            continue
        (DATA_DIR / name).write_text(data.strip(), encoding="utf-8")
        print("wrote", name)
