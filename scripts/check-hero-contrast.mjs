import { chromium } from 'playwright';
import { PNG } from 'pngjs';

/**
 * Measure the home page hero's text contrast against the pixels actually
 * behind it.
 *
 * The hero puts words over a photograph nobody has chosen yet, so "it looks
 * fine" is not a check — the next upload can break it silently. This loads the
 * panel, hides every glyph, photographs what is left, and computes the ratio
 * for each run of text against the darkest and lightest ground it covers.
 *
 * Two details that make the number trustworthy:
 *
 *   - It samples the *glyph* rectangles, taken from a Range over each text
 *     node, not the element's box. A short line in a full-width <p> has a box
 *     stretching far past its last letter, and measuring that reports a failure
 *     where no text is.
 *   - It composites the ink over the sampled ground before comparing, so text
 *     drawn at less than full alpha is judged as it is seen rather than as it
 *     is declared.
 *
 * It takes a selector, so it is not only about the hero: the header and the
 * footer carry the dark scope in both themes, which is exactly the situation
 * where a colour is chosen once and never checked against the ground it
 * actually lands on.
 *
 * Usage: node scripts/check-hero-contrast.mjs [url] [selector] [width...]
 *        THEME=dark node scripts/check-hero-contrast.mjs …
 *
 * The theme matters wherever a region follows it: the same colour that clears
 * AA on the light ground can fail on the dark one, and checking only the
 * default means finding that out from a reader.
 */

const url = process.argv[2] || 'http://127.0.0.1:8083/en';
const SELECTOR = process.argv[3] || '.site-hero';
let missing = 0;
const widths = process.argv.slice(4).map(Number);
const WIDTHS = widths.length ? widths : [390, 768, 1280, 1920];

const lin = (c) => {
  c /= 255;
  return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
};
const lum = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
const ratio = (a, b) => (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);

const parseColor = (css) => {
  const m = css.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,/\s]+([\d.]+))?/);
  return { r: +m[1], g: +m[2], b: +m[3], a: m[4] === undefined ? 1 : +m[4] };
};

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
let failures = 0;

for (const width of WIDTHS) {
  const context = await browser.newContext({ viewport: { width, height: 900 } });
  await context.addInitScript((theme) => {
    try {
      localStorage.setItem('nl_locale', 'en');
      if (theme === 'dark') localStorage.setItem('nl_theme', 'dark');
    } catch (e) { /* private window */ }
  }, process.env.THEME || 'light');
  const page = await context.newPage();
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);

  const box = await page.$(SELECTOR);
  if (!box) {
    // Not a pass. This check used to look for `.hero-full`, a component the
    // rebrand removed, and printed "no .hero-full on the page" followed by
    // "All text clears WCAG 2.1 AA" — a clean bill of health for a panel it had
    // never looked at. A check that cannot find its subject has failed to run,
    // and must say so loudly enough to be fixed.
    console.error(`  ${width}px — FAIL: no ${SELECTOR} on the page, so nothing was measured`);
    missing++;
    await page.close();
    continue;
  }

  // Every run of text in the panel, with the rectangles its glyphs occupy.
  const runs = await page.evaluate((SEL) => {
    const panel = document.querySelector(SEL);
    if (!panel) return [];
    const out = [];
    const walker = document.createTreeWalker(panel, NodeFilter.SHOW_TEXT);

    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
      const text = node.nodeValue.trim();
      if (text === '') continue;
      const el = node.parentElement;
      if (!el || el.closest('[aria-hidden="true"]')) continue;
      const style = getComputedStyle(el);
      if (style.visibility === 'hidden' || style.display === 'none') continue;
      // Visually-hidden labels are read aloud, never seen. Their Range rects
      // are the text's natural width even though the element clips to 1px, so
      // without this they report as failures over whatever they float above.
      if (el.closest('.sr-only')) continue;
      const ownRect = el.getBoundingClientRect();
      if (ownRect.width < 4 || ownRect.height < 4) continue;

      // Text clipped away by a scrolling ancestor is not on the screen, but its
      // Range rects still report where it *would* be. Measuring those samples
      // the page underneath the container and reports failures for words nobody
      // can see — so every rect is clipped to what is actually visible first.
      const clip = (r) => {
        let box = { top: r.top, left: r.left, right: r.right, bottom: r.bottom };
        for (let a = el; a && a !== document.body; a = a.parentElement) {
          const st = getComputedStyle(a);
          if (st.overflow === 'visible' && st.overflowX === 'visible' && st.overflowY === 'visible') continue;
          const ar = a.getBoundingClientRect();
          box = {
            top: Math.max(box.top, ar.top),
            left: Math.max(box.left, ar.left),
            right: Math.min(box.right, ar.right),
            bottom: Math.min(box.bottom, ar.bottom),
          };
        }
        return box.right - box.left > 1 && box.bottom - box.top > 1 ? box : null;
      };

      const range = document.createRange();
      range.selectNodeContents(node);
      const rects = [...range.getClientRects()]
        .filter((r) => r.width > 1 && r.height > 1)
        .map(clip)
        .filter(Boolean)
        .map((r) => ({
          x: Math.round(r.left + window.scrollX),
          y: Math.round(r.top + window.scrollY),
          w: Math.round(r.right - r.left),
          h: Math.round(r.bottom - r.top),
        }));
      if (rects.length === 0) continue;

      // WCAG's large-text threshold: 24px, or 18.66px when bold.
      const size = parseFloat(style.fontSize);
      const weight = parseInt(style.fontWeight, 10) || 400;
      const large = size >= 24 || (size >= 18.66 && weight >= 700);

      out.push({
        text: text.slice(0, 42),
        color: style.color,
        need: large ? 3.0 : 4.5,
        rects,
      });
    }
    return out;
  }, SELECTOR);

  // Hide the glyphs and photograph the panel: what remains is the ground.
  await page.addStyleTag({
    content: `${SELECTOR} *, ${SELECTOR} { color: transparent !important; }`
           + `${SELECTOR} svg, ${SELECTOR} img, ${SELECTOR} .hero-dot { visibility: hidden !important; }`,
  });

  // Floating overlays — the header, the help launcher — are fixed to the
  // viewport, so an element screenshot composites them wherever the capture
  // happened to be scrolled. Left in, they become the "ground" for whatever
  // text they landed on and report failures the hero is not responsible for.
  await page.evaluate((SEL) => {
    const hero = document.querySelector(SEL);
    document.querySelectorAll('body *').forEach((el) => {
      if (getComputedStyle(el).position !== 'fixed') return;
      if (hero && hero.contains(el)) return;
      el.style.setProperty('visibility', 'hidden', 'important');
    });
  }, SELECTOR);
  await page.waitForTimeout(150);
  // Full page, not the element. An element taller than the viewport is captured
  // by scrolling and stitching, and the result does not line up with
  // coordinates measured beforehand — which reported the footer's text as
  // sitting on the light page ground three screens above it. Document
  // coordinates against a document-sized image cannot drift.
  const png = PNG.sync.read(await page.screenshot({ fullPage: true }));

  let worst = Infinity;
  let worstText = '';

  for (const run of runs) {
    const ink = parseColor(run.color);
    let min = Infinity;
    let ground = null;

    for (const r of run.rects) {
      for (let y = r.y; y < Math.min(r.y + r.h, png.height); y += 2) {
        for (let x = r.x; x < Math.min(r.x + r.w, png.width); x += 2) {
          if (x < 0 || y < 0) continue;
          const i = (png.width * y + x) << 2;
          const bg = [png.data[i], png.data[i + 1], png.data[i + 2]];
          const fg = [ink.r, ink.g, ink.b].map((c, k) => ink.a * c + (1 - ink.a) * bg[k]);
          const c = ratio(lum(...fg), lum(...bg));
          if (c < min) { min = c; ground = bg; }
        }
      }
    }

    if (min === Infinity) continue;
    if (min < run.need) {
      failures++;
      console.log(`  FAIL ${width}px  ${min.toFixed(2)}:1 (needs ${run.need})  "${run.text}"  over rgb(${ground})`);
    }
    if (min < worst) { worst = min; worstText = run.text; }
  }

  console.log(`  ${width}px — ${runs.length} runs, tightest ${worst.toFixed(2)}:1 ("${worstText}")`);
  await context.close();
}

await browser.close();
if (missing > 0) {
  console.error(`\n${missing} viewport(s) had no ${SELECTOR} to measure — nothing was checked there.`);
}
console.log(failures === 0 && missing === 0
  ? `\nAll text in ${SELECTOR} clears WCAG 2.1 AA.`
  : `\n${failures} contrast failure(s).`);
process.exit(failures === 0 && missing === 0 ? 0 : 1);
