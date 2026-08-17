/**
 * Generates the placeholder artwork the CreativeFX site ships with.
 *
 *   node scripts/gen-placeholders.mjs
 *
 * These stand in for real photography until the client's own images are
 * uploaded through Admin -> Media. They are deliberately *abstract* — brand
 * duotone panels with a faint craft glyph — rather than stock photos, so a
 * page that still has placeholders reads as intentionally art-directed
 * instead of unfinished, and nobody ships a stranger's stock photo by
 * accident.
 *
 * SVG on purpose: ~1-2 KB each, resolution independent, no licensing, and
 * they inherit the brand palette from one place (below).
 */
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const OUT = new URL('../public/media/placeholders/', import.meta.url).pathname;

const RED = '#FFC107'; // CreativeFX amber — the accent the whole site is built on

/** Stroke glyphs, 24x24 viewBox, echoing the admin's Lucide-style set. */
const GLYPHS = {
  camera: 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
  video: 'M23 7l-7 5 7 5V7Z M14 5H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z',
  mic: 'M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3Z M19 10v2a7 7 0 0 1-14 0v-2 M12 19v4 M8 23h8',
  broadcast: 'M4.9 19.1a10 10 0 0 1 0-14.2 M19.1 4.9a10 10 0 0 1 0 14.2 M7.8 16.2a6 6 0 0 1 0-8.4 M16.2 7.8a6 6 0 0 1 0 8.4 M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
  // Lens rather than a literal aperture: concentric rings read cleanly at the
  // low opacity these panels use, where blade geometry turns to mush.
  aperture: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z M12 14.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z M8.6 6.2a7 7 0 0 0-2.4 2.4',
  person: 'M20 21v-2a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
  megaphone: 'M3 11v2a1 1 0 0 0 1 1h3l7 5V5L7 10H4a1 1 0 0 0-1 1Z M18 8a5 5 0 0 1 0 8',
  chart: 'M3 3v18h18 M7 15l4-5 3 3 5-7',
  film: 'M2 3h20v18H2Z M7 3v18 M17 3v18 M2 9h5 M2 15h5 M17 9h5 M17 15h5',
  drone: 'M12 9h0a3 3 0 0 1 3 3v0a3 3 0 0 1-3 3h0a3 3 0 0 1-3-3v0a3 3 0 0 1 3-3Z M9 12 4 7 M15 12l5-5 M9 12l-5 5 M15 12l5 5',
  light: 'M9 21h6 M10 17h4l1-4a5 5 0 1 0-7 0Z',
  play: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z M10 8.5v7l6-3.5Z',
  grid: 'M3 3h7v7H3Z M14 3h7v7h-7Z M3 14h7v7H3Z M14 14h7v7h-7Z',
};

const RATIOS = {
  hero: [1920, 900],
  wide: [1600, 900],
  card: [1200, 900],
  portrait: [900, 1200],
  square: [1000, 1000],
  banner: [1920, 640],
};

/**
 * @param {object} o
 * @param {string} o.ratio  key of RATIOS
 * @param {string} o.glyph  key of GLYPHS
 * @param {number} o.seed   shifts the glow so a grid of cards is not uniform
 * @param {number} o.warmth 0 = near-black, 1 = strong brand wash
 */
function panel({ ratio = 'card', glyph = 'aperture', seed = 0, warmth = 0.55, label = '' }) {
  const [w, h] = RATIOS[ratio] ?? RATIOS.card;
  const id = `${ratio}-${glyph}-${seed}`;

  // Deterministic spread so repeated calls stay stable across regenerations.
  const cx = 0.22 + ((seed * 0.37) % 0.62);
  const cy = 0.12 + ((seed * 0.23) % 0.5);
  const glowAlpha = (0.22 + warmth * 0.28).toFixed(3);
  const tilt = -22 + ((seed * 13) % 44);

  const g = GLYPHS[glyph] ?? GLYPHS.aperture;
  const glyphSize = Math.min(w, h) * 0.42;
  const gx = w / 2 - glyphSize / 2;
  const gy = h / 2 - glyphSize / 2;
  const scale = glyphSize / 24;

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}" role="img"${
    label ? ` aria-label="${label}"` : ' aria-hidden="true"'
  }>
  <defs>
    <linearGradient id="base-${id}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#171516"/>
      <stop offset="0.55" stop-color="#0C0B0C"/>
      <stop offset="1" stop-color="#050505"/>
    </linearGradient>
    <radialGradient id="glow-${id}" cx="${cx.toFixed(3)}" cy="${cy.toFixed(3)}" r="0.78">
      <stop offset="0" stop-color="${RED}" stop-opacity="${glowAlpha}"/>
      <stop offset="0.55" stop-color="${RED}" stop-opacity="${(glowAlpha * 0.28).toFixed(3)}"/>
      <stop offset="1" stop-color="${RED}" stop-opacity="0"/>
    </radialGradient>
    <pattern id="rule-${id}" width="34" height="34" patternUnits="userSpaceOnUse" patternTransform="rotate(${tilt})">
      <line x1="0" y1="0" x2="0" y2="34" stroke="#ffffff" stroke-opacity="0.05" stroke-width="1"/>
    </pattern>
    <linearGradient id="fade-${id}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#000" stop-opacity="0"/>
      <stop offset="1" stop-color="#000" stop-opacity="0.45"/>
    </linearGradient>
  </defs>

  <rect width="${w}" height="${h}" fill="url(#base-${id})"/>
  <rect width="${w}" height="${h}" fill="url(#glow-${id})"/>
  <rect width="${w}" height="${h}" fill="url(#rule-${id})"/>
  <g transform="translate(${gx.toFixed(1)} ${gy.toFixed(1)}) scale(${scale.toFixed(4)})"
     fill="none" stroke="#ffffff" stroke-opacity="0.16" stroke-width="1.1"
     stroke-linecap="round" stroke-linejoin="round">
    <path d="${g}"/>
  </g>
  <rect width="${w}" height="${h}" fill="url(#fade-${id})"/>
</svg>
`;
}

/** Every placeholder the seeders reference, by filename. */
const FILES = {
  // Home + section heroes
  'hero-home.svg': { ratio: 'hero', glyph: 'aperture', seed: 1, warmth: 0.9 },
  'hero-our-story.svg': { ratio: 'hero', glyph: 'film', seed: 2, warmth: 0.6 },
  'hero-services.svg': { ratio: 'hero', glyph: 'grid', seed: 3, warmth: 0.7 },
  'hero-portfolio.svg': { ratio: 'hero', glyph: 'play', seed: 4, warmth: 0.65 },
  'hero-contact.svg': { ratio: 'hero', glyph: 'broadcast', seed: 5, warmth: 0.7 },
  'hero-gear.svg': { ratio: 'hero', glyph: 'camera', seed: 6, warmth: 0.6 },

  // Service heroes + cards (one per service, glyph matched to the craft)
  'service-photography-videography.svg': { ratio: 'card', glyph: 'camera', seed: 11, warmth: 0.8 },
  'service-podcast-studio.svg': { ratio: 'card', glyph: 'mic', seed: 12, warmth: 0.7 },
  'service-live-streaming.svg': { ratio: 'card', glyph: 'broadcast', seed: 13, warmth: 0.75 },
  'service-gear-renting.svg': { ratio: 'card', glyph: 'camera', seed: 14, warmth: 0.5 },
  'service-social-media-advertising.svg': { ratio: 'card', glyph: 'megaphone', seed: 15, warmth: 0.85 },
  'service-digital-marketing.svg': { ratio: 'card', glyph: 'chart', seed: 16, warmth: 0.6 },

  'service-hero-photography-videography.svg': { ratio: 'hero', glyph: 'camera', seed: 21, warmth: 0.8 },
  'service-hero-podcast-studio.svg': { ratio: 'hero', glyph: 'mic', seed: 22, warmth: 0.7 },
  'service-hero-live-streaming.svg': { ratio: 'hero', glyph: 'broadcast', seed: 23, warmth: 0.75 },
  'service-hero-gear-renting.svg': { ratio: 'hero', glyph: 'camera', seed: 24, warmth: 0.5 },
  'service-hero-social-media-advertising.svg': { ratio: 'hero', glyph: 'megaphone', seed: 25, warmth: 0.85 },
  'service-hero-digital-marketing.svg': { ratio: 'hero', glyph: 'chart', seed: 26, warmth: 0.6 },
};

// Portfolio covers — a deck of interchangeable panels across the glyph set.
const PORTFOLIO_GLYPHS = ['play', 'camera', 'video', 'mic', 'broadcast', 'film', 'megaphone', 'chart', 'aperture', 'light', 'drone', 'grid'];
PORTFOLIO_GLYPHS.forEach((glyph, i) => {
  FILES[`project-${String(i + 1).padStart(2, '0')}.svg`] = {
    ratio: i % 3 === 2 ? 'portrait' : 'card',
    glyph,
    seed: 40 + i * 3,
    warmth: 0.4 + (i % 5) * 0.12,
  };
});

// Gear rental items.
['camera', 'aperture', 'light', 'mic', 'video', 'drone', 'broadcast', 'film', 'grid'].forEach((glyph, i) => {
  FILES[`gear-${String(i + 1).padStart(2, '0')}.svg`] = { ratio: 'square', glyph, seed: 70 + i * 5, warmth: 0.3 };
});

// Team portraits and client marks.
for (let i = 1; i <= 6; i++) {
  FILES[`team-${String(i).padStart(2, '0')}.svg`] = { ratio: 'portrait', glyph: 'person', seed: 90 + i * 7, warmth: 0.25 };
}
// Client marks are the one placeholder that is NOT a dark panel: the logo wall
// sits them on light plates (so real client logos, which are usually dark
// artwork, stay legible), and a dark panel there just reads as a black box.
// These are abstract dark marks on transparent, shaped like a logo rather than
// like a photograph.
const CLIENT_MARKS = [
  '<circle cx="60" cy="60" r="26" fill="none" stroke="#2A2A2E" stroke-width="9"/><rect x="56" y="16" width="8" height="88" rx="4" fill="#2A2A2E"/>',
  '<path d="M22 92 60 24l38 68Z" fill="none" stroke="#2A2A2E" stroke-width="9" stroke-linejoin="round"/>',
  '<rect x="20" y="20" width="36" height="36" rx="6" fill="#2A2A2E"/><rect x="64" y="20" width="36" height="36" rx="6" fill="#9A9AA2"/><rect x="20" y="64" width="36" height="36" rx="6" fill="#9A9AA2"/><rect x="64" y="64" width="36" height="36" rx="6" fill="#2A2A2E"/>',
  '<path d="M24 88V32h22a20 20 0 0 1 0 40H32" fill="none" stroke="#2A2A2E" stroke-width="9" stroke-linecap="round"/><circle cx="86" cy="80" r="12" fill="#2A2A2E"/>',
  '<path d="M20 60h80M60 20v80" stroke="#2A2A2E" stroke-width="9" stroke-linecap="round"/><circle cx="60" cy="60" r="30" fill="none" stroke="#9A9AA2" stroke-width="7"/>',
  '<path d="M24 84 48 36l24 30 24-18" fill="none" stroke="#2A2A2E" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>',
  '<rect x="22" y="34" width="76" height="52" rx="10" fill="none" stroke="#2A2A2E" stroke-width="9"/><path d="M40 60h40" stroke="#2A2A2E" stroke-width="9" stroke-linecap="round"/>',
  '<path d="M60 18 98 40v40L60 102 22 80V40Z" fill="none" stroke="#2A2A2E" stroke-width="9" stroke-linejoin="round"/>',
];

CLIENT_MARKS.forEach((mark, i) => {
  FILES[`client-${String(i + 1).padStart(2, '0')}.svg`] = { clientMark: mark };
});

/** A client logo stand-in: an abstract dark mark on transparent, for light plates. */
function clientMark(mark) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="120" height="120" role="img" aria-hidden="true">
  ${mark}
</svg>
`;
}

mkdirSync(OUT, { recursive: true });
let bytes = 0;
for (const [name, opts] of Object.entries(FILES)) {
  const svg = opts.clientMark ? clientMark(opts.clientMark) : panel(opts);
  writeFileSync(join(OUT, name), svg);
  bytes += svg.length;
}
console.log(`wrote ${Object.keys(FILES).length} placeholders (${(bytes / 1024).toFixed(1)} KB) -> public/media/placeholders/`);
