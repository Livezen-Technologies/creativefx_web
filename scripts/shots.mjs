/**
 * Page screenshotter for design review.
 *
 *   node scripts/shots.mjs out/dir /en /en/services /en/portfolio
 *   node scripts/shots.mjs out/dir --mobile /en
 *
 * Skips the once-per-session brand preloader, waits for fonts and lazy media,
 * scrolls the whole page so ScrollTrigger reveals have fired, then captures
 * full-page. Without the scroll pass every reveal-on-scroll section shoots
 * blank.
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';

const [outDir, ...rest] = process.argv.slice(2);
const mobile = rest.includes('--mobile');
const paths = rest.filter((a) => !a.startsWith('--'));
const base = process.env.BASE_URL || 'http://127.0.0.1:8080';

if (!outDir || paths.length === 0) {
  console.error('usage: node scripts/shots.mjs <outDir> [--mobile] <path...>');
  process.exit(1);
}

mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch({
  executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome',
  args: ['--no-sandbox', '--disable-gpu'],
});

const context = await browser.newContext({
  viewport: mobile ? { width: 390, height: 844 } : { width: 1440, height: 900 },
  deviceScaleFactor: 1,
  isMobile: mobile,
  hasTouch: mobile,
  // The site's GSAP reveals and fullpage scroller check this and fall back to
  // static rendering — otherwise a full-page shot catches half the sections
  // mid-reveal (or reversed, once we scroll back to the top).
  reducedMotion: 'reduce',
});

// Kill the preloader before any script on the page runs.
await context.addInitScript(() => {
  try {
    sessionStorage.setItem('nl_preloaded', '1');
  } catch (e) {
    /* ignore */
  }
});

for (const path of paths) {
  const page = await context.newPage();
  const url = base + path;
  const errors = [];
  // Third-party scripts (the chat widget) cannot be reached from a sandbox with
  // no outbound network, and a blocked request is not a defect in this page.
  // Filter those out or every run reports a false failure.
  const offsite = (t) => /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|ERR_INTERNET_DISCONNECTED|ERR_PROXY/.test(t);
  page.on('console', (m) => m.type() === 'error' && !offsite(m.text()) && errors.push(m.text()));
  page.on('pageerror', (e) => !offsite(String(e)) && errors.push(String(e)));

  let status = 0;
  try {
    const res = await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
    status = res ? res.status() : 0;
  } catch (e) {
    console.log(`${path}\tGOTO FAILED\t${e.message}`);
    await page.close();
    continue;
  }

  // Walk the page so scroll-triggered reveals run and lazy media loads, then
  // return to the top for the capture.
  await page.evaluate(async () => {
    const step = window.innerHeight * 0.8;
    for (let y = 0; y < document.body.scrollHeight; y += step) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 120));
    }
    window.scrollTo(0, 0);
    await new Promise((r) => setTimeout(r, 400));
  });

  // Belt and braces: anything still sitting at opacity 0 from a reveal that
  // reversed on the way back up would photograph as an empty band.
  await page.addStyleTag({
    content: `*, *::before, *::after { animation-play-state: paused !important; transition: none !important; }
              [data-reveal], .reveal, .gsap-reveal, .will-reveal { opacity: 1 !important; transform: none !important; }`,
  });
  await page.waitForTimeout(700);

  const name = (path === '/' ? 'root' : path.replace(/^\//, '').replace(/\//g, '_')) + (mobile ? '.mobile' : '') + '.png';
  await page.screenshot({ path: join(outDir, name), fullPage: true });

  const overflow = await page.evaluate(() =>
    Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
  );

  console.log(
    `${path}\t${status}\t${name}` +
      (overflow > 0 ? `\tH-OVERFLOW:${overflow}px` : '') +
      (errors.length ? `\tJS-ERRORS:${errors.length}: ${errors.slice(0, 3).join(' | ')}` : ''),
  );
  await page.close();
}

await browser.close();
