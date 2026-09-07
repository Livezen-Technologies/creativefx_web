import { chromium } from 'playwright';

/**
 * The first-visit language chooser, asserted in a real browser.
 *
 * The behaviour that matters is all about *not* appearing: once a reader has
 * answered — by choosing or by closing — it must never ask again, and it must
 * never appear on the welcome page, whose whole job is the same question. A
 * chooser that reappears is the single thing that would make this worse than
 * not having it, and it is invisible in a screenshot.
 *
 * Usage: node scripts/test-language-modal.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; console.log(`  ok    ${label}`); }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

/** A brand new visitor: its own context, so its own empty localStorage. */
const visitor = async (viewport = { width: 1280, height: 900 }) => {
  const context = await browser.newContext({ viewport });
  return { context, page: await context.newPage() };
};

const stored = (page) => page.evaluate(() => {
  try { return localStorage.getItem('nl_locale'); } catch (e) { return 'unreadable'; }
});

// ── It asks a first-time visitor who arrived deep in the site ───────────────
{
  const { context, page } = await visitor();
  const errors = [];
  page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
  page.on('pageerror', (e) => errors.push(String(e)));

  await page.goto(`${base}/en/services`, { waitUntil: 'domcontentloaded' });

  check('not shown during the first paint', !(await page.isVisible('.lang-modal')));
  // It deliberately waits for the loading screen to clear before asking, so
  // give it longer than the preloader's own minimum display time.
  await page.waitForSelector('.lang-modal', { state: 'visible', timeout: 8000 }).catch(() => {});
  check('appears once the page is readable', await page.isVisible('.lang-modal'));
  check('the loading screen has gone by then',
    (await page.$('#preloader')) === null);

  check('offers all three languages',
    (await page.$$('.lang-option')).length === 3);
  check('each option is tagged with its own language',
    await page.$$eval('.lang-option', (els) =>
      ['en', 'si'].every((c) => els.some((e) => e.getAttribute('lang') === c))));
  check('the current language is marked',
    (await page.$$eval('.lang-option[aria-current="true"]', (els) => els.length)) === 1);
  check('it is a labelled modal dialog',
    await page.$eval('.lang-modal', (el) =>
      el.getAttribute('role') === 'dialog' &&
      el.getAttribute('aria-modal') === 'true' &&
      !!el.getAttribute('aria-labelledby')));
  check('focus is inside the dialog',
    await page.evaluate(() => !!document.activeElement?.closest('.lang-modal')));
  check('the page behind cannot scroll',
    await page.evaluate(() => getComputedStyle(document.documentElement).overflow === 'hidden'));

  // Tab cycles rather than escaping into the page behind.
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  check('Tab stays inside the dialog',
    await page.evaluate(() => !!document.activeElement?.closest('.lang-modal')));

  // Arrow keys move between options.
  await page.focus('.lang-option[lang="en"]');
  await page.keyboard.press('ArrowDown');
  check('arrow keys move between languages',
    await page.evaluate(() => document.activeElement?.getAttribute('lang') === 'si'));

  check('no console errors', errors.length === 0, errors.join(' | '));
  await context.close();
}

// ── Choosing a language moves the reader and is remembered ──────────────────
{
  const { context, page } = await visitor();
  await page.goto(`${base}/en/services?q=tea#top`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.lang-modal', { state: 'visible', timeout: 8000 });

  await page.click('.lang-option[lang="si"]');
  await page.waitForLoadState('networkidle');

  check('choosing switches the locale in place', page.url().includes('/ta/services'),
    page.url());
  check('the query string and fragment survive',
    page.url().includes('q=tea') && page.url().includes('#top'), page.url());
  check('the choice is stored', (await stored(page)) === 'si');
  check('the page is served in that language',
    (await page.getAttribute('html', 'lang')) === 'si');

  await page.waitForTimeout(3000);
  check('it does not ask again after choosing', !(await page.isVisible('.lang-modal')));
  await context.close();
}

// ── Closing it is also an answer ────────────────────────────────────────────
{
  const { context, page } = await visitor();
  await page.goto(`${base}/en/faqs`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.lang-modal', { state: 'visible', timeout: 8000 });

  await page.keyboard.press('Escape');
  await page.waitForTimeout(300);
  check('Escape closes it', !(await page.isVisible('.lang-modal')));
  check('closing stores the language already shown', (await stored(page)) === 'en');
  check('the page can scroll again',
    await page.evaluate(() => getComputedStyle(document.documentElement).overflow !== 'hidden'));

  await page.goto(`${base}/en/downloads`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000);
  check('it does not ask again on the next page', !(await page.isVisible('.lang-modal')));
  await context.close();
}

// ── The backdrop dismisses it too ───────────────────────────────────────────
{
  const { context, page } = await visitor();
  await page.goto(`${base}/en/contact`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.lang-modal', { state: 'visible', timeout: 8000 });
  await page.click('.lang-modal-backdrop', { position: { x: 20, y: 20 } });
  await page.waitForTimeout(300);
  check('clicking the backdrop closes it', !(await page.isVisible('.lang-modal')));
  await context.close();
}

// ── Never on the welcome page ───────────────────────────────────────────────
{
  const { context, page } = await visitor();
  await page.goto(`${base}/?choose=1`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000);
  check('not shown on the welcome page', (await page.$('.lang-modal')) === null);
  await context.close();
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
