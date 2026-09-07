import { chromium } from 'playwright';

/**
 * The path a buyer actually takes, end to end, in a browser.
 *
 * Every part of this journey is covered somewhere else and the journey itself
 * is covered nowhere. `test-seat-inventory.mjs` proves the lock holds under
 * contention; `test-pricing.mjs` proves the currency rules; `test-course-page.mjs`
 * proves the page works. None of them buys anything, and the failures that cost
 * a booking live in the joins: a panel that adds the wrong date, a basket that
 * shows a different price from the page that fed it, a hold nobody mentions
 * that expires while somebody fetches their card.
 *
 * So this books a seat and checks that the same class, the same date and the
 * same price come out the other end — and then asks a **second visitor**, in a
 * separate browser context with its own cart, whether the seat has actually
 * gone. That last part is the point. A basket line is a row in a table; a seat
 * that is genuinely held is one nobody else can be sold, and the only way to
 * know which of the two happened is to look with different eyes.
 *
 * The seat is put back at the end, so running this does not slowly eat the
 * seeded catalogue.
 *
 * The checkout is skipped rather than failed if it is not there. This runs
 * against a site that is still being built, and a suite that goes red for a
 * page nobody has written yet trains people to ignore it.
 *
 * Usage: node scripts/test-booking.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');

/** How many catalogue entries to try before giving up on a bookable one. */
const CANDIDATES = 8;

/** InventoryService::HOLD_MINUTES. The stated deadline has to be this one. */
const HOLD_MINUTES = 15;

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};
const skip = (why) => console.log(`  SKIP  ${why}`);

/** A visitor: their own cookie jar, and the language modal already answered. */
const visitor = async () => {
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  await ctx.addInitScript(() => {
    try { localStorage.setItem('nl_locale', 'en'); } catch (e) { /* private window */ }
  });

  return ctx;
};

const path = (url) => new URL(url).pathname;

// ── The buyer ───────────────────────────────────────────────────────────────

const buyerCtx = await visitor();
const buyer = await buyerCtx.newPage();

await buyer.goto(`${base}/en/courses`, { waitUntil: 'domcontentloaded' });
const catalogue = await buyer.$$eval('a[href*="/en/course/"]', (els) => [...new Set(els.map((e) => e.href))]);
check('the catalogue lists courses', catalogue.length > 0, `${catalogue.length} links`);

/**
 * What the booking panel is offering, once Alpine has decided which tab is on.
 *
 * Everything is read out of the *visible* panel rather than the first one in
 * the document: all four modes are rendered and hidden, and reading the markup
 * rather than the panel would test a price nobody is being shown.
 */
const readPanel = () => buyer.evaluate(() => {
  const panel = [...document.querySelectorAll('aside [role="tabpanel"]')].find((p) => p.offsetParent !== null);
  if (!panel) return null;

  const form = panel.querySelector('form[action*="cart/add"]');
  if (form === null) return null;

  const id = form.querySelector('input[name="item_id"]')?.value ?? null;
  const price = panel.querySelector('span.text-3xl');
  // The date belongs to the session the button books, so it is taken from the
  // link that carries that session's id — not from "the first date listed",
  // which is the same thing right up until it is not.
  const link = id === null ? null : panel.querySelector(`a[href$="-${id}"]`);

  return {
    sessionId: id,
    sessionUrl: link ? link.href : null,
    dates: link ? link.textContent.replace(/\s+/g, ' ').trim() : null,
    price: price ? price.textContent.replace(/\s+/g, ' ').trim() : null,
    title: document.querySelector('h1')?.textContent.trim() ?? null,
  };
});

let panel = null;
let courseUrl = null;

for (const url of catalogue.slice(0, CANDIDATES)) {
  await buyer.goto(url, { waitUntil: 'domcontentloaded' });
  await buyer.waitForFunction(
    () => [...document.querySelectorAll('aside [role="tabpanel"]')].filter((p) => p.offsetParent !== null).length === 1,
    null,
    { timeout: 10_000 }
  ).catch(() => {});

  const state = await readPanel();
  if (state !== null && state.sessionId !== null && state.sessionUrl !== null && state.price !== null) {
    panel = state;
    courseUrl = url;
    break;
  }
}

if (panel === null) {
  console.log('  FAIL  no course in the catalogue offers a seat to buy');
  await browser.close();
  console.log(`\n${pass} passed, ${fail + 1} failed.`);
  process.exit(1);
}

console.log(`  buying: ${panel.title} — ${panel.dates} — ${panel.price} (session ${panel.sessionId})`);

check('the panel names the course', (panel.title ?? '') !== '');
check('the panel names the date it will book', (panel.dates ?? '') !== '');
check('the panel shows a price', /^(Rs|\$)/.test(panel.price ?? ''));

// ── The second visitor, before anything is bought ───────────────────────────
// The session page publishes the true number of free seats as the ceiling on
// its own quantity box: `max` is `InventoryService::seatsLeft()`, which counts
// unexpired holds rather than the denormalised cache. It is the only exact,
// machine-readable seat count on the public site, and it is what a second
// buyer's browser is actually told.

const otherCtx = await visitor();
const other = await otherCtx.newPage();

const seatsSeenByOther = async () => {
  await other.goto(panel.sessionUrl, { waitUntil: 'domcontentloaded' });
  const max = await other.$eval('#seat-qty', (el) => Number(el.getAttribute('max'))).catch(() => null);

  return max;
};

const seatsBefore = await seatsSeenByOther();
check('a second visitor can see how many seats are left', seatsBefore !== null && seatsBefore > 1,
  String(seatsBefore));

// The box is capped at twenty however many seats there are, so on a very large
// class the number would not move and the check below would prove nothing.
const seatCountIsTelling = seatsBefore !== null && seatsBefore > 1 && seatsBefore < 20;
if (!seatCountIsTelling) {
  skip(`the seat count on this class is ${seatsBefore}, which the quantity box caps, so it cannot be watched going down`);
}

// ── Buy one ─────────────────────────────────────────────────────────────────

await buyer.fill('aside [role="tabpanel"]:not([style*="display: none"]) input[name="qty"]', '1');
await Promise.all([
  buyer.waitForURL(/\/en\/cart$/, { timeout: 15_000 }).catch(() => {}),
  buyer.click('aside [role="tabpanel"]:not([style*="display: none"]) form[action*="cart/add"] button[type="submit"]'),
]);

check('booking a seat lands on the basket', path(buyer.url()).endsWith('/en/cart'), path(buyer.url()));

// ── The basket says what was bought ─────────────────────────────────────────

const line = await buyer.evaluate(() => {
  const article = document.querySelector('article[aria-labelledby^="line-"]');
  if (!article) return null;

  const held = article.querySelector('time[datetime]');

  return {
    title: article.querySelector('h2')?.textContent.trim() ?? null,
    detail: [...article.querySelectorAll('ul li')].map((li) => li.textContent.replace(/\s+/g, ' ').trim()),
    text: article.innerText.replace(/ /g, ' ').replace(/\s+/g, ' '),
    heldIso: held ? held.getAttribute('datetime') : null,
    heldLabel: held ? held.textContent.trim() : null,
    warning: article.querySelector('.border-brand-red\\/40')?.textContent.trim() ?? null,
  };
});

check('the basket has a line in it', line !== null);

if (line !== null) {
  check('the basket names the course that was booked', line.title === panel.title,
    `${line.title} vs ${panel.title}`);
  check('the basket names the date that was booked', line.detail.includes(panel.dates),
    `${line.detail.join(' · ')} does not contain ${panel.dates}`);
  // The price per seat, not the line total: one seat was bought, so the two
  // agree here, and comparing the per-seat figure is what catches a basket
  // that quietly repriced.
  check('the basket shows the price the course page showed', line.text.includes(panel.price),
    `${panel.price} is not in "${line.text.slice(0, 120)}…"`);
  check('the line carries no warning', line.warning === null, String(line.warning));

  // ── The hold, and its deadline ────────────────────────────────────────────
  // A fifteen-minute hold nobody mentions is a hold that runs out while
  // somebody is fetching their card, and the failure at the checkout then looks
  // like a bug rather than a policy.

  check('the line says when the seats stop being held', line.heldIso !== null);

  if (line.heldIso !== null) {
    const minutes = (Date.parse(line.heldIso) - Date.now()) / 60_000;
    // A real deadline, not a constant: it has to be about a quarter of an hour
    // away, in the future, and moving.
    check('the stated deadline is the fifteen-minute hold',
      minutes > HOLD_MINUTES - 6 && minutes <= HOLD_MINUTES + 1, `${minutes.toFixed(1)} minutes away`);

    const banner = await buyer.evaluate((clock) => [...document.querySelectorAll('p')]
      .filter((p) => p.closest('article') === null)
      .map((p) => p.textContent.replace(/\s+/g, ' ').trim())
      .find((t) => t.includes(clock)) ?? null, line.heldLabel);

    // Said once beside the line and once above the basket, because the line is
    // where it applies and the banner is where somebody reads it before
    // deciding whether to go and find their card now or later.
    check('the basket states the deadline above the lines as well', banner !== null,
      `no paragraph outside the lines mentions ${line.heldLabel}`);
  }
}

// ── The second visitor, after ───────────────────────────────────────────────

if (seatCountIsTelling) {
  const seatsAfter = await seatsSeenByOther();
  check('the seat a buyer is holding is gone from everybody else\'s view',
    seatsAfter === seatsBefore - 1, `${seatsBefore} before, ${seatsAfter} after`);
}

// ── The checkout ────────────────────────────────────────────────────────────

const checkoutLink = await buyer.$('a[href$="/en/checkout"]');

if (checkoutLink === null) {
  skip('the basket offers no route to a checkout, so the hold notice there is not checked');
} else {
  const response = await buyer.goto(`${base}/en/checkout`, { waitUntil: 'domcontentloaded' });
  const status = response === null ? 0 : response.status();

  if (status === 404) {
    skip('the checkout is not built yet (404), so the rest of the journey is not checked');
  } else if (!path(buyer.url()).endsWith('/en/checkout')) {
    skip(`the checkout sent us to ${path(buyer.url())} instead, so the rest of the journey is not checked`);
  } else {
    check('the checkout asks for the money', await buyer.$('form[action$="/en/checkout"]') !== null);
    check('the checkout still knows the price', (await buyer.innerText('body')).includes(panel.price), panel.price);

    // The buyer is about to leave for a payment provider. Whether the seats
    // survive that trip is the question they are actually asking, and the page
    // has to answer it where the button is.
    const holdNote = await buyer.evaluate(() => [...document.querySelectorAll('p, li')]
      .map((e) => e.textContent.replace(/\s+/g, ' ').trim())
      .find((t) => /seats are held|holding (?:these|your) seats|seats stay held/i.test(t)) ?? null);

    check('the checkout says the seats are held while the payment is made', holdNote !== null,
      'nothing on the page says what happens to the seats');

    if (holdNote !== null) console.log(`  hold notice: "${holdNote.slice(0, 110)}…"`);
  }
}

// ── Put it back ─────────────────────────────────────────────────────────────
// The hold would lapse on its own in a quarter of an hour, but a check that
// leaves a seeded classroom a seat short every time it runs is a check people
// stop running.

await buyer.goto(`${base}/en/cart`, { waitUntil: 'domcontentloaded' });
const remove = await buyer.$('form[action*="cart/remove"] button[type="submit"]');

if (remove !== null) {
  await Promise.all([buyer.waitForURL(/\/en\/cart$/, { timeout: 15_000 }).catch(() => {}), remove.click()]);

  const emptied = await buyer.$('article[aria-labelledby^="line-"]') === null;
  check('the line can be taken back out', emptied);

  if (seatCountIsTelling) {
    // And releasing the hold has to give the seat back, or the basket is a way
    // to take a class off sale by changing your mind.
    const seatsRestored = await seatsSeenByOther();
    check('the seat goes back on sale when the line is removed',
      seatsRestored === seatsBefore, `${seatsBefore} before, ${seatsRestored} after`);
  }
}

await otherCtx.close();
await buyerCtx.close();
await browser.close();

console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
