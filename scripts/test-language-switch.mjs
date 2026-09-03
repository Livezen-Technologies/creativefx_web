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
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
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
await page.waitForTimeout(300);
await switchTo('සිංහල');
const y = await page.evaluate(() => window.scrollY);
y > 900 ? ok(`scroll restored to ${Math.round(y)}px`) : bad(`scroll fell back to ${Math.round(y)}px`);

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
