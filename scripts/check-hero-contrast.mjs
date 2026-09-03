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
 * Usage: node scripts/check-hero-contrast.mjs [url] [width...]
 */

const url = process.argv[2] || 'http://127.0.0.1:8083/en';
const widths = process.argv.slice(3).map(Number);
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
  const page = await browser.newPage({ viewport: { width, height: 900 } });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);

  const box = await page.$('.hero-box');
  if (!box) {
    console.log(`  ${width}px — no .hero-box on the page`);
    await page.close();
    continue;
  }

  // Every run of text in the panel, with the rectangles its glyphs occupy.
  const runs = await page.evaluate(() => {
    const panel = document.querySelector('.hero-box');
    const origin = panel.getBoundingClientRect();
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

      const range = document.createRange();
      range.selectNodeContents(node);
      const rects = [...range.getClientRects()]
        .filter((r) => r.width > 1 && r.height > 1)
        .map((r) => ({
          x: Math.round(r.x - origin.x),
          y: Math.round(r.y - origin.y),
          w: Math.round(r.width),
          h: Math.round(r.height),
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
  });

  // Hide the glyphs and photograph the panel: what remains is the ground.
  await page.addStyleTag({
    content: '.hero-box *, .hero-box { color: transparent !important; }' +
             '.hero-box svg, .hero-box .hero-dot { visibility: hidden !important; }',
  });
  await page.waitForTimeout(150);
  const png = PNG.sync.read(await box.screenshot());

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
  await page.close();
}

await browser.close();
console.log(failures === 0 ? '\nAll hero text clears WCAG 2.1 AA.' : `\n${failures} contrast failure(s).`);
process.exit(failures === 0 ? 0 : 1);
