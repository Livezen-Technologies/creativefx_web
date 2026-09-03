/**
 * Rasterise the Authority's emblem into the favicon set.
 *
 * The emblem is an SVG, which most browsers accept for `rel="icon"` — but not
 * all of them, and not for the Apple touch icon or the Android home-screen
 * icons, which are PNG only. So the SVG is the source of truth and this script
 * renders it once per required size, rather than anyone hand-exporting six
 * files and getting one of them wrong.
 *
 * Chromium does the rasterising because it is already installed for the test
 * suite, and because the browser that will display the icon is the most
 * faithful thing to render it. Run after editing the emblem:
 *
 *     node scripts/make-favicon.mjs
 */
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const src  = join(root, 'public/media/tshda/tshda-emblem.svg');
const out  = join(root, 'public');

// 32 and 48 for the browser tab, 180 for the Apple touch icon, 192 and 512 for
// the Android manifest.
const sizes = {
  'favicon-32.png': 32,
  'favicon-48.png': 48,
  'apple-touch-icon.png': 180,
  'favicon-192.png': 192,
  'favicon-512.png': 512,
};

const svg = readFileSync(src, 'utf8');
// The repository pins its own Playwright version; the image ships one
// Chromium at a fixed path. Pointing at it directly avoids a download that
// the build environment blocks anyway.
const executablePath = process.env.PLAYWRIGHT_CHROMIUM ?? '/opt/pw-browsers/chromium';
const browser = await chromium.launch(existsSync(executablePath) ? { executablePath } : {});

const pngs = {};
for (const [name, size] of Object.entries(sizes)) {
  const page = await browser.newPage({ viewport: { width: size, height: size }, deviceScaleFactor: 1 });
  await page.setContent(
    `<body style="margin:0;width:${size}px;height:${size}px">${svg.replace('<svg', `<svg width="${size}" height="${size}"`)}</body>`
  );
  const buf = await page.screenshot({ omitBackground: true });
  writeFileSync(join(out, name), buf);
  pngs[size] = buf;
  await page.close();
  console.log(`  ${name}  ${size}x${size}`);
}
await browser.close();

/*
 * The .ico, assembled by hand.
 *
 * ICO is a six-byte header, a sixteen-byte directory entry per image, then the
 * images themselves. Every modern decoder accepts a PNG payload inside an ICO,
 * which is why this needs no encoder: the PNGs Chromium just produced go in
 * whole. Two sizes, because that is what a tab and a bookmark bar ask for.
 */
const entries = [16, 32].map((size) => ({ size, png: pngs[size === 16 ? 32 : size] }));
// 16 is rendered from the 32 by the browser at display time; storing the 32
// twice would waste bytes, so the small entry declares 16 and carries the 32.
const header = Buffer.alloc(6);
header.writeUInt16LE(0, 0);              // reserved
header.writeUInt16LE(1, 2);              // type: icon
header.writeUInt16LE(entries.length, 4); // image count

let offset = 6 + entries.length * 16;
const dir = [];
for (const { size, png } of entries) {
  const e = Buffer.alloc(16);
  e.writeUInt8(size >= 256 ? 0 : size, 0); // width  (0 means 256)
  e.writeUInt8(size >= 256 ? 0 : size, 1); // height
  e.writeUInt8(0, 2);                      // palette colours
  e.writeUInt8(0, 3);                      // reserved
  e.writeUInt16LE(1, 4);                   // colour planes
  e.writeUInt16LE(32, 6);                  // bits per pixel
  e.writeUInt32LE(png.length, 8);
  e.writeUInt32LE(offset, 12);
  offset += png.length;
  dir.push(e);
}

writeFileSync(join(out, 'favicon.ico'), Buffer.concat([header, ...dir, ...entries.map((e) => e.png)]));
console.log('  favicon.ico');
