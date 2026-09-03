import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { join, extname } from 'node:path';

/**
 * Catch Tailwind utilities that are written but never generated.
 *
 * The whole class of bug is silent: the class sits in the markup, reads as
 * correct, and produces no CSS at all. `bg-brand-black/98` shipped a
 * full-screen navigation drawer with no background — every link on the page
 * showing through it — because Tailwind's opacity scale has no 98. Nothing
 * warned, and no test that loads pages or measures colour can see it.
 *
 * Two checks, both cheap:
 *
 *   1. Opacity modifiers must be on the scale (multiples of 5), or be an
 *      explicit arbitrary value like /[.98].
 *   2. Any class using the site's own colour names must actually appear in the
 *      built stylesheet — which catches the same failure from any other cause,
 *      including a template outside the content globs.
 *
 * Usage: node scripts/check-tailwind-classes.mjs
 */

const ROOTS = ['modules', 'resources/js', 'app/Views'];
const BUILD = 'public/build/assets';

// Tailwind's default opacity scale.
const VALID_OPACITY = new Set([0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60,
  65, 70, 75, 80, 85, 90, 95, 100]);

const files = [];
const walk = (dir) => {
  if (!existsSync(dir)) return;
  for (const name of readdirSync(dir)) {
    if (name === 'node_modules' || name === 'vendor' || name.startsWith('.')) continue;
    const full = join(dir, name);
    if (statSync(full).isDirectory()) walk(full);
    else if (['.php', '.js', '.html'].includes(extname(full))) files.push(full);
  }
};
ROOTS.forEach(walk);

// The built stylesheet, for check 2.
let css = '';
if (existsSync(BUILD)) {
  for (const name of readdirSync(BUILD)) {
    if (name.endsWith('.css')) css += readFileSync(join(BUILD, name), 'utf8');
  }
}
if (css === '') {
  console.log('No built CSS found — run `npm run build` first.');
  process.exit(1);
}

const problems = [];

// Utilities that take a colour and so can take an opacity modifier.
const OPACITY_RE = /\b(bg|text|border|ring|from|via|to|divide|outline|shadow|placeholder|decoration|accent|caret|fill|stroke)-([a-z0-9-]+)\/(\[[^\]]+\]|\d+)/g;

for (const file of files) {
  const src = readFileSync(file, 'utf8');
  const lines = src.split('\n');

  lines.forEach((line, i) => {
    for (const m of line.matchAll(OPACITY_RE)) {
      const [cls, , , alpha] = m;
      if (alpha.startsWith('[')) continue;              // an explicit arbitrary value is fine
      if (VALID_OPACITY.has(Number(alpha))) continue;
      problems.push(
        `${file}:${i + 1}  ${cls} — ${alpha} is not on Tailwind's opacity scale, `
        + `so this utility generates nothing. Use a multiple of 5, or /[.${alpha}].`
      );
    }
  });
}

// Check 2: the site's own colour names must reach the stylesheet.
// The variant prefix is part of the generated selector — `hover:bg-brand-red/85`
// becomes `.hover\:bg-brand-red\/85:hover`, so looking for a leading dot
// reports every hover and focus utility on the site as missing. Match the
// prefix, then check for the base class anywhere in the stylesheet.
const BRAND_RE = /\b(?:[a-z-]+:)*(bg|text|border|ring|from|via|to)-(brand-black|brand-red|brand-red-dark|forest|surface|line)(?:\/(\d+))?\b/g;
const seen = new Map();
for (const file of files) {
  const src = readFileSync(file, 'utf8');
  src.split('\n').forEach((line, i) => {
    for (const m of line.matchAll(BRAND_RE)) {
      // Store the base class, without variants, since that is what has to
      // appear in the stylesheet.
      const base = `${m[1]}-${m[2]}${m[3] ? '/' + m[3] : ''}`;
      if (!seen.has(base)) seen.set(base, `${file}:${i + 1}`);
    }
  });
}
for (const [cls, where] of seen) {
  // Tailwind escapes the slash in the selector. No leading dot: the class may
  // carry variant prefixes ahead of it.
  const needle = cls.replace('/', '\\/');
  if (!css.includes(needle)) {
    problems.push(`${where}  ${cls} — written, but no rule for it in the built CSS.`);
  }
}

console.log(`Checked ${files.length} templates against the built stylesheet.`);
if (problems.length === 0) {
  console.log('Every Tailwind utility written is a utility that exists.');
  process.exit(0);
}
console.log(`${problems.length} problem(s):`);
for (const p of problems) console.log('  ' + p);
process.exit(1);
