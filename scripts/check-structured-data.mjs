import { chromium } from 'playwright';

/**
 * What this site tells a search engine about itself.
 *
 * JSON-LD is the one part of the page nobody reads and everybody trusts. It is
 * also the only part where a mistake is not a bug but a claim: a rating nobody
 * left, a named trainer who does not exist, a price that is not the price. Rich
 * results are withdrawn for exactly those, and a manual action is not a thing
 * you notice in a screenshot review.
 *
 * So the two checks this file exists for are the last two:
 *
 *   **`aggregateRating` never appears on a course with no reviews.** The school
 *   has no learners yet. A star rating in a search result would be an invention
 *   presented to somebody deciding how to spend LKR 60,000, and it is the
 *   single easiest lie for a template to tell by accident — most course
 *   templates ship with one defaulted in. The page itself is the witness: if
 *   there is no review on it, there must be no rating under it.
 *
 *   **No `Person` is emitted for a faculty placeholder.** Every profile at
 *   launch describes a role rather than a human being, and the page says so in
 *   a bordered note at the top. `Person` is a claim about a human being. The
 *   page's own disclosure and the graph have to agree, because if they ever
 *   disagree it is the graph that is read by the machine and the page that is
 *   read by nobody.
 *
 * Everything else here is the floor: every block parses, every node carries the
 * fields its type requires, every `@id` reference resolves to a node on the
 * same page, and every price in the graph is a price the visitor can actually
 * see on it. An `offers` block quoting a figure that is nowhere on the page is
 * a bait-and-switch to a crawler.
 *
 * The pages are discovered rather than listed, so a reseed or a rename does not
 * silently reduce this to checking the home page.
 *
 * Usage: node scripts/check-structured-data.mjs [base-url]
 */

const base = (process.argv[2] || 'http://127.0.0.1:8083').replace(/\/$/, '');

/**
 * The fields without which a node is not worth emitting.
 *
 * Deliberately the minimum each type needs to be *usable*, not everything
 * schema.org allows. A checker that demands optional properties trains people
 * to add empty ones, and an empty property is worse than a missing one.
 */
const REQUIRED = {
  EducationalOrganization: ['name', 'url'],
  Organization: ['name', 'url'],
  WebSite: ['name', 'url'],
  Course: ['name', 'description', 'url', 'provider'],
  CourseInstance: ['courseMode'],
  EducationEvent: ['name', 'startDate', 'endDate', 'eventAttendanceMode', 'location', 'organizer'],
  Event: ['name', 'startDate', 'location'],
  FAQPage: ['mainEntity'],
  Question: ['name', 'acceptedAnswer'],
  Answer: ['text'],
  BreadcrumbList: ['itemListElement'],
  ItemList: ['itemListElement'],
  ListItem: ['position', 'name'],
  Offer: ['price', 'priceCurrency', 'availability', 'url'],
  Person: ['name'],
  Place: ['name'],
  VirtualLocation: ['url'],
  AggregateRating: ['ratingValue', 'reviewCount'],
};

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
await context.addInitScript(() => {
  // The first-visit language modal covers the page; it changes no markup, but
  // dismissing it keeps the visible-text reads below honest.
  try { localStorage.setItem('nl_locale', 'en'); } catch (e) { /* private window */ }
});
const page = await context.newPage();

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

/** The first link on the current page matching a shape, or null. */
const firstLink = async (pattern) => {
  const found = await page.$$eval('a[href]', (els, re) =>
    els.map((e) => e.href).find((h) => new RegExp(re).test(h)) ?? null, pattern.source);

  return found;
};

/** money() from the commerce helper, in reverse, so a price can be looked for. */
const asShown = (price, currency) => {
  const amount = Number(price);
  if (Number.isNaN(amount)) return null;

  const group = (n, decimals) => n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

  // LKR drops the decimals when there are none to show, exactly as money()
  // does; USD always keeps them.
  return currency === 'LKR'
    ? 'Rs ' + group(amount, Number.isInteger(amount) ? 0 : 2)
    : currency === 'USD' ? '$' + group(amount, 2) : `${currency} ${group(amount, 2)}`;
};

/** Every node carrying an @type, flattened out of a graph of any depth. */
const nodesOf = (value, out = []) => {
  if (Array.isArray(value)) { value.forEach((v) => nodesOf(v, out)); return out; }
  if (value === null || typeof value !== 'object') return out;
  if (typeof value['@type'] === 'string') out.push(value);
  Object.values(value).forEach((v) => nodesOf(v, out));

  return out;
};

// ── Work out what to look at ────────────────────────────────────────────────

await page.goto(`${base}/en/courses`, { waitUntil: 'domcontentloaded' });
const categoryUrl = await firstLink(/\/en\/courses\/[a-z0-9-]+$/);
const courseUrl = await firstLink(/\/en\/course\/[a-z0-9-]+$/);

await page.goto(`${base}/en/schedule`, { waitUntil: 'domcontentloaded' });
const sessionUrl = await firstLink(/\/en\/schedule\/[a-z0-9-]+-\d+$/);

await page.goto(`${base}/en/certificates`, { waitUntil: 'domcontentloaded' });
const bundleUrl = await firstLink(/\/en\/certificates\/[a-z0-9-]+$/);

await page.goto(`${base}/en/blog`, { waitUntil: 'domcontentloaded' });
// Both shapes, because the blog is the News module mounted at another address
// and the two have disagreed before. Whatever the index links to is what a
// reader clicks, so it is what gets walked.
const articleUrl = await firstLink(/\/en\/(?:blog|news)\/[a-z0-9-]+$/);

await page.goto(`${base}/en/instructors`, { waitUntil: 'domcontentloaded' });
const instructorUrl = await firstLink(/\/en\/instructors\/[a-z0-9-]+$/);

const targets = [
  { label: 'home', url: `${base}/en`, expect: ['EducationalOrganization', 'WebSite'] },
  { label: 'catalogue', url: `${base}/en/courses`, expect: ['ItemList', 'BreadcrumbList'] },
  { label: 'category', url: categoryUrl, expect: ['ItemList', 'BreadcrumbList'] },
  { label: 'course', url: courseUrl, expect: ['Course', 'BreadcrumbList'] },
  { label: 'session', url: sessionUrl, expect: ['EducationEvent', 'BreadcrumbList'] },
  { label: 'bundle', url: bundleUrl, expect: ['Course', 'BreadcrumbList'] },
  { label: 'faculty', url: `${base}/en/instructors`, expect: ['BreadcrumbList'] },
  { label: 'profile', url: instructorUrl, expect: ['BreadcrumbList'] },
  // The blog carries no graph today. Listed anyway: what it emits still has to
  // parse, and the walk is the thing that would notice the day it starts
  // emitting an Article with half its fields empty.
  { label: 'article', url: articleUrl, expect: [] },
];

for (const target of targets) {
  check(`${target.label}: a page was found to check`, target.url !== null);
}

// ── Walk them ───────────────────────────────────────────────────────────────

for (const target of targets) {
  if (target.url === null) continue;

  const at = target.label;
  const response = await page.goto(target.url, { waitUntil: 'domcontentloaded' });
  const status = response === null ? 0 : response.status();

  // A page that does not exist has no structured data worth discussing. Failed
  // rather than skipped, because the address came off a page on this site: a
  // 404 here means something is linking to it, which is worse news than the
  // missing graph and would be hidden by routing around it.
  if (status !== 200) {
    check(`${at}: the page this site links to exists`, false,
      `HTTP ${status} at ${target.url.replace(base, '')}`);
    continue;
  }

  await page.waitForTimeout(400);

  const raw = await page.$$eval('script[type="application/ld+json"]', (els) => els.map((e) => e.textContent ?? ''));
  const bodyText = await page.evaluate(() => document.body.innerText.replace(/ /g, ' '));

  const graphs = [];
  raw.forEach((text, i) => {
    try {
      graphs.push(JSON.parse(text));
    } catch (e) {
      check(`${at}: block ${i + 1} is valid JSON`, false, `${e.message} in "${text.slice(0, 80)}…"`);
    }
  });

  const nodes = nodesOf(graphs);
  const types = nodes.map((n) => n['@type']);

  console.log(`  ${at.padEnd(9)} ${raw.length} block(s), ${nodes.length} nodes: ${[...new Set(types)].join(', ') || '(none)'}`);

  for (const wanted of target.expect) {
    check(`${at}: the graph carries a ${wanted}`, types.includes(wanted), types.join(', ') || '(nothing)');
  }

  if (nodes.length === 0) continue;

  // Every block must declare its vocabulary, or the types in it mean nothing.
  for (const graph of graphs) {
    check(`${at}: the block names schema.org as its context`,
      String(graph['@context'] ?? '').includes('schema.org'), String(graph['@context'] ?? '(none)'));
  }

  // ── Required fields, and no empty ones ────────────────────────────────────

  for (const node of nodes) {
    const type = node['@type'];
    const needs = REQUIRED[type];
    if (needs === undefined) continue;

    const missing = needs.filter((f) => {
      const v = node[f];

      return v === undefined || v === null || v === '' || (Array.isArray(v) && v.length === 0);
    });

    check(`${at}: ${type} carries what it needs`, missing.length === 0, `missing ${missing.join(', ')}`);
  }

  // An empty string in structured data is a promise of a fact that is not
  // there — a blank telephone is worse than no telephone, because it is
  // published as if it were one.
  const empties = [];
  const findEmpty = (v, path) => {
    if (Array.isArray(v)) return v.forEach((x, i) => findEmpty(x, `${path}[${i}]`));
    if (v !== null && typeof v === 'object') return Object.entries(v).forEach(([k, x]) => findEmpty(x, `${path}.${k}`));
    if (v === '' || v === null) empties.push(path);
  };
  findEmpty(graphs, at);
  check(`${at}: nothing is published as an empty value`, empties.length === 0, empties.slice(0, 4).join(', '));

  // ── References resolve ────────────────────────────────────────────────────
  // `provider: {"@id": …}` is only worth anything if that id is a node on this
  // page. A dangling reference is a graph with a hole in it, and it fails
  // quietly: the block still validates, and the provider simply is not there.

  const defined = new Set(nodes.map((n) => n['@id']).filter(Boolean));
  const refs = [];
  const findRefs = (v) => {
    if (Array.isArray(v)) return v.forEach(findRefs);
    if (v === null || typeof v !== 'object') return;
    const keys = Object.keys(v);
    if (keys.length === 1 && keys[0] === '@id') refs.push(v['@id']);
    Object.values(v).forEach(findRefs);
  };
  findRefs(graphs);

  const unresolved = refs.filter((r) => !defined.has(r));
  check(`${at}: every @id reference points at something on the page`,
    unresolved.length === 0, [...new Set(unresolved)].join(', '));

  // ── Breadcrumbs ───────────────────────────────────────────────────────────

  for (const crumbs of nodes.filter((n) => n['@type'] === 'BreadcrumbList')) {
    const items = crumbs.itemListElement ?? [];
    const positions = items.map((i) => i.position);
    const consecutive = positions.every((p, i) => p === i + 1);

    check(`${at}: the breadcrumb is numbered from one, in order`, consecutive, positions.join(','));
    // Everything but the last step is a link. The last one is the page the
    // reader is on, and an item pointing at itself adds nothing.
    check(`${at}: every breadcrumb but the last is a link`,
      items.slice(0, -1).every((i) => typeof i.item === 'string' && i.item.startsWith('http')),
      items.map((i) => i.item ?? '—').join(' / '));
  }

  // ── Dates ─────────────────────────────────────────────────────────────────

  for (const node of nodes.filter((n) => n.startDate !== undefined)) {
    const start = Date.parse(node.startDate);
    const end = node.endDate === undefined ? start : Date.parse(node.endDate);

    check(`${at}: ${node['@type']} dates parse`, !Number.isNaN(start) && !Number.isNaN(end),
      `${node.startDate} → ${node.endDate}`);
    check(`${at}: ${node['@type']} does not end before it starts`, end >= start,
      `${node.startDate} → ${node.endDate}`);
  }

  // ── Prices ────────────────────────────────────────────────────────────────
  // The rule the Schema library states about itself: nothing is emitted that is
  // not true on the page. A price a crawler is given and a reader cannot find
  // is the definition of a mismatch.

  const offers = nodes.filter((n) => n['@type'] === 'Offer');
  const currencies = [...new Set(offers.map((o) => o.priceCurrency))];

  if (offers.length > 0) {
    check(`${at}: every offer is quoted in one currency`, currencies.length === 1, currencies.join(', '));

    for (const offer of offers) {
      const shown = asShown(offer.price, offer.priceCurrency);
      check(`${at}: the offer price is a number`, shown !== null, String(offer.price));
      if (shown === null) continue;

      check(`${at}: ${shown} is a price the reader can see on the page`, bodyText.includes(shown), `${offer.url ?? ''}`);
      check(`${at}: the offer says whether it is available`,
        String(offer.availability ?? '').startsWith('https://schema.org/'), String(offer.availability));
    }

    // And the currency the graph quotes has to be the currency the page is in.
    const visible = bodyText.match(/Rs [\d,]+|\$[\d,]+\.\d{2}/);
    if (visible !== null) {
      const pageCurrency = visible[0].startsWith('Rs') ? 'LKR' : 'USD';
      check(`${at}: the graph quotes the currency the page is priced in`,
        currencies[0] === pageCurrency, `graph ${currencies[0]}, page ${pageCurrency}`);
    }
  }

  // ── FAQs ──────────────────────────────────────────────────────────────────

  for (const faq of nodes.filter((n) => n['@type'] === 'FAQPage')) {
    const questions = faq.mainEntity ?? [];
    check(`${at}: the FAQ graph has questions in it`, questions.length > 0);
    check(`${at}: every question has an answer with words in it`,
      questions.every((q) => (q.acceptedAnswer?.text ?? '').trim().length > 0),
      `${questions.filter((q) => !(q.acceptedAnswer?.text ?? '').trim()).length} empty`);
    // The answer a crawler is shown must be the answer on the page. A FAQPage
    // whose text was written for the graph alone is the oldest trick there is.
    check(`${at}: the questions are the ones on the page`,
      questions.every((q) => bodyText.includes(String(q.name).trim())),
      questions.map((q) => q.name).filter((n) => !bodyText.includes(String(n).trim())).slice(0, 2).join(' | '));
  }

  // ── The rating that must not exist ────────────────────────────────────────

  const courses = nodes.filter((n) => n['@type'] === 'Course');
  if (courses.length > 0) {
    // The page is the witness. `#reviews` holds one blockquote per published
    // review and an honest sentence when there are none, so counting them is
    // asking the same question a reader asks.
    const reviewsOnPage = await page.$$eval('#reviews blockquote', (els) => els.length).catch(() => 0);
    const rated = courses.filter((c) => c.aggregateRating !== undefined);

    if (reviewsOnPage === 0) {
      check(`${at}: no rating is claimed for a course nobody has reviewed`,
        rated.length === 0,
        rated.map((c) => `${c.name}: ${JSON.stringify(c.aggregateRating)}`).join(' | '));
    } else {
      // The other direction matters too. Real reviews that never reach the
      // graph are a rich result the school has earned and is not getting.
      check(`${at}: the ${reviewsOnPage} reviews on the page are declared`, rated.length > 0);
      for (const c of rated) {
        const count = Number(c.aggregateRating.reviewCount);
        const value = Number(c.aggregateRating.ratingValue);
        check(`${at}: the rating counts real reviews`, count >= reviewsOnPage, `${count} claimed, ${reviewsOnPage} shown`);
        check(`${at}: the rating is on the scale it declares`,
          value >= Number(c.aggregateRating.worstRating ?? 1) && value <= Number(c.aggregateRating.bestRating ?? 5),
          String(value));
      }
    }
  }

  // ── The people who must not be invented ───────────────────────────────────

  const people = nodes.filter((n) => n['@type'] === 'Person').map((n) => String(n.name).trim());

  // A profile page discloses its own placeholder status in a bordered note at
  // the top of the body; a card in the faculty list carries a chip. Reading the
  // page rather than the database is the point: the disclosure a human sees and
  // the claim a crawler reads have to be the same claim.
  const placeholders = await page.evaluate(() => {
    const names = new Set();

    if (document.querySelector('#profile-note') !== null) {
      const heading = document.querySelector('h1');
      if (heading) names.add(heading.textContent.trim());
    }

    for (const card of document.querySelectorAll('article')) {
      const chip = [...card.querySelectorAll('.chip')].some((c) => /faculty profile/i.test(c.textContent));
      const name = card.querySelector('h2, h3');
      if (chip && name) names.add(name.textContent.trim());
    }

    return [...names];
  });

  if (placeholders.length > 0) {
    const claimed = placeholders.filter((n) => people.includes(n));
    check(`${at}: no placeholder profile is published as a person`, claimed.length === 0, claimed.join(', '));
    console.log(`  ${''.padEnd(9)} ${placeholders.length} placeholder profile(s) on the page, ${people.length} Person node(s)`);
  }

  // A profile with no disclosure is a real trainer, and a real trainer should
  // be in the graph — the rule cuts both ways, or "emit nothing, ever" would
  // pass it.
  if (at === 'profile' && placeholders.length === 0) {
    check(`${at}: a named trainer is published as a person`, people.length > 0);
  }
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
