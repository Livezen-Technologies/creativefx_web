/**
 * Load every public page at four widths, in every language, and report anything
 * that went wrong on the way.
 *
 * A status-code sweep cannot see a page that returns 200 and renders a blank
 * column, a script that threw before the menu could open, or a table that made
 * the whole document scroll sideways on a phone. This can: it looks for
 * JavaScript errors, for horizontal overflow, and for content that is still
 * transparent after the reveal animations should have finished.
 *
 *     node scripts/check-pages.mjs [baseUrl]
 */
import { existsSync } from 'node:fs';
import { chromium } from 'playwright';

const BASE = process.argv[2] || 'http://127.0.0.1:8083';
const WIDTHS = [390, 768, 1280, 1920];
const LOCALES = ['en', 'si'];
const PATHS = [
  // The commercial spine first, because these are the pages a sweep is really
  // protecting: a 500 on /courses costs money in a way a 500 on /about does not.
  '', '/courses', '/courses/photoshop', '/course/photoshop-level-1',
  '/schedule', '/on-demand', '/on-demand/photoshop-level-1',
  '/certificates', '/certificates/graphic-design-certificate',
  '/bootcamps', '/adobe', '/ai', '/adobe/certification',
  '/corporate', '/corporate/request-quote',
  '/locations', '/locations/colombo', '/instructors', '/reviews',
  '/resources', '/resources/photoshop-shortcut-cheat-sheet', '/webinars',
  '/blog', '/blog/photoshop-vs-illustrator',
  '/cart', '/account/login', '/account/register', '/account/forgot',
  '/search?q=photoshop', '/sitemap', '/contact', '/faq', '/about',
  '/why-mylearnplus', '/policies-terms', '/policies-privacy',
  '/policies-refund', '/policies-reschedule', '/policies-accessibility',
  '/careers',
];

const chrome = process.env.PLAYWRIGHT_CHROMIUM ?? '/opt/pw-browsers/chromium';
const browser = await chromium.launch(existsSync(chrome) ? { executablePath: chrome } : {});
const problems = [];
let checked = 0;

for (const width of WIDTHS) {
  const ctx = await browser.newContext({ viewport: { width, height: 900 } });
  // Seed the stored language so the first-visit chooser stays shut. This sweep
  // is about the pages: a modal that covers every one of them, locks scrolling
  // and holds focus would be measured instead of the page behind it — and it is
  // correct behaviour, covered by scripts/test-language-modal.mjs.
  await ctx.addInitScript(() => {
    try {
      if (!localStorage.getItem('nl_locale')) localStorage.setItem('nl_locale', 'en');
    } catch (e) { /* private window */ }
  });
  const page = await ctx.newPage();

  for (const locale of LOCALES) {
    // Every path in every language. The previous version swept only the first
    // twelve paths in the non-default languages, so a layout that broke in
    // Sinhala anywhere past the twelfth entry passed clean — which is most of
    // the site, and exactly where a longer script is likely to break it.
    const paths = PATHS;

    for (const path of paths) {
      const url = `${BASE}/${locale}${path}`;
      const errors = [];
      page.removeAllListeners('pageerror');
      page.on('pageerror', (e) => errors.push(e.message));

      let status = 0;
      try {
        // domcontentloaded, not networkidle: the home page embeds the
        // Authority's Facebook feed, and waiting for the network to go quiet
        // means waiting for a third-party service this check does not care
        // about — and cannot always reach.
        const r = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 });
        status = r ? r.status() : 0;
      } catch (e) {
        problems.push(`${width}px ${url} — navigation failed: ${e.message.split('\n')[0]}`);
        continue;
      }
      checked++;

      if (status !== 200) problems.push(`${width}px ${url} — HTTP ${status}`);
      // Long enough for Alpine to have initialised and the reveals to have
      // been triggered. The 4-second unhide fallback is checked separately,
      // once, rather than waited for on every one of two hundred loads.
      await page.waitForTimeout(1200);

      const report = await page.evaluate(() => {
        const doc = document.documentElement;
        const invisible = [...document.querySelectorAll('[data-gsap="reveal"]')]
          .filter((el) => {
            const r = el.getBoundingClientRect();
            const onScreen = r.top < window.innerHeight && r.bottom > 0;
            return onScreen && parseFloat(getComputedStyle(el).opacity) < 0.99;
          }).length;
        return {
          overflow: doc.scrollWidth - doc.clientWidth,
          invisible,
          // CodeIgniter's debug toolbar injects its own <h1> (the framework
          // version) in development. It is not part of the page and is not
          // there in production, so it does not count against the one-<h1>
          // rule this is checking.
          h1: [...document.querySelectorAll('h1')]
            .filter((h) => ! h.closest('#debug-bar, #toolbar, .toolbar')).length,
          untranslated: (document.body.innerText.match(/Site\.[a-z_]+\.[a-z_0-9]+/g) || []).length,
        };
      });

      if (report.overflow > 1) problems.push(`${width}px ${url} — page scrolls ${report.overflow}px sideways`);
      // Reveals animate on entry, so blocks below the fold are legitimately
      // transparent at this moment. Only the ones on screen are checked.
      if (report.invisible > 0) problems.push(`${width}px ${url} — ${report.invisible} on-screen block(s) invisible`);
      if (report.h1 !== 1) problems.push(`${width}px ${url} — ${report.h1} <h1> elements`);
      if (report.untranslated > 0) problems.push(`${width}px ${url} — ${report.untranslated} unresolved language key(s)`);
      if (errors.length) problems.push(`${width}px ${url} — JS: ${errors[0]}`);
    }
  }
  await ctx.close();
}

await browser.close();
console.log(`\nChecked ${checked} page loads.`);
if (problems.length === 0) {
  console.log('No problems found.');
} else {
  console.log(`${problems.length} problem(s):`);
  for (const p of problems) console.log('  ' + p);
}
process.exit(problems.length ? 1 : 0);
