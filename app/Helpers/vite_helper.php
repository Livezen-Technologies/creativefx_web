<?php

/**
 * Vite asset helper.
 *
 * In production it reads the Vite manifest (public/build/.vite/manifest.json)
 * and emits hashed <link>/<script> tags. In development, when `npm run dev`
 * has written a `public/hot` file, it points at the Vite dev server for HMR.
 */
if (! function_exists('vite_dev_server')) {
    function vite_dev_server(): ?string
    {
        $hot = FCPATH . 'hot';
        if (is_file($hot)) {
            $url = trim((string) file_get_contents($hot));
            return $url !== '' ? rtrim($url, '/') : 'http://localhost:5173';
        }
        return null;
    }
}

if (! function_exists('vite_tags')) {
    function vite_tags(string $entry = 'resources/js/app.js'): string
    {
        helper('url');

        // --- Dev (HMR) ---
        if (($dev = vite_dev_server()) !== null) {
            return '<script type="module" src="' . $dev . '/@vite/client"></script>' . "\n"
                . '<script type="module" src="' . $dev . '/' . $entry . '"></script>';
        }

        // --- Production (manifest) ---
        $manifestPath = FCPATH . 'build/.vite/manifest.json';
        if (! is_file($manifestPath)) {
            return '<!-- Vite manifest not found. Run `npm run build`. -->';
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        if (! isset($manifest[$entry])) {
            return '<!-- Vite entry "' . esc($entry) . '" not found in manifest. -->';
        }

        $chunk = $manifest[$entry];
        $tags  = [];

        $addCss = static function (array $node) use (&$tags) {
            foreach ($node['css'] ?? [] as $css) {
                $tags[] = '<link rel="stylesheet" href="' . base_url('build/' . $css) . '">';
            }
        };

        foreach ($chunk['imports'] ?? [] as $import) {
            if (isset($manifest[$import])) {
                $addCss($manifest[$import]);
            }
        }
        $addCss($chunk);

        $tags[] = '<script type="module" src="' . base_url('build/' . $chunk['file']) . '"></script>';

        return implode("\n", $tags);
    }
}
