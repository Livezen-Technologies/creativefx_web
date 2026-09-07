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
import { existsSync, readdirSync } from 'node:fs';
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

// Which prefixes count as an unresolved language key.
//
// This was a hand-written list, and a hand-written list goes stale the day
// somebody adds a bundle: `Reviews.php` was added and the sweep stopped being
// able to see a missing Reviews key at all — a check that silently narrows is
// worse than no check, because it still reports "clean". The names come from
// the language files on disk instead, so a new bundle is covered the moment it
// exists.
const NAMESPACES = [...new Set(
  ['app/Language/en', ...readdirSync('modules', { withFileTypes: true })
    .filter((d) => d.isDirectory())
    .map((d) => `modules/${d.name}/Language/en`)]
    .filter((dir) => existsSync(dir))
    .flatMap((dir) => readdirSync(dir))
    .filter((f) => f.endsWith('.php'))
    .map((f) => f.replace(/\.php$/, '')),
)].sort();

if (NAMESPACES.length === 0) {
  console.error('No language bundles found — the unresolved-key check would pass vacuously.');
  process.exit(2);
}

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

      // Whether the PAGE scrolls sideways, asked by trying to scroll it.
      //
      // The obvious measure — documentElement.scrollWidth - clientWidth —
      // reports a wide table as page overflow even when that table is sealed
      // inside its own overflow-x-auto box and the document cannot move a
      // pixel. It flagged five pages that were fine.
      //
      // But asking in the other direction has its own trap, and it is worse:
      // the site runs Lenis, so a scroll is animated and reading window.scrollX
      // in the same turn reports 0 on a page that pans perfectly well a moment
      // later. A check that answers "no problem" while the page slides under
      // the reader's thumb is more dangerous than the one that cried wolf.
      //
      // So: scroll, wait for the animation, then read. This is the reading that
      // caught the course page panning 204px behind an invisible sr-only span.
      await page.evaluate(() => window.scrollTo(9999, window.scrollY));
      await page.waitForTimeout(700);
      const sidewaysPan = await page.evaluate(() => window.scrollX);
      await page.evaluate(() => window.scrollTo(0, window.scrollY));
      await page.waitForTimeout(400);

      const report = await page.evaluate(([namespaces, sidewaysPan]) => {
        const doc = document.documentElement;
        const invisible = [...document.querySelectorAll('[data-gsap="reveal"]')]
          .filter((el) => {
            const r = el.getBoundingClientRect();
            const onScreen = r.top < window.innerHeight && r.bottom > 0;
            return onScreen && parseFloat(getComputedStyle(el).opacity) < 0.99;
          }).length;
        return {
          overflow: sidewaysPan,
          invisible,
          // CodeIgniter's debug toolbar injects its own <h1> (the framework
          // version) in development. It is not part of the page and is not
          // there in production, so it does not count against the one-<h1>
          // rule this is checking.
          h1: [...document.querySelectorAll('h1')]
            .filter((h) => ! h.closest('#debug-bar, #toolbar, .toolbar')).length,
          // Every module's namespace, not just Site's. The pattern used to
          // read /Site\./ only, so a view asking for a Catalog key that had
          // never been written printed "Catalog.instructors.teaches_count" at
          // a visitor and swept clean. The keys themselves are reported, not
          // just a count: a number tells you a page is broken, the key tells
          // you which line to write.
          untranslated: [...new Set(document.body.innerText.match(
            new RegExp(`\\b(?:${namespaces.join('|')})\\.[A-Za-z_]+(?:\\.[A-Za-z_0-9]+)+`, 'g'),
          ) || [])],
        };
      }, [NAMESPACES, sidewaysPan]);

      if (report.overflow > 1) problems.push(`${width}px ${url} — page scrolls ${report.overflow}px sideways`);

      // Reveals animate on entry, so blocks below the fold are legitimately
      // transparent at this moment. Only the ones on screen are checked.
      if (report.invisible > 0) problems.push(`${width}px ${url} — ${report.invisible} on-screen block(s) invisible`);
      if (report.h1 !== 1) problems.push(`${width}px ${url} — ${report.h1} <h1> elements`);
      if (report.untranslated.length) {
        problems.push(`${width}px ${url} — unresolved language key(s): ${report.untranslated.join(', ')}`);
      }
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
