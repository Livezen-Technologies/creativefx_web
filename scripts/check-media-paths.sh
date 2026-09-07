#!/usr/bin/env bash
# Every /media/… path the code references must have a file behind it.
#
# A seeder naming a photograph that is not there fails silently: the page
# renders, the layout holds, and the only symptom is a gap where a picture
# should be. Three shipped that way in one commit here, from nothing worse
# than a missing "-1" in a filename. A grep and a test catch all of them in
# under a second, which is cheaper than looking at every page.
#
# Usage: scripts/check-media-paths.sh [root]
set -uo pipefail
cd "${1:-$(dirname "$0")/..}" || exit 2

missing=0
checked=0

while read -r path; do
    checked=$((checked + 1))
    if [ ! -f "public${path}" ]; then
        printf '  missing: %s\n' "$path"
        missing=$((missing + 1))
        # Offer the near-miss, since these are almost always a suffix slip.
        stem=$(basename "${path%.*}")
        find public/media -maxdepth 2 -name "${stem}*" -printf '      did you mean: /media/%P\n' 2>/dev/null | head -3
    fi
done < <(grep -rhoE '/media/[A-Za-z0-9._@/-]+\.(jpe?g|png|webp|svg|gif|mp4|webm)' \
             modules/ resources/ 2>/dev/null | sort -u)

# An SVG that exists is not necessarily an SVG that renders.
#
# emblem.svg served 200 as image/svg+xml and decoded to nothing, because a
# double hyphen is illegal inside an XML comment and its comment named the CSS
# custom properties the obvious way. Every use of it — the favicon set, the
# loading screen, the Open Graph card — was silently blank, and every check
# that asked whether the file was there said yes.
broken=0
if command -v python3 >/dev/null 2>&1; then
    while read -r svg; do
        if ! python3 -c "import sys,xml.dom.minidom; xml.dom.minidom.parse(sys.argv[1])" "$svg" 2>/dev/null; then
            printf '  malformed XML, will not render: %s\n' "${svg#public}"
            broken=$((broken + 1))
        fi
    done < <(find public/media -name '*.svg' -type f 2>/dev/null | sort)
else
    printf '  note: python3 not found, SVGs were not parsed\n'
fi

if [ "$missing" -gt 0 ] || [ "$broken" -gt 0 ]; then
    [ "$missing" -gt 0 ] && printf '\n%d of %d media references have no file behind them.\n' "$missing" "$checked"
    [ "$broken" -gt 0 ] && printf '%d SVG file(s) will not render.\n' "$broken"
    exit 1
fi

printf 'All %d media references resolve, and every SVG parses.\n' "$checked"
