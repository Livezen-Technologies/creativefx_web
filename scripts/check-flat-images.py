#!/usr/bin/env python3
"""Flag referenced images that are a flat colour rather than a picture.

A missing file at least leaves a gap. A file that exists but is a solid plate
renders as a confident coloured box, resolves in every path check, returns 200
from every probe, and looks deliberate — so it survives all the way to the live
site. Kukuleganga-Giants-Forest-background-1-1.jpg is exactly that: a #346142
section background carrying a faint watermark. Pointed at the hero and at half
of a photo collage, it painted both green and nothing complained.

The test is variance, not exact uniformity: a plate is not perfectly flat once
it carries a watermark and JPEG noise, so measure how far the pixels stray from
the mean and call anything very close to its own average a plate. Logos, icons
and the marks in media/certifications are excluded — flatness is the point
there. A hit is worth looking at, not automatically wrong.

Usage: check-flat-images.py [repo-root]
"""
import re
import sys
from pathlib import Path

try:
    from PIL import Image
except ImportError:
    print("Pillow is not installed; skipping the flat-image check.")
    raise SystemExit(0)

# Below this mean absolute deviation from the image's own average colour, there
# is not enough going on for the file to be a photograph of anything.
FLAT = 12.0
SKIP = re.compile(r"(logo|favicon|icon|watermark|/certifications/|\.svg$)", re.IGNORECASE)
REF = re.compile(r"/media/[A-Za-z0-9._@/-]+\.(?:jpe?g|png|webp)")


def deviation(path: Path) -> float:
    im = Image.open(path).convert("RGB")
    im.thumbnail((64, 64))
    b = im.tobytes()                       # flat RGB, no deprecated accessor
    n = len(b)
    avg = [sum(b[i::3]) / (n / 3) for i in range(3)]
    return sum(abs(v - avg[i % 3]) for i, v in enumerate(b)) / n


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else Path(__file__).parent.parent)
    refs = set()
    for src in list((root / "modules").rglob("*.php")) + list((root / "resources").rglob("*.*")):
        try:
            refs.update(REF.findall(src.read_text(encoding="utf-8", errors="ignore")))
        except OSError:
            continue

    flat, checked = [], 0
    for ref in sorted(refs):
        if SKIP.search(ref):
            continue
        f = root / "public" / ref.lstrip("/")
        if not f.is_file():
            continue          # check-media-paths.sh owns the missing-file case
        checked += 1
        try:
            d = deviation(f)
        except Exception as exc:                      # noqa: BLE001
            print(f"  unreadable: {ref} ({exc})")
            continue
        if d < FLAT:
            flat.append((ref, d))

    for ref, d in flat:
        print(f"  flat: {ref}  (deviation {d:.1f}) — a plate, not a photograph?")

    if flat:
        print(f"\n{len(flat)} of {checked} referenced photographs are close to a single colour.")
        return 1
    print(f"All {checked} referenced photographs carry real detail.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
