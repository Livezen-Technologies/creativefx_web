/**
 * Build-time generator for the interactive global-presence map.
 *
 * Samples a grid over an equirectangular world and keeps the points that fall on
 * land (tested against Natural Earth 110m land geometry from `world-atlas`). The
 * output is a compact list of NORMALISED dot positions ([nx, ny] in 0..1) plus
 * the projection metadata, so both the dots and the live location markers use the
 * exact same plate-carrée maths and line up perfectly at any canvas size.
 *
 * Run: `node scripts/gen-dotmap.mjs`  → writes resources/data/world-dots.json
 * (committed; regenerate only if the grid/crop changes — no runtime dependency
 * on d3-geo / world-atlas, which stay dev-only).
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { feature } from 'topojson-client';
import { geoContains } from 'd3-geo';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');

// --- Projection (plate carrée / plain equirectangular) -----------------------
// Full longitude, latitude cropped to drop most of Antarctica and the empty
// Arctic so the map reads tight and centred.
const W = 1000;
const PPD = W / 360; // pixels per degree of longitude
const LAT_TOP = 83;
const LAT_BOTTOM = -56;
const H = (LAT_TOP - LAT_BOTTOM) * PPD;
const STEP = 8; // grid spacing in px — smaller = denser (heavier) dot field
const R = 4; // decimals kept in normalised output

const project = (lon, lat) => [((lon + 180) * PPD) / W, ((LAT_TOP - lat) * PPD) / H];

// --- Land geometry -----------------------------------------------------------
const topo = JSON.parse(readFileSync(resolve(root, 'node_modules/world-atlas/land-110m.json'), 'utf8'));
const land = feature(topo, topo.objects.land);

// --- Sample the grid ---------------------------------------------------------
const round = (n) => Math.round(n * 10 ** R) / 10 ** R;
const dots = [];
for (let py = STEP / 2; py < H; py += STEP) {
  const lat = LAT_TOP - py / PPD;
  for (let px = STEP / 2; px < W; px += STEP) {
    const lon = px / PPD - 180;
    if (geoContains(land, [lon, lat])) {
      dots.push([round(px / W), round(py / H)]);
    }
  }
}

const out = {
  meta: { w: W, h: Math.round(H), latTop: LAT_TOP, latBottom: LAT_BOTTOM, ppd: PPD, step: STEP },
  aspect: round(W / H),
  count: dots.length,
  dots,
};

const dir = resolve(root, 'resources/data');
mkdirSync(dir, { recursive: true });
writeFileSync(resolve(dir, 'world-dots.json'), JSON.stringify(out));
console.log(`wrote ${dots.length} land dots · aspect ${out.aspect} · viewBox 0 0 ${W} ${Math.round(H)}`);
