/**
 * Rebuilds every brand raster from one source file.
 *
 *   node scripts/gen-brand.mjs                       # uses the default source
 *   node scripts/gen-brand.mjs path/to/logo.svg      # or .png / .jpg
 *
 * The site references the mark in four places (layout head, offline page,
 * header, footer) but they all point at the same few files in
 * public/media/brand/. Replacing the logo is therefore: drop the new artwork
 * in, run this, commit. No template edits.
 *
 * Outputs:
 *   cfx-symbol.svg  the source, when the source is an SVG (copied verbatim)
 *   cfx-32.png      browser tab
 *   cfx-180.png     iOS home screen
 *   cfx-512.png     Open Graph / social share
 *
 * Rasterising through headless Chromium rather than a native image library:
 * it renders SVG exactly as the browsers do, and it is already installed here.
 */
import { chromium } from 'playwright';
import { copyFileSync, existsSync, readFileSync } from 'node:fs';
import { extname, resolve } from 'node:path';

const BRAND = new URL('../public/media/brand/', import.meta.url).pathname;
const SIZES = [32, 180, 512];

const source = resolve(process.argv[2] || `${BRAND}cfx-symbol.svg`);

if (!existsSync(source)) {
  console.error(`No such file: ${source}`);
  console.error('Pass the logo explicitly:  node scripts/gen-brand.mjs path/to/logo.svg');
  process.exit(1);
}

const ext = extname(source).toLowerCase();
const isSvg = ext === '.svg';

// Keep the vector as the canonical mark when we have one — the header and
// footer use it directly, so it stays crisp at any size.
if (isSvg && source !== `${BRAND}cfx-symbol.svg`) {
  copyFileSync(source, `${BRAND}cfx-symbol.svg`);
  console.log('cfx-symbol.svg  <- ' + source);
}

const markup = isSvg
  ? readFileSync(source, 'utf8')
  : `<img src="data:image/${ext === '.jpg' ? 'jpeg' : ext.slice(1)};base64,${readFileSync(source).toString('base64')}">`;

const browser = await chromium.launch({
  executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome',
  args: ['--no-sandbox', '--disable-gpu'],
});

for (const size of SIZES) {
  const context = await browser.newContext({ viewport: { width: size, height: size }, deviceScaleFactor: 1 });
  const page = await context.newPage();
  await page.setContent(
    `<style>html,body{margin:0;padding:0;background:transparent}
     svg,img{display:block;width:${size}px;height:${size}px;object-fit:contain}</style>${markup}`,
  );
  await page.screenshot({ path: `${BRAND}cfx-${size}.png`, omitBackground: true });
  console.log(`cfx-${size}.png`);
  await context.close();
}

await browser.close();
console.log('\nBrand rasters rebuilt. Rebuild assets (npm run build) if the SVG changed.');
