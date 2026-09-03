import { chromium } from 'playwright';

/**
 * The hero slideshow's behaviour, asserted in a real browser.
 *
 * The parts worth testing are the ones that are easy to get wrong and
 * invisible when they are: that it advances on its own, that the pause button
 * genuinely stops it (WCAG 2.2.2), that focus entering the panel stops it so
 * the picture does not change under someone reaching for the search box, and
 * that a reader who has asked for reduced motion never sees it move at all.
 *
 * Usage: node scripts/test-hero-slider.mjs [url]
 */

const url = process.argv[2] || 'http://127.0.0.1:8083/en';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; console.log(`  ok    ${label}`); }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

// Swiper's own realIndex, not the DOM position of .swiper-slide-active: in a
// looping slider the wrapper holds duplicate slides, so two different moments
// in the sequence can report the same DOM index and an assertion that the
// slide changed reads as a failure when it did not.
const activeIndex = (page) =>
  page.$eval('.hero-slider', (el) => (el.swiper ? el.swiper.realIndex : -1));

// ── No photographs is a supported state, not a broken one ───────────────────
// The `hero` folder starts empty, so this is what the site looks like until the
// Authority's photography is signed off. Assert it rather than crashing on a
// slider that is correctly absent — and stop, because there is no slideshow to
// exercise.
{
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);

  if ((await page.$('.hero-slider')) === null) {
    check('the hero still renders with no photographs', (await page.$('.hero-full')) !== null);
    check('it falls back to the plain panel',
      await page.$eval('.hero-full', (el) => el.classList.contains('hero-full--bare')));
    check('no slideshow controls are offered', (await page.$('[data-hero-controls]')) === null);
    check('the masthead is intact', (await page.$$eval('.hero-full h1', (els) => els.length)) === 1);
    check('the search box is still there',
      (await page.$('.hero-card input[type="search"]')) !== null);

    await page.close();
    await browser.close();
    console.log('\n' + `${pass} passed, ${fail} failed.`);
    console.log('The `hero` media folder is empty, so the slideshow itself was not exercised.');
    process.exit(fail === 0 ? 0 : 1);
  }

  check('slider is present', true);
  check('controls are present', (await page.$('[data-hero-controls]')) !== null);

  const dots = await page.$$('.hero-dot');
  const slides = await page.$$('.hero-slider .swiper-slide:not(.swiper-slide-duplicate)');
  check('one dot per slide', dots.length === slides.length, `${dots.length} dots, ${slides.length} slides`);

  // Next / previous
  const start = await activeIndex(page);
  await page.click('[data-hero-next]');
  await page.waitForTimeout(1100);
  const afterNext = await activeIndex(page);
  check('next advances the slide', afterNext !== start, `${start} → ${afterNext}`);

  await page.click('[data-hero-prev]');
  await page.waitForTimeout(1100);
  check('previous goes back', (await activeIndex(page)) === start);

  // A dot jumps straight to its slide.
  await page.$$eval('.hero-dot', (els) => els[2] && els[2].click());
  await page.waitForTimeout(1100);
  const dotActive = await page.$$eval('.hero-dot', (els) => els.findIndex((e) => e.classList.contains('is-active')));
  check('a dot selects its own slide', dotActive === 2, `active dot ${dotActive}`);

  // Pause really stops it. Six seconds is the autoplay delay, so wait past one.
  await page.click('[data-hero-toggle]');
  const paused = await page.$eval('[data-hero-toggle]', (el) => el.classList.contains('is-paused'));
  check('pause button reports paused', paused);
  const before = await activeIndex(page);
  await page.waitForTimeout(7500);
  check('paused slideshow does not advance', (await activeIndex(page)) === before,
    `${before} → ${await activeIndex(page)}`);

  const label = await page.$eval('[data-hero-toggle]', (el) => el.getAttribute('aria-label'));
  check('paused button offers to play', /play|ධාවනය|இயக்கு/i.test(label), label);

  // And resumes.
  await page.click('[data-hero-toggle]');
  const resumed = await activeIndex(page);
  await page.waitForTimeout(8000);
  check('resumed slideshow advances again', (await activeIndex(page)) !== resumed);

  await page.close();
}

// ── Focus inside the panel holds it still ───────────────────────────────────
{
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);

  await page.focus('.hero-card input[type="search"]');
  const before = await activeIndex(page);
  await page.waitForTimeout(7500);
  check('focus in the panel stops autoplay', (await activeIndex(page)) === before);

  await page.$eval('.hero-card input[type="search"]', (el) => el.blur());
  await page.waitForTimeout(8000);
  check('autoplay resumes when focus leaves', (await activeIndex(page)) !== before);

  await page.close();
}

// ── Reduced motion: it never moves ──────────────────────────────────────────
{
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);

  const before = await activeIndex(page);
  await page.waitForTimeout(8000);
  check('reduced motion: does not autoplay', (await activeIndex(page)) === before);

  const startsPaused = await page.$eval('[data-hero-toggle]', (el) => el.classList.contains('is-paused'));
  check('reduced motion: button starts in the play state', startsPaused);

  // Still operable by hand — the preference is about motion nobody asked for.
  await page.click('[data-hero-next]');
  await page.waitForTimeout(600);
  check('reduced motion: arrows still work', (await activeIndex(page)) !== before);

  await page.close();
}

// ── The slideshow is decoration, not content ────────────────────────────────
{
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);

  check('slider is hidden from assistive technology',
    await page.$eval('.hero-slider', (el) => el.getAttribute('aria-hidden') === 'true'));
  check('slide images carry empty alt',
    await page.$$eval('.hero-slider img', (els) => els.every((e) => e.getAttribute('alt') === '')));
  check('every control has a label',
    await page.$$eval('[data-hero-controls] button', (els) =>
      els.every((e) => (e.getAttribute('aria-label') || '').trim().length > 0)));
  check('exactly one h1 in the panel',
    await page.$$eval('.hero-full h1', (els) => els.length === 1));

  await page.close();
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed.`);
process.exit(fail === 0 ? 0 : 1);
