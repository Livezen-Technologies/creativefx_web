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


def expose(path: str, target: float) -> None:
    """Lift a correctly-ranged but underexposed scene to a target lightness.

    Distinct from relight(): that undoes a multiply someone else baked in, this
    is a judgement about how bright the photograph should read. A gamma is used
    rather than a gain because it lifts the midtones — the wood and the husks —
    while leaving the highlights where they are, so the lit kernels do not clip.

    Idempotent in effect: a photograph already at the target is left alone.
    """
    from PIL import Image

    def lightness(im):
        px = list(im.resize((160, 160)).getdata())
        lin = lambda c: (c / 255 / 12.92) if c <= 10 else (((c / 255 + 0.055) / 1.055) ** 2.4)
        y = sum(0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b) for r, g, b in px) / len(px)
        return 116 * (y ** (1 / 3)) - 16 if y > 0.008856 else 903.3 * y

    im  = Image.open(path).convert('RGB')
    now = lightness(im)

    if now >= target - 0.5:
        print(f'{path}: L* {now:.1f} already at or above the target {target:.1f}')
        return

    lo, hi = 0.30, 1.0
    for _ in range(24):                       # bisect for the gamma that lands on target
        mid = (lo + hi) / 2
        lut = [min(255, round(255 * ((i / 255) ** mid))) for i in range(256)]
        if lightness(im.point(lut * 3)) < target:
            hi = mid
        else:
            lo = mid

    gamma = (lo + hi) / 2
    lut   = [min(255, round(255 * ((i / 255) ** gamma))) for i in range(256)]
    out   = im.point(lut * 3)
    out.save(path, 'JPEG', quality=JPEG_QUALITY, optimize=True, progressive=True)
    print(f'{path}: L* {now:.1f} -> {lightness(out):.1f} (gamma {gamma:.3f})')


if __name__ == '__main__':
    args = sys.argv[1:]
    target = None
    if '--target-lstar' in args:
        i = args.index('--target-lstar')
        target = float(args[i + 1])
        del args[i:i + 2]

    targets = args or ['public/media/magiccorn/best-corn.jpg']

    if target is not None:
        for t in targets:
            expose(t, target)
    else:
        changed = sum(relight(t) for t in targets)
        print(f'{changed} of {len(targets)} relit')
