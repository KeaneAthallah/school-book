"""QA for captured screenshots: flag blank, clipped or suspiciously small images.

Usage: python docs/panduan/scripts/qa_images.py
"""
from __future__ import annotations

import statistics
import sys
from pathlib import Path

from PIL import Image, ImageStat

SCREENS = Path(__file__).resolve().parent.parent / "screens"
MIN_WIDTH = 200
MIN_STDDEV = 3.0  # a blank capture sits near 0; real crops with whitespace sit well above
MIN_BYTES = 2_000


def main() -> int:
    files = sorted(SCREENS.glob("*.png"))
    if not files:
        print("no screenshots found")
        return 1

    problems: list[str] = []
    rows: list[tuple[str, str, int, int, float, int]] = []

    for path in files:
        size = path.stat().st_size
        with Image.open(path) as image:
            gray = image.convert("L")
            width, height = gray.size
            stat = ImageStat.Stat(gray)
            stddev = statistics.fmean(stat.stddev) if stat.stddev else 0.0

        reason = None
        if width < MIN_WIDTH:
            reason = f"width {width} < {MIN_WIDTH}"
        elif stddev < MIN_STDDEV:
            reason = f"flat image (stddev {stddev:.1f})"
        elif size < MIN_BYTES:
            reason = f"tiny file ({size} bytes)"

        if reason:
            problems.append(f"{path.name}: {reason}")

        rows.append((path.name, f"{width}x{height}", width, height, stddev, size))

    print(f"checked {len(rows)} images\n")
    print(f"{'file':44} {'size':>12} {'stddev':>8} {'kB':>8}")
    for name, dims, _w, _h, stddev, size in rows:
        flag = ""
        if _w < MIN_WIDTH or stddev < MIN_STDDEV or size < MIN_BYTES:
            flag = "  <-- CHECK"
        print(f"{name:44} {dims:>12} {stddev:>8.1f} {size / 1024:>8.1f}{flag}")

    print()
    if problems:
        print(f"{len(problems)} problematic image(s):")
        for problem in problems:
            print(f"  - {problem}")
        return 1

    print("all screenshots look valid")
    return 0


if __name__ == "__main__":
    sys.exit(main())
