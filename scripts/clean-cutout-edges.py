#!/usr/bin/env python3
"""Soften the hard edge on a flattened product cutout.

Magic-Corn-with-corn.png — the product shot behind every corn-in-a-cup card —
is a palette PNG whose transparency is one bit deep. The original cutout's
anti-aliased boundary was flattened against white before the palette was built,
so every edge pixel is either fully opaque or fully gone, and the last opaque
row still carries the white it was blended into. On the site's dark cards that
reads as a jagged pale outline tracing the whole silhouette.

Eroding the mask by a couple of pixels drops the contaminated row, and a light
blur gives back the anti-aliasing the palette threw away. Only the alpha
channel is touched, so no colour in the photograph moves — and because the work
is confined to the mask boundary, interior whites (the cup's rim, the cob's
centre) are untouched.

Idempotent: a mask that already has soft edges is left alone.

    python3 scripts/clean-cutout-edges.py public/media/magiccorn/Magic-Corn-with-corn.png
"""

import sys

from PIL import Image, ImageFilter

ERODE_PX = 2
FEATHER_PX = 1.0
# A one-bit mask has (almost) no values between clear and solid. Anything above
# this share of partial pixels means the edge is already anti-aliased.
SOFT_EDGE_SHARE = 0.005


def clean(path: str) -> bool:
    im = Image.open(path).convert('RGBA')
    alpha = im.getchannel('A')

    hist = alpha.histogram()
    partial = sum(hist[8:248]) / float(im.width * im.height)
    if partial > SOFT_EDGE_SHARE:
        print(f'{path}: {partial:.1%} partial alpha — edge already soft, left alone')
        return False

    mask = alpha
    for _ in range(ERODE_PX):
        mask = mask.filter(ImageFilter.MinFilter(3))
    mask = mask.filter(ImageFilter.GaussianBlur(FEATHER_PX))

    im.putalpha(mask)
    im.save(path, 'PNG', optimize=True)
    print(f'{path}: eroded {ERODE_PX}px, feathered {FEATHER_PX}px')
    return True


if __name__ == '__main__':
    targets = sys.argv[1:] or ['public/media/magiccorn/Magic-Corn-with-corn.png']
    changed = sum(clean(t) for t in targets)
    print(f'{changed} of {len(targets)} cleaned')
