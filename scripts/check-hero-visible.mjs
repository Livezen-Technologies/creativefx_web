// Does the promoted photograph reach the screen?
//
// The assertion that markup cannot make. A hero image can be in the HTML, be
// the right file, carry the right dimensions — and paint nothing, because a
// negative z-index without a stacking context puts it under the section's own
// background, or because `.hero-aurora` (whose background stack ends in an
// opaque rgb(var(--bg))) is laid over the top of it. Both happened here, and
// both left every other check green.
//
// So: render the same page with a black hero image and with a white one. If the
// photograph is reaching the screen the hero backdrop differs. If it is hidden,
// the two are identical.
import { chromium } from 'playwright';
import { PNG } from 'pngjs';

// An absolute path, because this is invoked both from the repo root and from
// check-hero-image.php, which has already chdir'd into public/.
const [base, file] = process.argv.slice(2);
const { execFileSync } = await import('node:child_process');

const paint = (r, g, b) => execFileSync('php', ['-r', `
$im = imagecreatetruecolor(2400, 1400);
imagefilledrectangle($im, 0, 0, 2400, 1400, imagecolorallocate($im, ${r}, ${g}, ${b}));
imagejpeg($im, ${JSON.stringify(file)}, 95);
`]);

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const shot = async () => {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, colorScheme: 'dark' });
  await ctx.addCookies([{ name: 'locale', value: 'en', url: base }]);
  const page = await ctx.newPage();
  await page.goto(base + '/en', { waitUntil: 'networkidle' });
  // The splash and the chat bubble are fixed overlays covering the hero; every
  // one of my hand-rolled pixel samples read them instead of the page until I
  // removed them.
  await page.evaluate(() => {
    document.querySelectorAll('*').forEach((el) => {
      if (getComputedStyle(el).position === 'fixed') el.remove();
    });
  });
  await page.waitForTimeout(350);
  const png = PNG.sync.read(await page.screenshot({ clip: { x: 1150, y: 200, width: 60, height: 60 } }));
  await ctx.close();
  let sum = 0;
  for (let i = 0; i < png.data.length; i += 4) sum += png.data[i] + png.data[i + 1] + png.data[i + 2];
  return sum / (png.data.length / 4) / 3;
};

paint(0, 0, 0);
const dark = await shot();
paint(255, 255, 255);
const light = await shot();
await browser.close();

const delta = light - dark;
console.log(`  ${'hero backdrop over a black image'.padEnd(52)} ${dark.toFixed(1)}`);
console.log(`  ${'hero backdrop over a white image'.padEnd(52)} ${light.toFixed(1)}`);
console.log(`  ${'the photograph reaches the screen'.padEnd(52)} ${delta > 20 ? 'ok' : 'FAIL'} (delta ${delta.toFixed(1)}, needs > 20)`);
process.exit(delta > 20 ? 0 : 1);
