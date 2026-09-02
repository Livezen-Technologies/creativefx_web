#!/usr/bin/env python3
"""Reduce an HTML page to the text a reader would actually see.

Written for reading a source site through the asset-import workflow. Two earlier
attempts did this with sed and both failed on real WordPress output: the first
matched <script>...</script> only within a single line, and the second deleted
by line range, which does nothing useful when the whole document is minified
onto a handful of lines. Both let several thousand characters of inline theme
CSS through as "page text" and buried the copy.

Regexes over the whole document, with DOTALL, handle either shape. This is not
a general-purpose HTML parser and does not need to be — it needs to show what a
page says so the content can be rebuilt.

Usage: html-to-text.py FILE [max_lines]
"""
import html
import re
import sys

DROP = re.compile(
    r"<(script|style|noscript|svg|template)\b[^>]*>.*?</\1\s*>",
    re.IGNORECASE | re.DOTALL,
)
COMMENT = re.compile(r"<!--.*?-->", re.DOTALL)
# Tags that separate one thought from the next; everything else just goes.
BREAK = re.compile(
    r"</?(p|div|br|li|tr|h[1-6]|section|article|header|footer|nav|figcaption)\b[^>]*>",
    re.IGNORECASE,
)
TAG = re.compile(r"<[^>]*>")


def main() -> int:
    path = sys.argv[1]
    limit = int(sys.argv[2]) if len(sys.argv) > 2 else 200

    with open(path, "r", encoding="utf-8", errors="replace") as fh:
        doc = fh.read()

    doc = COMMENT.sub(" ", doc)
    doc = DROP.sub(" ", doc)
    doc = BREAK.sub("\n", doc)
    doc = TAG.sub(" ", doc)
    doc = html.unescape(doc)

    seen_blank = False
    printed = 0
    for raw in doc.splitlines():
        line = " ".join(raw.split())
        if not line:
            seen_blank = True
            continue
        # A run of blank lines collapses to one, so the shape of the page
        # survives without the emptiness a stripped document is mostly made of.
        if seen_blank and printed:
            print()
        seen_blank = False
        print(line)
        printed += 1
        if printed >= limit:
            print(f"... [truncated at {limit} lines]")
            break
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
