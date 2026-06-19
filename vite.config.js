import { defineConfig } from 'vite';
import { resolve } from 'path';

// Vite builds the frontend bundle into CodeIgniter's web root (public/build).
// The PHP `vite_tags()` helper (app/Helpers/vite_helper.php) reads the manifest
// to emit hashed <script>/<link> tags, and switches to the dev server when a
// `public/hot` file is present (written by `npm run dev`).
export default defineConfig({
  base: '/build/',
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: resolve(__dirname, 'resources/js/app.js'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
});
