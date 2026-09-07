import { chromium } from 'playwright';

/**
 * The help assistant, asserted in a real browser.
 *
 * The things worth pinning down are the ones a screenshot cannot show: that
 * focus goes to the input on open and back to the launcher on close, that
 * Escape works, that a real question gets a real answer out of the Authority's
 * own FAQ, that a question with no answer says so and offers a person instead
 * of inventing one, and that the thread survives a page load without surviving
 * the browser session.
 *
 * Usage: node scripts/test-assistant.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

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

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; console.log(`  ok    ${label}`); }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

const focused = (page) => page.evaluate(() => document.activeElement?.className || '');
const lastBubble = (page) =>
  page.$$eval('.assistant-bubble', (els) => (els.length ? els[els.length - 1].textContent.trim() : ''));

// ── Opening, closing, focus ─────────────────────────────────────────────────
{
  const page = await (await returningVisitor()).newPage();
  const errors = [];
  page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
  page.on('pageerror', (e) => errors.push(String(e)));

  await page.goto(`${base}/en`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(700);

  check('launcher is present', (await page.$('.assistant-launcher')) !== null);
  check('panel starts closed', !(await page.isVisible('.assistant-panel')));
  check('launcher reports collapsed',
    (await page.getAttribute('.assistant-launcher', 'aria-expanded')) === 'false');

  await page.click('.assistant-launcher');
  await page.waitForTimeout(400);
  check('panel opens', await page.isVisible('.assistant-panel'));
  check('launcher reports expanded',
    (await page.getAttribute('.assistant-launcher', 'aria-expanded')) === 'true');
  check('focus moves to the input', (await focused(page)).includes('assistant-input'));

  check('topic links point somewhere real',
    await page.$$eval('.assistant-topics .assistant-topic', (els) =>
      els.length === 3 && els.every((e) => /^https?:\/\/.+\/.+/.test(e.href))));

  await page.keyboard.press('Escape');
  await page.waitForTimeout(400);
  check('Escape closes the panel', !(await page.isVisible('.assistant-panel')));
  check('focus returns to the launcher', (await focused(page)).includes('assistant-launcher'));

  check('no console errors', errors.length === 0, errors.join(' | '));
  await page.close();
}

// ── A question it can answer ────────────────────────────────────────────────
{
  const page = await (await returningVisitor()).newPage();
  await page.goto(`${base}/en`, { waitUntil: 'networkidle' });
  await page.click('.assistant-launcher');
  await page.waitForTimeout(300);

  await page.fill('.assistant-input', 'How much is the replanting subsidy?');
  await page.click('.assistant-send');
  await page.waitForTimeout(2500);

  const answer = await lastBubble(page);
  check('a real question gets a substantial answer', answer.length > 60, `${answer.length} chars`);
  check('the answer is not the fallback', !/could not find/i.test(answer), answer.slice(0, 60));
  check('the answer offers somewhere to read more',
    await page.$$eval('.assistant-links .assistant-topic', (els) => els.length > 0));
  check('the question is echoed back',
    await page.$$eval('.assistant-bubble--you', (els) =>
      els.some((e) => e.textContent.includes('replanting subsidy'))));

  // It survives a reload, in this tab.
  await page.reload({ waitUntil: 'networkidle' });
  await page.waitForTimeout(700);
  check('the thread survives a page load', await page.isVisible('.assistant-panel'));
  check('the answer is still there', (await lastBubble(page)).length > 60);

  await page.close();
}

// ── A question it cannot answer ─────────────────────────────────────────────
{
  const page = await (await returningVisitor()).newPage();
  await page.goto(`${base}/en`, { waitUntil: 'networkidle' });
  await page.click('.assistant-launcher');
  await page.waitForTimeout(300);

  await page.fill('.assistant-input', 'qwzzx flurble nonsense');
  await page.click('.assistant-send');
  await page.waitForTimeout(2500);

  const answer = await lastBubble(page);
  check('an unanswerable question says so', /could not find/i.test(answer), answer.slice(0, 80));
  check('and offers a human instead', /contact/i.test(answer) || await page.$$eval(
    '.assistant-links .assistant-topic', (els) => els.some((e) => /contact/i.test(e.textContent))));

  await page.close();
}

// ── A new session starts clean ──────────────────────────────────────────────
{
  const context = await returningVisitor();
  const page = await context.newPage();
  await page.goto(`${base}/en`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
  check('a fresh session has no thread', !(await page.isVisible('.assistant-panel')));
  await context.close();
}

// ── Trilingual ──────────────────────────────────────────────────────────────
for (const locale of ['si']) {
  const page = await (await returningVisitor()).newPage();
  await page.goto(`${base}/${locale}`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);

  const label = await page.textContent('.assistant-launcher');
  check(`${locale}: the launcher is translated`, !/Need help/i.test(label), label.trim());

  await page.click('.assistant-launcher');
  await page.waitForTimeout(300);
  const status = await page.textContent('.assistant-status');
  check(`${locale}: the status line is translated`, !/Automated/i.test(status), status.trim());
  check(`${locale}: no unresolved language keys`,
    !(await page.$eval('.assistant-panel', (el) => /Site\.assistant\./.test(el.textContent))));

  await page.close();
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
