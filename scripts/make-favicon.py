#!/usr/bin/env python3
"""Build square app icons from the wordmark.

The favicon pointed at the logo PNG directly. That file is a 300x200 lockup —
"Giants" over "Forest" over "KUKULEGANGA" — and a browser squeezes the whole
thing into a 16px box, where it is a grey smear. Rendered back up, it is not
readable as anything. An icon has to work at the size it is actually drawn.

So the icon is the mark's own G, cropped from the supplied artwork and set on
the brand green with the rounded corner the site uses elsewhere. Nothing is
redrawn and no letterform is invented: this is a detail of the logo the hotel
already publishes, which is what a favicon is for.

The crop is deliberately generous on the right. The brush lettering overlaps —
the upstroke of the following "i" runs into the G's bowl — so cutting at the G's
own edge slices the bowl open. A couple of pixels of the next stroke read as
part of the brush at icon size; a broken bowl does not.

Usage: make-favicon.py
"""
from PIL import Image, ImageDraw

SRC = "public/media/giantforests/Kukuleganga-Giants-Forest-Logo-white.png"
GREEN = (52, 97, 66)
G_BOX = (0, 0, 86, 100)   # the G, measured off the 300x200 artwork


def compose(mark, size, pad_ratio=0.16, radius_ratio=0.22):
    canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))

    mask = Image.new("L", (size, size), 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        [0, 0, size - 1, size - 1], radius=int(size * radius_ratio), fill=255
    )
    canvas.paste(Image.new("RGBA", (size, size), GREEN + (255,)), (0, 0), mask)

    box = size - int(size * pad_ratio) * 2
    scale = min(box / mark.width, box / mark.height)
    art = mark.resize(
        (max(1, round(mark.width * scale)), max(1, round(mark.height * scale))),
        Image.LANCZOS,
    )
    canvas.paste(art, ((size - art.width) // 2, (size - art.height) // 2), art)
    return canvas


def main():
    logo = Image.open(SRC).convert("RGBA")
    mark = logo.crop(G_BOX)
    bbox = mark.getchannel("A").getbbox()
    if bbox:
        mark = mark.crop(bbox)
    print(f"mark {mark.size} from {SRC}")

    for size, path in [
        (32, "public/favicon-32.png"),
        (48, "public/favicon-48.png"),
        (180, "public/apple-touch-icon.png"),
        (192, "public/favicon-192.png"),
        (512, "public/favicon-512.png"),
    ]:
        compose(mark, size).save(path)
        print(path)

    # A multi-resolution .ico for anything still asking for /favicon.ico.
    compose(mark, 64).save("public/favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)])
    print("public/favicon.ico")


if __name__ == "__main__":
    main()
