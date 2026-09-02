#!/usr/bin/env python3
"""Make a single-colour version of a transparent logo.

The hotel publishes a white logo for use on dark ground, but the import brought
it back as a WordPress 404 page rather than an image, so it is not available.
The colour logo is a clean transparent PNG drawn entirely in the brand green, so
the white version is recoverable from it exactly: keep the alpha channel, which
carries the whole shape including its anti-aliasing, and replace the colour.

That is a knockout, not a redraw — no letterform is invented, and the result is
the same mark the alpha already described.

Usage: knockout-logo.py SOURCE DEST [R G B]
"""
import sys

from PIL import Image


def main() -> int:
    src, dest = sys.argv[1], sys.argv[2]
    rgb = tuple(int(v) for v in sys.argv[3:6]) if len(sys.argv) >= 6 else (255, 255, 255)

    im = Image.open(src).convert("RGBA")
    alpha = im.getchannel("A")

    if alpha.getextrema() == (255, 255):
        print(f"{src}: fully opaque — there is no shape in the alpha to keep. Refusing.")
        return 1

    out = Image.new("RGBA", im.size, rgb + (0,))
    out.putalpha(alpha)
    out.save(dest)

    lo, hi = alpha.getextrema()
    print(f"{dest}: {im.size[0]}x{im.size[1]}, alpha {lo}-{hi}, filled rgb{rgb}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
