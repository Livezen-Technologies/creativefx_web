/**
 * Clause 3.15's language behaviour, checked in a real browser.
 *
 * The clause is specific — "switching preserves the reader's current page and
 * scroll position rather than returning them to the home page. Language
 * preference is remembered" — and none of it can be checked by fetching HTML:
 * the switch is a script, the scroll restore happens after paint, and the
 * preference lives in the browser. So this drives Chromium.
 *
 * Run against a site already serving on 8083:
 *     node scripts/test-language-switch.mjs
 */
import { chromium } from 'playwright';

const B = process.env.BASE_URL || 'http://127.0.0.1:8083';
import { existsSync } from 'node:fs';

// The image ships one Chromium at a fixed path; fall back to whatever
// Playwright has installed elsewhere.
const chrome = process.env.PLAYWRIGHT_CHROMIUM ?? '/opt/pw-browsers/chromium';
const browser = await chromium.launch(existsSync(chrome) ? { executablePath: chrome } : {});
/**
 * A page belonging to somebody who already uses this site.
 *
 * Seeding the stored language keeps the first-visit chooser shut. Without it
 * these checks are run against a modal dialog that locks scrolling and holds
 * focus — which is correct behaviour for a first visit and has nothing to do
 * with what this file is testing. scripts/test-language-modal.mjs covers the
 * first visit itself.
 */
const returningVisitor = async (viewport = { width: 1280, height: 900 }) => {
  const context = await browser.newContext({ viewport });
  // Only when absent: this runs on every navigation, and overwriting the key
  // each time would undo a switch the test just made and then assert it did not
  // happen.
  await context.addInitScript(() => {
    try {
      if (!localStorage.getItem('nl_locale')) localStorage.setItem('nl_locale', 'en');
    } catch (e) { /* private window */ }
  });
  return context;
};

const page = await (await returningVisitor()).newPage();
let pass = 0, fail = 0;
const ok = (m) => { console.log('  PASS  ' + m); pass++; };
const bad = (m) => { console.log('  FAIL  ' + m); fail++; };

async function switchTo(code) {
  await page.click('button[aria-label="Change language"]');
  await page.waitForTimeout(200);
  await page.click(`text=${code}`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(600);
}

console.log('== Switching keeps you on the page ==');
await page.goto(B + '/en/services/replanting-subsidy', { waitUntil: 'networkidle' });
await switchTo('සිංහල');
const p1 = new URL(page.url()).pathname;
p1 === '/si/services/replanting-subsidy'
  ? ok('/en/services/replanting-subsidy -> ' + p1)
  : bad('landed on ' + p1);

console.log('== Switching keeps the query string ==');
await page.goto(B + '/si/directory?district=Galle', { waitUntil: 'networkidle' });
await switchTo('தமிழ்');
const u2 = new URL(page.url());
(u2.pathname === '/ta/directory' && u2.search === '?district=Galle')
  ? ok('filters survive: ' + u2.pathname + u2.search)
  : bad('landed on ' + u2.pathname + u2.search);

console.log('== Switching keeps the scroll position ==');
await page.goto(B + '/en/about-us', { waitUntil: 'networkidle' });
await page.waitForTimeout(500);
await page.evaluate(() => window.scrollTo(0, 1400));
// Lenis animates the scroll, so the position 300ms later is wherever the
// animation has got to — not 1400. Waiting for it to settle is the difference
// between testing the restore and testing the easing curve.
await page.waitForFunction(() => {
  window.__last = window.__last ?? -1;
  const settled = Math.abs(window.scrollY - window.__last) < 1 && window.scrollY > 100;
  window.__last = window.scrollY;
  return settled;
}, null, { timeout: 5000, polling: 120 }).catch(() => {});
const before = await page.evaluate(() => Math.round(window.scrollY));
await switchTo('සිංහල');
const y = await page.evaluate(() => window.scrollY);
// Within a viewport of where the reader was, not to the pixel: the Sinhala
// page sets different type and is not the same height, so an exact match would
// be asserting something the clause does not ask for.
Math.abs(y - before) < 400
  ? ok(`scroll restored to ${Math.round(y)}px (left at ${before}px)`)
  : bad(`scroll fell back to ${Math.round(y)}px from ${before}px`);

console.log('== The choice is remembered at the welcome page ==');
const stored = await page.evaluate(() => localStorage.getItem('nl_locale'));
stored === 'si' ? ok('preference stored as ' + stored) : bad('preference stored as ' + stored);
await page.goto(B + '/', { waitUntil: 'networkidle' });
await page.waitForTimeout(800);
new URL(page.url()).pathname === '/si'
  ? ok('welcome page sends a returning reader straight through')
  : bad('welcome page landed on ' + new URL(page.url()).pathname);

console.log('== The page declares its own language ==');
await page.goto(B + '/ta/faqs', { waitUntil: 'networkidle' });
const lang = await page.evaluate(() => document.documentElement.lang);
lang === 'ta' ? ok('html lang="ta"') : bad('html lang="' + lang + '"');

console.log(`\n${pass} passed, ${fail} failed`);
await browser.close();
process.exit(fail ? 1 : 0);
