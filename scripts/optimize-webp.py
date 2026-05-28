#!/usr/bin/env python3
"""Re-encode WebP assets for web: resize + sensible compression (ffmpeg)."""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "src" / "assets" / "images"

PHOTO_MAX_EDGE = 1920
LOGO_MAX_EDGE = 320
PHOTO_QUALITY = 82
LOGO_QUALITY = 88
COMPRESSION_LEVEL = 6


def ffprobe_size(path: Path) -> tuple[int, int] | None:
    try:
        out = subprocess.check_output(
            [
                "ffprobe",
                "-v",
                "error",
                "-select_streams",
                "v:0",
                "-show_entries",
                "stream=width,height",
                "-of",
                "csv=p=0",
                str(path),
            ],
            text=True,
            stderr=subprocess.DEVNULL,
        ).strip()
        w, h = out.split(",")
        return int(w), int(h)
    except (subprocess.CalledProcessError, ValueError):
        return None


def optimize_file(path: Path, *, dry_run: bool = False) -> dict | None:
    is_logo = path.name.lower() == "logo.webp"
    max_edge = LOGO_MAX_EDGE if is_logo else PHOTO_MAX_EDGE
    quality = LOGO_QUALITY if is_logo else PHOTO_QUALITY

    before = path.stat().st_size
    dims = ffprobe_size(path)
    if dims is None:
        return None

    w, h = dims
    long_edge = max(w, h)
    needs_resize = long_edge > max_edge
    # Re-encode heavy files even when dimensions are already small.
    needs_reencode = before > 350_000 and not is_logo

    if not needs_resize and not needs_reencode and not is_logo:
        return None
    if is_logo and long_edge <= max_edge and before < 80_000:
        return None

    if dry_run:
        return {
            "path": str(path.relative_to(ROOT.parent.parent.parent)),
            "before": before,
            "after": before,
            "dry_run": True,
        }

    tmp = path.with_name(path.stem + ".opt.webp")
    scale = f"scale={max_edge}:{max_edge}:force_original_aspect_ratio=decrease"
    cmd = [
        "ffmpeg",
        "-y",
        "-hide_banner",
        "-loglevel",
        "error",
        "-i",
        str(path),
        "-vf",
        scale,
        "-c:v",
        "libwebp",
        "-quality",
        str(quality),
        "-compression_level",
        str(COMPRESSION_LEVEL),
        str(tmp),
    ]
    subprocess.run(cmd, check=True)
    after = tmp.stat().st_size
    # Keep original if optimization grew the file (rare).
    if after >= before and not needs_resize:
        tmp.unlink(missing_ok=True)
        return None
    tmp.replace(path)
    return {
        "path": str(path.relative_to(ROOT.parent.parent.parent)),
        "before": before,
        "after": path.stat().st_size,
        "width": ffprobe_size(path),
    }


def collect_webps(root: Path) -> list[Path]:
    return sorted(p for p in root.rglob("*.webp") if p.is_file())


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--root",
        type=Path,
        default=ROOT,
        help=f"Image root (default: {ROOT})",
    )
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--json", action="store_true", help="Print summary JSON")
    args = parser.parse_args()

    files = collect_webps(args.root)
    if not files:
        print(f"No WebP files under {args.root}", file=sys.stderr)
        return 1

    total_before = sum(p.stat().st_size for p in files)
    results: list[dict] = []
    errors: list[str] = []

    for i, path in enumerate(files, 1):
        try:
            result = optimize_file(path, dry_run=args.dry_run)
            if result:
                results.append(result)
        except subprocess.CalledProcessError as exc:
            errors.append(f"{path}: ffmpeg failed ({exc})")
        if not args.json and i % 25 == 0:
            print(f"  … {i}/{len(files)}", flush=True)

    files_after = collect_webps(args.root)
    total_after = sum(p.stat().st_size for p in files_after)
    over_1mb = sum(1 for p in files_after if p.stat().st_size > 1_000_000)

    summary = {
        "files_scanned": len(files),
        "files_optimized": len(results),
        "errors": errors,
        "total_before_mb": round(total_before / 1_048_576, 2),
        "total_after_mb": round(total_after / 1_048_576, 2),
        "over_1mb_after": over_1mb,
        "dry_run": args.dry_run,
    }

    if args.json:
        print(json.dumps({**summary, "optimized": results}, indent=2))
    else:
        saved = total_before - total_after
        print(
            f"Scanned {len(files)} WebP — optimized {len(results)} file(s).\n"
            f"Size: {summary['total_before_mb']} MB -> {summary['total_after_mb']} MB "
            f"({round(saved / 1_048_576, 2)} MB saved).\n"
            f"Files still > 1 MB: {over_1mb}."
        )
        if errors:
            print("Errors:", file=sys.stderr)
            for e in errors:
                print(f"  {e}", file=sys.stderr)

    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
