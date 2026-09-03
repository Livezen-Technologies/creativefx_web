/**
 * Screenshot a page of the running site, and report anything the console
 * complained about while it loaded.
 *
 *     node scripts/screenshot.mjs <url> <out.png> [width] [height] [fullPage]
 *
 * Used for reviewing a layout at a given width without a browser to hand — and
 * for catching the class of bug where a page returns 200 and renders nothing,
 * which a status-code sweep cannot see.
 */
import { existsSync } from 'node:fs';
import { chromium } from 'playwright';
const [,, url, out, width = '1440', height = '1200', full = '1'] = process.argv;
const chrome = process.env.PLAYWRIGHT_CHROMIUM ?? '/opt/pw-browsers/chromium';
const browser = await chromium.launch(existsSync(chrome) ? { executablePath: chrome } : {});
const page = await browser.newPage({ viewport: { width: +width, height: +height } });
const errors = [];
page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
page.on('pageerror', (e) => errors.push('PAGEERROR: ' + e.message));
const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
await page.waitForTimeout(1200);
await page.screenshot({ path: out, fullPage: full === '1' });
console.log('status', resp.status());
if (errors.length) console.log('console errors:\n' + errors.slice(0, 10).join('\n'));
await browser.close();
