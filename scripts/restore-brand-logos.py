"""
Restore the customer logos in public/media/brands to white-background PNGs.

    python3 scripts/restore-brand-logos.py public/media/brands

One-off repair, kept because logos keep arriving in the same state. Safe to
re-run: once a file has a white backdrop it is classified as a plate and
passes through untouched.

Every file arrived flattened onto opaque black — the transparency had been
baked out — so on the white .brand-card each drew as a black rectangle.
Treatment differs per logo; one blanket rule gets several wrong (it keys
postie's red plate away, and flips Tesco's white F&F panel to black).
"""
from PIL import Image
from collections import deque, Counter
import glob, os, sys

SRC, NEAR, FRINGE = 'public/media/brands', 26, 70

# keyall     : lift every near-black pixel, not only the region connected to the
#              frame, because enclosed gaps were transparent too (Target's ring)
# flip_light : wordmark drawn white for a dark backdrop. Flip only the LIGHT
#              desaturated ink to dark; brand colours and dark ink are left alone,
#              so Asda's green and Superdry's red survive untouched
KEYALL     = {'20'}
FLIP_LIGHT = {'04', '07', '11'}

def dist(p,q): return max(abs(p[0]-q[0]), abs(p[1]-q[1]), abs(p[2]-q[2]))
def onwhite(im):
    o = Image.new('RGBA', im.size, (255,255,255,255)); o.alpha_composite(im); return o.convert('RGB')

OUT = sys.argv[1]; os.makedirs(OUT, exist_ok=True)
rep=[]
for f in sorted(glob.glob(f'{SRC}/customer-*.png')):
    key = os.path.basename(f).replace('customer-','').replace('.png','')
    im  = Image.open(f).convert('RGBA'); w,h = im.size; px = im.load()
    border = [px[x,y] for x in range(w) for y in (0,h-1)] + [px[x,y] for y in range(h) for x in (0,w-1)]
    bg = Counter((p[0],p[1],p[2]) for p in border).most_common(1)[0][0]

    # A coloured plate (postie red, Lidl blue, Aldi yellow, next's charcoal box)
    # is part of the logo, not the flattening artefact.
    # Aldi's border ring is its own yellow frame, but the four corners outside
    # the rounded rect are still flattened black — go by the corners in that case.
    corners = [px[0,0], px[w-1,0], px[0,h-1], px[w-1,h-1]]
    dark_corners = sum(1 for c in corners if 0.299*c[0]+0.587*c[1]+0.114*c[2] < 40)
    if dark_corners >= 3:
        bg = (0,0,0)
    elif 0.299*bg[0]+0.587*bg[1]+0.114*bg[2] >= 40:
        onwhite(im).save(f'{OUT}/customer-{key}.png'); rep.append((key,'kept plate')); continue

    seen = bytearray(w*h)
    if key in KEYALL:
        for y in range(h):
            for x in range(w):
                if dist(px[x,y], bg) <= NEAR: seen[y*w+x]=1
    else:
        q=deque()
        def push(x,y):
            if not seen[y*w+x] and dist(px[x,y],bg) <= NEAR: seen[y*w+x]=1; q.append((x,y))
        for x in range(w): push(x,0); push(x,h-1)
        for y in range(h): push(0,y); push(w-1,y)
        while q:
            x,y=q.popleft()
            for dx,dy in ((1,0),(-1,0),(0,1),(0,-1)):
                nx,ny=x+dx,y+dy
                if 0<=nx<w and 0<=ny<h: push(nx,ny)

    rim = [(x,y) for y in range(h) for x in range(w)
           if not seen[y*w+x] and any(0<=x+dx<w and 0<=y+dy<h and seen[(y+dy)*w+x+dx]
                                      for dx,dy in ((1,0),(-1,0),(0,1),(0,-1)))]
    for y in range(h):
        for x in range(w):
            if seen[y*w+x]: px[x,y]=(255,255,255,0)
    for x,y in rim:                                             # soften the cut edge
        r,g,b,a = px[x,y]; d = dist((r,g,b), bg)
        if d < FRINGE: px[x,y]=(r,g,b,int(255*d/FRINGE))

    note = 'keyed (all)' if key in KEYALL else 'keyed'
    if key in FLIP_LIGHT:
        note += ' + light ink flipped'
        for y in range(h):
            for x in range(w):
                r,g,b,a = px[x,y]
                if a == 0: continue
                v, m = max(r,g,b), min(r,g,b)
                sat = 0 if v == 0 else (v-m)/v
                L = 0.299*r + 0.587*g + 0.114*b
                if sat < 0.25 and L > 128:                      # white ink -> dark ink
                    nl = int(255 - L)
                    px[x,y] = (nl, nl, nl, a)
    onwhite(im).save(f'{OUT}/customer-{key}.png'); rep.append((key,note))
for k,n in rep: print(f'{k:4} {n}')
