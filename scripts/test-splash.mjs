import { chromium } from 'playwright';

/**
 * The loading screen and the mark on it.
 *
 * This is the first thing anyone sees, and it has been wrong twice: once with
 * the Authority's name running off the left edge of a phone, once with the mark
 * jumping size because the <img> declared 300×200 for artwork that is 460×120.
 * Both were reported from a device rather than caught here, and both are the
 * kind of thing a screenshot review notices only if somebody happens to look at
 * the right viewport.
 *
 * So: measured, at both sizes, in both themes.
 *
 * Usage: node scripts/test-splash.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

const SIZES = [[390, 844, 'mobile'], [768, 1024, 'tablet'], [1440, 900, 'desktop']];

for (const [width, height, name] of SIZES) {
  for (const theme of ['light', 'dark']) {
    const context = await browser.newContext({ viewport: { width, height } });
    await context.addInitScript((t) => {
      try {
        localStorage.setItem('nl_locale', 'en');
        if (t === 'dark') localStorage.setItem('nl_theme', 'dark');
      } catch (e) { /* private window */ }
    }, theme);

    const page = await context.newPage();
    const imageRequests = [];
    page.on('request', (r) => {
      if (/\.(svg|png|jpe?g|webp)(\?|$)/i.test(r.url()) && /logo/i.test(r.url())) {
        imageRequests.push(r.url());
      }
    });

    // `commit`, so the splash is still on screen: it clears on load + 850ms.
    await page.goto(`${base}/en`, { waitUntil: 'commit' });
    await page.waitForTimeout(700);

    const at = `${name} ${theme}`;
    const found = await page.evaluate(() => {
      const pre = document.getElementById('preloader');
      if (!pre || getComputedStyle(pre).display === 'none') return null;

      const centreOf = (el) => {
        const r = el.getBoundingClientRect();
        return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, r };
      };
      const logo = pre.querySelector('.pre-logo');
      const word = pre.querySelector('.pre-word');
      const bar = pre.querySelector('.pre-bar');
      const inner = pre.querySelector('.pre-inner');

      // The glyph run, not the text box: letter-spacing leaves a trailing space
      // inside the box, so a box that is centred and a line that looks centred
      // are not the same measurement.
      const range = document.createRange();
      range.selectNodeContents(word.firstChild);
      const lines = [...range.getClientRects()];

      const ir = centreOf(inner);
      return {
        tag: logo ? logo.tagName.toLowerCase() : null,
        logoOff: logo ? Math.round(centreOf(logo).x - window.innerWidth / 2) : null,
        logoWidth: logo ? Math.round(centreOf(logo).w) : 0,
        barOff: bar ? Math.round(centreOf(bar).x - window.innerWidth / 2) : null,
        innerOffX: Math.round(ir.x - window.innerWidth / 2),
        innerOffY: Math.round(ir.y - window.innerHeight / 2),
        overflows: ir.r.left < -0.5 || ir.r.right > window.innerWidth + 0.5,
        glyphOffs: lines.map((r) => Math.round(r.left + r.width / 2 - window.innerWidth / 2)),
      };
    });

    check(`${at}: the loading screen is showing`, found !== null);
    if (found === null) { await context.close(); continue; }

    check(`${at}: the panel is centred horizontally`, Math.abs(found.innerOffX) <= 1, `${found.innerOffX}px`);
    check(`${at}: the panel is centred vertically`, Math.abs(found.innerOffY) <= 1, `${found.innerOffY}px`);
    check(`${at}: nothing runs off the edge`, !found.overflows);
    check(`${at}: the mark is centred`, Math.abs(found.logoOff) <= 1, `${found.logoOff}px`);
    check(`${at}: the progress bar is centred`, Math.abs(found.barOff) <= 1, `${found.barOff}px`);
    check(`${at}: every line of the name is centred`,
      found.glyphOffs.every((o) => Math.abs(o) <= 1), found.glyphOffs.join(', ') + 'px');
    check(`${at}: the mark has a sensible size`,
      found.logoWidth > 80 && found.logoWidth < width * 0.9, `${found.logoWidth}px wide`);

    // Drawn from the theme's tokens, so it costs no request and cannot be the
    // wrong colourway or the wrong shape while it loads.
    check(`${at}: the mark is drawn, not fetched`, found.tag === 'svg', `<${found.tag}>`);
    check(`${at}: no logo file is downloaded`, imageRequests.length === 0,
      imageRequests.join(', '));

    await context.close();
  }
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
