import { mkdirSync, copyFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Copies the Basis/KTX2 transcoder out of three/examples into public/vendor.
 *
 * KTX2Loader fetches these at runtime from a URL (setTranscoderPath), so they
 * cannot be bundled — they have to exist as static files. Copying them at build
 * time instead of committing them keeps the transcoder locked to whichever
 * three.js version is installed; a stale committed copy silently fails to
 * decode once three is upgraded.
 */
const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const from = join(root, 'node_modules/three/examples/jsm/libs/basis');
const to   = join(root, 'public/vendor/basis');

mkdirSync(to, { recursive: true });
for (const file of ['basis_transcoder.js', 'basis_transcoder.wasm']) {
    copyFileSync(join(from, file), join(to, file));
}
console.log(`copied basis transcoder -> ${to}`);
