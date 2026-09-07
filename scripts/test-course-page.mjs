import { chromium } from 'playwright';

/**
 * The course page — the only page on this site that has to sell.
 *
 * Everything a buyer needs in order to say yes is on this one page, and every
 * part of it is a mechanism rather than a paragraph: the mode switch is Alpine,
 * the curriculum and the FAQ are `<details>`, the panel is `position: sticky`,
 * and the dates table is the one wide thing on a narrow screen. Each of those
 * is a way for the page to look finished in a screenshot and be unusable in a
 * hand.
 *
 * So this asserts the outcomes, not the markup. Can I see a different price
 * when I ask for a different mode. Does the button change with it, because
 * "Book now" under a price that is only available as a private course is a lie
 * the reader finds out about two clicks later. Are the dates real dates, in the
 * future, with a price and a seat count against each. Do the accordions open.
 * Can I reach the booking panel and press its button on a phone. And the one
 * that costs nothing to break and everything to ship: does the page scroll
 * sideways, at any of the three widths people actually hold.
 *
 * The mode switch is the reason the price checks are worth making twice. A tab
 * that changes the highlight but not the panel underneath it is the failure
 * mode of every hand-rolled tab strip, and it reads as correct in a screenshot
 * of either tab.
 *
 * Usage: node scripts/test-course-page.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');

// The phone, the tablet and the laptop. 390 is where the sticky panel stops
// being sticky and becomes an ordinary block, 768 is where the grid is still
// one column but the table is not, and 1440 is where the panel travels down its
// own column beside the content — three different layouts of the same page.
const WIDTHS = [390, 768, 1440];

/** How many catalogue entries to try before giving up on finding a full one. */
const CANDIDATES = 6;

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

/** "Rs 48,000" and "$695.00" both come back as a number and a currency. */
const parseMoney = (text) => {
  const t = (text || '').replace(/ /g, ' ').trim();
  const m = t.match(/^(Rs|\$|[A-Z]{3})\s*([\d,]+(?:\.\d{2})?)$/);

  return m === null ? null : { currency: m[1], amount: Number(m[2].replace(/,/g, '')) };
};

/**
 * The last "3 Oct 2026" in a cell, as a date.
 *
 * The last rather than the first, because a range is written "29–30 Sep 2026"
 * and "1 Sep – 3 Oct 2026": only the closing half carries the year, and it is
 * the end of the class that has to be in the future for the row to be worth
 * showing at all.
 */
const parseEndDate = (text) => {
  const all = [...(text || '').matchAll(/(\d{1,2})\s+([A-Za-z]{3})\w*\s+(\d{4})/g)];
  if (all.length === 0) return null;

  const [, d, mon, y] = all[all.length - 1];
  const parsed = Date.parse(`${d} ${mon} ${y} 23:59:59 UTC`);

  return Number.isNaN(parsed) ? null : new Date(parsed);
};

/**
 * A context with the first-visit language modal already answered.
 *
 * It is a full-screen backdrop over the page until somebody chooses, so without
 * this every "can the button be pressed" reading below says the same thing:
 * covered by the modal. `nl_locale` is what the modal writes when it is
 * dismissed, and the rest of the site reads it the same way.
 */
const freshContext = async (viewport) => {
  const ctx = await browser.newContext({ viewport });
  await ctx.addInitScript(() => {
    try { localStorage.setItem('nl_locale', 'en'); } catch (e) { /* private window */ }
  });

  return ctx;
};

const context = await freshContext({ width: 1440, height: 1000 });
const page = await context.newPage();

// ── Find a course worth testing ─────────────────────────────────────────────
// The first catalogue entry that offers more than one delivery mode. A course
// with a single mode has no switch to test, and picking one would make this
// check pass by having nothing to do.

await page.goto(`${base}/en/courses`, { waitUntil: 'domcontentloaded' });

const catalogue = await page.$$eval('a[href*="/course/"]', (els) =>
  [...new Set(els.map((e) => e.href))]);

check('the catalogue lists courses', catalogue.length > 0, `${catalogue.length} links`);

let courseUrl = null;
let modes = [];

for (const url of catalogue.slice(0, CANDIDATES)) {
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  // Alpine hides every panel but one, and until it has run they are all hidden
  // by x-cloak. Waiting for exactly one visible panel is waiting for Alpine to
  // have taken charge of the switch, which is the thing under test.
  await page.waitForFunction(
    () => [...document.querySelectorAll('aside [role="tabpanel"]')].filter((p) => p.offsetParent !== null).length === 1,
    null,
    { timeout: 10_000 }
  ).catch(() => {});

  const tabs = await page.$$eval('aside [role="tab"]', (els) => els.map((e) => e.textContent.trim()));
  if (tabs.length > 1) { courseUrl = url; modes = tabs; break; }
}

if (courseUrl === null) {
  console.log('  FAIL  no catalogue course offers more than one delivery mode, so the switch cannot be tested');
  await browser.close();
  console.log(`\n${pass} passed, ${fail + 1} failed.`);
  process.exit(1);
}

console.log(`  ${courseUrl.replace(base, '')}  modes: ${modes.join(', ')}`);

/** What the visible tab panel is currently offering. */
const panelState = () => page.evaluate(() => {
  const panel = [...document.querySelectorAll('aside [role="tabpanel"]')].find((p) => p.offsetParent !== null);
  if (!panel) return null;

  const price = panel.querySelector('span.text-3xl');
  const book = panel.querySelector('form[action*="cart/add"] button[type="submit"]');
  const quote = panel.querySelector('a[href*="request-quote"]');
  const waitlist = panel.querySelector('form[action*="waitlist"] button[type="submit"]');
  const cta = book ?? quote ?? waitlist;

  return {
    price: price ? price.textContent.trim() : null,
    // The kind matters more than the words. A price that only exists as a
    // private booking must not sit under a button that says "Book now".
    kind: book ? 'book' : quote ? 'quote' : waitlist ? 'waitlist' : 'none',
    ctaText: cta ? cta.textContent.trim() : null,
    sessionId: panel.querySelector('input[name="item_id"]')?.value ?? null,
    selected: [...document.querySelectorAll('aside [role="tab"]')]
      .filter((t) => t.getAttribute('aria-selected') === 'true').length,
  };
});

// ── The mode switch ─────────────────────────────────────────────────────────

const seen = [];

for (let i = 0; i < modes.length; i++) {
  await page.$$eval('aside [role="tab"]', (els, idx) => els[idx].click(), i);
  await page.waitForTimeout(250);

  const state = await panelState();
  check(`${modes[i]}: a panel is showing`, state !== null);
  if (state === null) continue;

  check(`${modes[i]}: exactly one tab reads as selected`, state.selected === 1, `${state.selected} selected`);
  check(`${modes[i]}: the panel offers a way to act`, state.kind !== 'none', state.ctaText ?? '(nothing)');

  // A dated mode has to price the seat it is selling. Only the private route is
  // allowed to answer "ask us", because it genuinely is a quotation.
  if (state.kind === 'book') {
    const money = parseMoney(state.price);
    check(`${modes[i]}: the price is a price`, money !== null && money.amount > 0, state.price ?? '(none)');
    check(`${modes[i]}: the button books a specific date`, state.sessionId !== null && Number(state.sessionId) > 0);
  }

  seen.push(state);
}

const prices = [...new Set(seen.map((s) => s.price).filter(Boolean))];
const kinds = [...new Set(seen.map((s) => s.kind))];

check('the price changes with the mode', prices.length > 1, prices.join(' / ') || '(none)');
check('the call to action changes with the mode', kinds.length > 1, kinds.join(' / '));

// The specific claim worth pinning: the private option is a conversation, and
// its button has to say so rather than offering a seat that cannot be bought.
const priv = seen[modes.findIndex((m) => /private/i.test(m))];
if (priv !== undefined) {
  check('the private option asks for a quote rather than offering a seat', priv.kind === 'quote', priv.kind);
}

// ── The dates table ─────────────────────────────────────────────────────────

const rows = await page.$$eval('#dates table tbody tr', (trs) => trs.map((tr) => {
  const cells = [...tr.querySelectorAll('td')].map((td) => td.innerText.replace(/\s+/g, ' ').trim());

  return {
    when: cells[0] ?? '',
    mode: cells[1] ?? '',
    seats: cells[3] ?? '',
    price: cells[4] ?? '',
    booking: tr.querySelector('form[action*="cart/add"] input[name="item_id"]')?.value ?? null,
    waitlist: tr.querySelector('form[action*="waitlist"]') !== null,
  };
}));

check('the dates table lists dates', rows.length > 0, `${rows.length} rows`);

const today = new Date();
today.setHours(0, 0, 0, 0);

let badDate = '';
let badPrice = '';
let badSeats = '';
let badAction = '';

for (const row of rows) {
  const when = parseEndDate(row.when);
  // A self-paced row has no date and says so; every other row has to carry one,
  // and it has to be a date somebody can still attend. A schedule showing last
  // month is the most common way a training site goes stale in public.
  if (when === null) {
    if (!/any time/i.test(row.when)) badDate ||= row.when || '(empty)';
  } else if (when < today) {
    badDate ||= `${row.when} is in the past`;
  }

  const money = parseMoney(row.price);
  if (money === null || money.amount <= 0) badPrice ||= row.price || '(empty)';
  if (row.seats === '') badSeats ||= row.when;
  // Every row is actionable: book it, or join the list for the next running.
  // A row that does neither is a date the reader can only look at.
  if (row.booking === null && !row.waitlist) badAction ||= row.when;
}

check('every row carries a real date, today or later', badDate === '', badDate);
check('every row carries a price', badPrice === '', badPrice);
check('every row says how many seats are left', badSeats === '', badSeats);
check('every row can be acted on', badAction === '', badAction);

// ── The accordions ──────────────────────────────────────────────────────────

/**
 * Open a closed panel and prove the content came with it.
 *
 * `details.open` alone is not enough: the attribute flips whether or not the
 * panel has anything under it, and a summary that toggles an attribute over an
 * empty box is exactly what a broken accordion looks like from the outside.
 *
 * The reading has to be `checkVisibility()` and the rendered text, not a
 * height. Chromium hides a closed `<details>` with `content-visibility`, which
 * leaves the child with a perfectly ordinary bounding box — so the obvious
 * version of this check measures 80px of invisible panel and reports a closed
 * accordion as open. `innerText` is empty until it is genuinely on screen.
 */
const openAccordion = async (selector, label, expectFirstOpen) => {
  const shape = await page.$$eval(selector, (all) => {
    const closed = all.find((d) => !d.open);
    if (!closed) return null;
    const body = closed.querySelector('div');

    return {
      index: all.indexOf(closed),
      firstOpen: all[0].open,
      shownBefore: body !== null && body.checkVisibility(),
      textBefore: body ? body.innerText.trim().length : 0,
    };
  });

  if (shape === null) {
    check(`${label}: there is a closed panel to open`, false, 'every panel is already open');

    return;
  }

  // The curriculum opens its first module so the section is not a wall of
  // closed bars; the FAQ opens none, because a question answered before it is
  // asked is just body copy. Both are deliberate, so both are asserted.
  check(`${label}: the first panel starts ${expectFirstOpen ? 'open' : 'closed'}`,
    shape.firstOpen === expectFirstOpen);
  check(`${label}: a closed panel shows nothing`, !shape.shownBefore && shape.textBefore === 0,
    `${shape.textBefore} characters visible`);

  await page.$$eval(selector, (all, i) => all[i].querySelector('summary').click(), shape.index);
  await page.waitForTimeout(300);

  const after = await page.$$eval(selector, (all, i) => {
    const body = all[i].querySelector('div');

    return {
      open: all[i].open,
      shown: body !== null && body.checkVisibility(),
      text: body ? body.innerText.trim().length : 0,
    };
  }, shape.index);

  check(`${label}: pressing the heading opens the panel`, after.open);
  check(`${label}: the opened panel has readable content in it`, after.shown && after.text > 20,
    `${after.text} characters`);
};

await openAccordion('section[aria-labelledby="curriculum"] details', 'curriculum', true);
await openAccordion('section[aria-labelledby="faq"] details', 'FAQ', false);

await context.close();

// ── The panel, and the page's width, at three sizes ─────────────────────────

for (const width of WIDTHS) {
  const ctx = await freshContext({ width, height: 900 });
  const p = await ctx.newPage();
  await p.goto(courseUrl, { waitUntil: 'domcontentloaded' });
  await p.waitForFunction(
    () => [...document.querySelectorAll('aside [role="tabpanel"]')].filter((x) => x.offsetParent !== null).length === 1,
    null,
    { timeout: 10_000 }
  ).catch(() => {});
  // The web fonts change the width of everything, and an overflow that only
  // appears once they land is still an overflow.
  await p.waitForTimeout(1200);

  const at = `${width}px`;

  const cta = await p.$('aside [role="tabpanel"]:not([style*="display: none"]) form[action*="cart/add"] button[type="submit"], aside [role="tabpanel"]:not([style*="display: none"]) a[href*="request-quote"]');
  check(`${at}: the booking panel offers its button`, cta !== null);

  if (cta !== null) {
    // `scrollIntoViewIfNeeded()` is not enough here. The page runs Lenis, which
    // takes over the scroller, and "if needed" declines to move an element that
    // is a quarter of a pixel off the bottom edge — which is exactly where this
    // button sits at 390. Centring it explicitly and then letting the smooth
    // scroll finish is the only reading that settles.
    await cta.evaluate((el) => el.scrollIntoView({ block: 'center' }));
    await p.waitForTimeout(900);

    // Present, in the viewport, and actually the thing under the pointer. A
    // button behind the sticky header is visible to Playwright, visible to a
    // screenshot, and unpressable by a person.
    const reachable = await cta.evaluate((el) => {
      const r = el.getBoundingClientRect();
      if (r.width < 1 || r.height < 1) return { ok: false, why: 'it has no size' };
      if (r.top < -1 || r.bottom > window.innerHeight + 1) {
        return { ok: false, why: `it is off-screen, top ${Math.round(r.top)} of ${window.innerHeight}` };
      }

      const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);

      return {
        ok: hit === el || el.contains(hit),
        why: hit ? `${hit.tagName.toLowerCase()} class="${String(hit.className).split(' ')[0]}" is over it` : 'nothing is there',
      };
    });

    check(`${at}: the button can be pressed`, reachable.ok, reachable.why);
  }

  // ── Sideways scroll ───────────────────────────────────────────────────────
  // Asked twice, because neither reading is sufficient on its own. `scrollWidth`
  // says the document is wider than the window; scrolling it and looking says
  // a reader can actually pan the page. The site sets `overflow-x: hidden` on
  // <html>, which suppresses the scrollbar without suppressing the scroll, so
  // the measurement that matters is the one that moves the page.
  // Two steps and a wait between them: the site runs Lenis, so a scroll is
  // animated and reading scrollX in the same turn reports 0 on a page that
  // pans perfectly well a moment later.
  await p.evaluate(() => window.scrollTo(9999, window.scrollY));
  await p.waitForTimeout(700);
  const panned = await p.evaluate(() => window.scrollX);
  await p.evaluate(() => window.scrollTo(0, window.scrollY));
  await p.waitForTimeout(700);

  const overflow = await p.evaluate((panned) => {
    const doc = document.documentElement;
    const over = doc.scrollWidth - doc.clientWidth;

    if (over <= 1 && panned <= 1) return { over, panned, culprit: null };

    // The element that *defines* the width, not the widest one, and measured
    // back at the left edge. Two things otherwise mislead: a table inside an
    // `overflow-x: auto` box is deliberately wider than the phone and its
    // bounding box says so, though it is clipped and contributes nothing; and
    // a fixed header is as wide as the window wherever the page is panned to,
    // so it looks like the offender from the far end of every scroll.
    let culprit = null;
    for (const el of document.querySelectorAll('body *')) {
      if (getComputedStyle(el).position === 'fixed') continue;
      const right = el.getBoundingClientRect().right;
      if (right > doc.clientWidth + 1 && right <= doc.scrollWidth + 1 && (culprit === null || right > culprit.right)) {
        culprit = { right: Math.round(right), tag: el.tagName.toLowerCase(), cls: String(el.className || '').slice(0, 60) };
      }
    }

    return { over, panned, culprit };
  }, panned);

  check(`${at}: the page does not scroll sideways`, overflow.over <= 1 && overflow.panned <= 1,
    `${overflow.over}px wider than the window, pans ${overflow.panned}px`
    + (overflow.culprit ? `, out to <${overflow.culprit.tag} class="${overflow.culprit.cls}">` : ''));

  await ctx.close();
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
