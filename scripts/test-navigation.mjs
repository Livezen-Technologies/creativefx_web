import { chromium } from 'playwright';

/**
 * Can a reader reach the navigation, at every width, in every language?
 *
 * This exists because they could not. The header shows a hamburger whenever the
 * nav does not fit — a measurement, since the same eight items are half again
 * as wide in Tamil — but the drawer it opened was hidden by a hard `lg:hidden`
 * breakpoint. Between 1024px and the width the menu actually fits at, the
 * button was there, the click registered, and nothing happened: every link on
 * the site unreachable at the width most laptops run at.
 *
 * Nothing else caught it. The page sweep loads pages and never clicks; the
 * contrast checker measures colour. A widget that is present, styled and inert
 * is invisible to both. So this asserts the outcome a reader cares about — I
 * can get to the menu, and the links in it go somewhere — rather than the
 * mechanism.
 *
 * Usage: node scripts/test-navigation.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');

// The awkward widths on purpose: 1024 and 1280 are where a breakpoint and a
// measurement disagreed, 1440 is where English starts fitting, 1920 is where
// Tamil does. 390 and 768 are the phone and the tablet.
const WIDTHS = [390, 768, 1024, 1280, 1440, 1600, 1920];
const LOCALES = ['en', 'si', 'ta'];

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

for (const locale of LOCALES) {
  const results = [];

  for (const width of WIDTHS) {
    const context = await browser.newContext({ viewport: { width, height: 900 } });
    await context.addInitScript(() => {
      try { localStorage.setItem('nl_locale', 'en'); } catch (e) { /* private window */ }
    });
    const page = await context.newPage();
    // domcontentloaded, not networkidle: the home page embeds the Authority's
    // Facebook feed, so waiting for the network to fall quiet means waiting for
    // a third-party service this check does not care about and cannot always
    // reach. check-pages.mjs made the same call for the same reason.
    await page.goto(`${base}/${locale}`, { waitUntil: 'domcontentloaded' });
    // The header measures itself on load and again once the web fonts land,
    // and the answer changes when they do.
    await page.waitForTimeout(1500);

    const inlineLinks = await page.$$eval('.primary-nav a', (els) =>
      els.filter((e) => e.offsetParent !== null).length);
    const burger = await page.$('button[aria-label="Toggle menu"]');
    const burgerVisible = burger !== null && await burger.isVisible();

    let route = 'inline';
    let reachable = inlineLinks;

    if (inlineLinks === 0) {
      // No inline nav, so the drawer is the only way through. Open it.
      check(`${locale} ${width}px: a way into the menu exists`, burgerVisible);
      if (!burgerVisible) { await context.close(); results.push(`${width}:none`); continue; }

      await burger.click();
      await page.waitForTimeout(400);

      const drawerVisible = await page.isVisible('nav[aria-label="Mobile"]');
      check(`${locale} ${width}px: the drawer opens when its button is pressed`, drawerVisible);

      reachable = await page.$$eval('nav[aria-label="Mobile"] a', (els) =>
        els.filter((e) => e.offsetParent !== null).length);
      route = 'drawer';

      // Every link has to be *reachable*, not merely present. The body scroll
      // is locked while the drawer is open, so if the panel itself does not
      // scroll, anything past the fold is in the DOM and unreachable — which is
      // exactly what happened: the menu opened and half the site was not in it.
      const shape = await page.$eval('.drawer-panel', (el) => ({
        overflows: el.scrollHeight > el.clientHeight + 1,
        overflowY: getComputedStyle(el).overflowY,
        scrollHeight: el.scrollHeight,
        client: el.clientHeight,
      }));

      if (shape.overflows) {
        // A real wheel event, not `el.scrollTop = …`. Assigning scrollTop moves
        // an `overflow: hidden` container perfectly well, so the obvious version
        // of this check passes against the very bug it is meant to catch —
        // verified by breaking the CSS and watching it stay green. Only input
        // the browser routes through the scrolling machinery proves a reader
        // can do it.
        const box = await page.$('.drawer-panel');
        const b = await box.boundingBox();
        await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2);
        await page.mouse.wheel(0, 600);
        await page.waitForTimeout(350);
        const moved = await page.$eval('.drawer-panel', (el) => el.scrollTop);

        check(`${locale} ${width}px: the menu scrolls when a reader scrolls it`,
          moved > 0,
          `content ${shape.scrollHeight}px in ${shape.client}px, overflow-y: ${shape.overflowY}, stayed at 0`);
      }

      // The last link must land inside the panel once scrolled to, not under
      // its edge.
      await page.$eval('.drawer-panel', (el) => { el.scrollTop = el.scrollHeight; });
      await page.waitForTimeout(200);
      const lastVisible = await page.evaluate(() => {
        const links = [...document.querySelectorAll('nav[aria-label="Mobile"] a')];
        const last = links[links.length - 1];
        if (!last) return false;
        const r = last.getBoundingClientRect();
        return r.bottom <= window.innerHeight + 1 && r.top >= 0;
      });
      check(`${locale} ${width}px: the last link can be brought into view`, lastVisible);

      await page.$eval('.drawer-panel', (el) => { el.scrollTop = 0; });

      // And closes again, or it is a trap rather than a menu.
      await page.keyboard.press('Escape');
      await page.waitForTimeout(300);
      check(`${locale} ${width}px: the drawer closes`,
        !(await page.isVisible('nav[aria-label="Mobile"]')));
    }

    check(`${locale} ${width}px: the menu has links`, reachable >= 5, `${reachable} reachable`);

    // Every link goes somewhere real — a menu of dead hrefs reaches nothing.
    const hrefs = await page.$$eval(
      route === 'drawer' ? 'nav[aria-label="Mobile"] a' : '.primary-nav a',
      (els) => els.map((e) => e.getAttribute('href'))
    );
    check(`${locale} ${width}px: every link has a destination`,
      hrefs.length > 0 && hrefs.every((h) => h && h !== '#' && h.trim() !== ''),
      hrefs.filter((h) => !h || h === '#').length + ' empty');

    results.push(`${width}:${route}(${reachable})`);
    await context.close();
  }

  console.log(`  ${locale}  ${results.join('  ')}`);
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
