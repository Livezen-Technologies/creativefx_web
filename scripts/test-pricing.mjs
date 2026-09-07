import { chromium } from 'playwright';

/**
 * Which currency a visitor is quoted in, and what the two currencies are.
 *
 * The currency rules are easy to state and easy to get subtly wrong: Cloudflare's
 * country header decides when nobody has said otherwise, an explicit choice
 * outranks it for a year, and the choice has to survive the next page or it is
 * not a choice at all — it is a button that appears to work.
 *
 * The check that matters, though, is the last one. **No price on this site is a
 * conversion of the other.** The LKR figure is a number a human chose and can
 * defend; it is not the USD figure through this morning's rate. Runtime
 * conversion is the failure that looks like a feature: it ships as
 * Rs 148,237 on a page, a margin that drifts while nobody is watching, and a
 * price that changes between the course page and the card form. Nothing on the
 * page says which of the two you are looking at, so it is invisible until a
 * customer asks why they were charged something different.
 *
 * So this pairs the two published prices for the same session and asserts, of
 * every pair, that it is one of the pairs a person actually set — and then that
 * no single multiplier maps one column to the other. Both halves are needed. A
 * check that only compares against the table would pass a site that had quietly
 * started converting and rounding to the same numbers; a check that only looks
 * at the ratios would pass a table somebody had replaced wholesale.
 *
 * Usage: node scripts/test-pricing.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');

/**
 * The published price table, in minor units, mirroring the one in
 * `CatalogSeeder::BANDS`. Level 1 sits near the bottom of a band and level 3
 * near the top.
 *
 * This is deliberately a second copy rather than a query. The point of the
 * check is that the numbers on the site are the numbers somebody chose, and a
 * test that reads them back out of the same database proves only that a copy
 * succeeded. When prices genuinely change, this table changes with them — which
 * is the review a price change should get.
 */
const BANDS = {
  '1day': { USD: [42500, 49500, 57500], LKR: [2800000, 3500000, 4200000] },
  '2day': { USD: [69500, 79500, 94500], LKR: [4800000, 6000000, 7200000] },
  bootcamp: { USD: [119500, 149500, 174500], LKR: [8900000, 11000000, 13200000] },
  // Self-paced is never taught in a room, so it takes no classroom uplift.
  selfpaced: { USD: [4900, 9900, 14900], LKR: [450000, 850000, 1250000], roomless: true },
};

/** A seat in a room costs more than a seat at home: the room, and the lunch. */
const CLASSROOM_UPLIFT = 1.2;

/** Rounded to the currency's own step, so an uplift never yields Rs 42,371. */
const STEP = { USD: 100, LKR: 10000 };

/** How many courses to sample. Enough to cross several bands and both levels. */
const SAMPLE = 8;

/**
 * Every (USD, LKR) pair the price table can produce, as "usd:lkr" strings.
 */
const publishedPairs = (() => {
  const out = new Set();
  const uplift = (cents, currency) => Math.round(cents * CLASSROOM_UPLIFT / STEP[currency]) * STEP[currency];

  for (const band of Object.values(BANDS)) {
    for (let tier = 0; tier < 3; tier++) {
      out.add(`${band.USD[tier]}:${band.LKR[tier]}`);
      if (!band.roomless) out.add(`${uplift(band.USD[tier], 'USD')}:${uplift(band.LKR[tier], 'LKR')}`);
    }
  }

  return out;
})();

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

/**
 * A context that looks like it is browsing from one country.
 *
 * `CF-IPCountry` is what Cloudflare puts in front of this site, and it is the
 * only signal in the resolution order derived from the connection rather than
 * from something the browser volunteered.
 */
const fromCountry = async (country) => {
  const ctx = await browser.newContext({
    viewport: { width: 1280, height: 900 },
    extraHTTPHeaders: { 'CF-IPCountry': country },
  });
  await ctx.addInitScript(() => {
    try { localStorage.setItem('nl_locale', 'en'); } catch (e) { /* private window */ }
  });

  return ctx;
};

/** "Rs 28,000" or "$425.00" → minor units, or null. */
const toCents = (text) => {
  const m = (text || '').replace(/ /g, ' ').trim().match(/^(Rs|\$)\s*([\d,]+(?:\.\d{2})?)$/);

  return m === null ? null : { currency: m[1] === 'Rs' ? 'LKR' : 'USD', cents: Math.round(Number(m[2].replace(/,/g, '')) * 100) };
};

/** Which currency this page is quoting, read from the first price on it. */
const currencyOn = async (page) => {
  const found = await page.evaluate(() => {
    const m = document.body.innerText.replace(/ /g, ' ').match(/Rs [\d,]+|\$[\d,]+\.\d{2}/);

    return m === null ? null : m[0];
  });

  return found === null ? null : (found.startsWith('Rs') ? 'LKR' : 'USD');
};

// ── The header decides when nobody has said otherwise ───────────────────────

const sampleCourses = await (async () => {
  const ctx = await fromCountry('GB');
  const page = await ctx.newPage();
  await page.goto(`${base}/en/courses`, { waitUntil: 'domcontentloaded' });
  const urls = await page.$$eval('a[href*="/en/course/"]', (els) => [...new Set(els.map((e) => e.href))]);
  await ctx.close();

  return urls.slice(0, SAMPLE);
})();

check('the catalogue offers courses to price', sampleCourses.length > 0, `${sampleCourses.length} found`);

for (const [country, expected] of [['LK', 'LKR'], ['GB', 'USD']]) {
  const ctx = await fromCountry(country);
  const page = await ctx.newPage();

  for (const path of ['/en/courses', '/en/schedule']) {
    await page.goto(base + path, { waitUntil: 'domcontentloaded' });
    check(`${country} on ${path}: prices are in ${expected}`, await currencyOn(page) === expected, String(await currencyOn(page)));
  }

  if (sampleCourses.length > 0) {
    await page.goto(sampleCourses[0], { waitUntil: 'domcontentloaded' });
    check(`${country} on a course page: prices are in ${expected}`, await currencyOn(page) === expected);
  }

  await ctx.close();
}

// ── An explicit choice outranks the header, and stays chosen ────────────────
// Run in both directions on purpose. A switcher that only ever moves people
// towards dollars would pass a one-way test and still be broken for the visitor
// in London who wants to be quoted in rupees.

for (const [country, guessed, chosen] of [['LK', 'LKR', 'USD'], ['GB', 'USD', 'LKR']]) {
  const ctx = await fromCountry(country);
  const page = await ctx.newPage();

  await page.goto(`${base}/en/courses`, { waitUntil: 'domcontentloaded' });
  check(`${country}: the guess before choosing is ${guessed}`, await currencyOn(page) === guessed);

  await page.goto(`${base}/en/currency/${chosen}`, { waitUntil: 'domcontentloaded' });

  const cookie = (await ctx.cookies()).find((c) => c.name === 'mlp_currency');
  check(`${country}: the choice of ${chosen} is remembered`, cookie !== undefined && cookie.value === chosen,
    cookie === undefined ? 'no cookie was set' : cookie.value);
  // A year, because a currency is a standing preference rather than a session
  // detail. A cookie that lapses with the session quietly reverts somebody to
  // the header's guess the next morning.
  if (cookie !== undefined) {
    const days = (cookie.expires * 1000 - Date.now()) / 86_400_000;
    check(`${country}: the choice outlives the session`, days > 300, `${Math.round(days)} days`);
  }

  // Three different pages, because "survives a page change" is the whole claim
  // and a cookie read once on the page that set it proves none of it.
  for (const url of [`${base}/en/courses`, sampleCourses[0] ?? `${base}/en/courses`, `${base}/en/schedule`]) {
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    check(`${country}: ${chosen} survives ${url.replace(base, '')}`, await currencyOn(page) === chosen,
      String(await currencyOn(page)));
  }

  await ctx.close();
}

// ── The one that matters: neither price is a conversion of the other ────────

const priced = { LKR: {}, USD: {} };

for (const [country, currency] of [['LK', 'LKR'], ['GB', 'USD']]) {
  const ctx = await fromCountry(country);
  const page = await ctx.newPage();

  for (const url of sampleCourses) {
    await page.goto(url, { waitUntil: 'domcontentloaded' });

    // Keyed by session id, taken from the row's own booking form. Pairing the
    // two renders by row order would work right up until an ordering changed
    // under one currency and not the other, and then it would compare two
    // different classes and call the result a conversion.
    const rows = await page.$$eval('#dates table tbody tr', (trs) => trs.map((tr) => ({
      id: tr.querySelector('input[name="item_id"], input[name="session_id"]')?.value ?? null,
      price: (tr.querySelectorAll('td')[4]?.textContent ?? '').replace(/\s+/g, ' ').trim(),
    })));

    for (const row of rows) {
      if (row.id === null) continue;
      const money = toCents(row.price);
      if (money === null) continue;

      check(`${url.split('/').pop()} #${row.id}: the price is in ${currency}`, money.currency === currency, row.price);
      priced[currency][row.id] = money.cents;
    }
  }

  await ctx.close();
}

const ids = Object.keys(priced.USD).filter((id) => priced.LKR[id] !== undefined);
check('the same dates are priced in both currencies', ids.length > 0, `${ids.length} sessions in both`);

const ratios = [];
const seenPairs = new Map();
let unpublished = '';

for (const id of ids) {
  const usd = priced.USD[id];
  const lkr = priced.LKR[id];

  if (!publishedPairs.has(`${usd}:${lkr}`)) {
    unpublished ||= `session ${id} is priced ${usd} / ${lkr} minor units, which is not a pair in the table`;
  }

  ratios.push(lkr / usd);
  seenPairs.set(`${usd}:${lkr}`, (seenPairs.get(`${usd}:${lkr}`) ?? 0) + 1);
}

check('every published pair is one somebody set by hand', unpublished === '',
  unpublished + ' — if the prices changed on purpose, update BANDS in this file');

const lo = Math.min(...ratios);
const hi = Math.max(...ratios);

// A converted column has one ratio, give or take the rounding step. Anything
// past a few per cent cannot be a rate: it is two independent decisions.
check('no single rate maps one currency to the other',
  ratios.length > 0 && hi / lo > 1.05,
  `LKR per USD runs ${lo.toFixed(2)} to ${hi.toFixed(2)} across ${seenPairs.size} distinct pairs`);

console.log(`  ${ids.length} sessions, ${seenPairs.size} distinct price pairs, LKR per USD ${lo.toFixed(2)}–${hi.toFixed(2)}`);

// The decisive shape, when the sample happens to contain it: one rupee price
// against two different dollar prices cannot be the output of any function of
// the dollar price. Reported rather than required, because whether it turns up
// depends on which courses were sampled.
const byLkr = new Map();
for (const key of seenPairs.keys()) {
  const [usd, lkr] = key.split(':');
  byLkr.set(lkr, new Set([...(byLkr.get(lkr) ?? []), usd]));
}
for (const [lkr, usds] of byLkr) {
  if (usds.size > 1) {
    console.log(`  the same LKR ${Number(lkr) / 100} is published against USD ${[...usds].map((u) => Number(u) / 100).join(' and ')}`);
  }
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
