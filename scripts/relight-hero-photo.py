#!/usr/bin/env python3
"""Undo the black wash baked into an imported hero photograph.

magiccorn.lk exports its hero backgrounds with the darkening overlay already
flattened into the JPEG: best-corn.jpg arrives with every channel squeezed into
0-67 of the available 0-255, i.e. a ~74% black multiply. Our page heroes lay a
scrim of their own over the image, so the two stack and the hero renders almost
black regardless of how far the scrim is pulled back.

This restores the photograph to full range so the site's own scrim is the only
wash on it. The transform is a plain gain (divide by the measured white point),
which is the exact inverse of a multiply -- no tone curve, no colour shift, so
the photograph is unchanged apart from its exposure.

Idempotent: an image that already reaches full range is left alone.

    python3 scripts/relight-hero-photo.py public/media/magiccorn/best-corn.jpg
"""

import sys

from PIL import Image

# Above this the photo already uses the range it was given and needs no help.
ALREADY_LIT = 200
JPEG_QUALITY = 88


def relight(path: str) -> bool:
    im = Image.open(path).convert('RGB')
    white = max(hi for _, hi in im.getextrema())

    if white >= ALREADY_LIT:
        print(f'{path}: white point {white} — already full range, left alone')
        return False

    gain = 255.0 / white
    im = im.point(lambda v, g=gain: min(255, round(v * g)))
    im.save(path, 'JPEG', quality=JPEG_QUALITY, optimize=True, progressive=True)
    print(f'{path}: white point {white} -> 255 (gain {gain:.2f}x)')
    return True


if __name__ == '__main__':
    targets = sys.argv[1:] or ['public/media/magiccorn/best-corn.jpg']
    changed = sum(relight(t) for t in targets)
    print(f'{changed} of {len(targets)} relit')
