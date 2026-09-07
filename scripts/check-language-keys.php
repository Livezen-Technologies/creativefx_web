<?php

/**
 * Every lang() key the code asks for, checked against the files that answer.
 *
 * CodeIgniter answers a key it cannot find with the key itself, so a missing
 * translation is not an error — it is a page that quietly prints
 * "Catalog.instructors.teaches_count" at a visitor. The browser sweep catches
 * that only on pages it can reach: not the ones behind a login, not the ones
 * that render after a POST, and not an email. This reads the source instead,
 * so a key is checked whether or not anything renders it today.
 *
 * Only literal keys can be checked. A key built at runtime — lang('X.' . $k) —
 * is reported separately rather than silently skipped, because "we cannot check
 * these" is information the reader of a clean run needs to have.
 *
 *     php scripts/check-language-keys.php [locale]
 *     php scripts/check-language-keys.php --parity   compare every locale's
 *                                                    key set against English
 *
 * Exits non-zero if any literal key is missing.
 */

$root   = dirname(__DIR__);
$locale = $argv[1] ?? 'en';

// ── Parity between locales ──────────────────────────────────────────────────
//
// CodeIgniter falls back to the default locale key by key, so a half-translated
// bundle renders half a page in the wrong language and never errors. The only
// way to see it is to compare the key sets.
if ($locale === '--parity') {
    $flatten = static function (array $a, string $prefix = '') use (&$flatten): array {
        $out = [];
        foreach ($a as $k => $v) {
            $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
            if (is_array($v)) {
                $out += $flatten($v, $key);
            } else {
                $out[$key] = true;
            }
        }

        return $out;
    };

    // Same walk, but keeping the strings — the key-only version cannot compare
    // what the translations actually say.
    $flattenValues = static function (array $a, string $prefix = '') use (&$flattenValues): array {
        $out = [];
        foreach ($a as $k => $v) {
            $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
            if (is_array($v)) {
                $out += $flattenValues($v, $key);
            } else {
                $out[$key] = $v;
            }
        }

        return $out;
    };

    $locales = [];
    foreach (glob($root . '/modules/*/Language/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $locales[basename($dir)] = true;
    }
    unset($locales['en']);

    $exit = 0;
    foreach (array_keys($locales) as $other) {
        $missingTotal        = 0;
        $extraTotal          = 0;
        $lines               = [];
        $placeholderProblems = [];

        foreach (glob($root . '/modules/*/Language/en/*.php') ?: [] as $enFile) {
            $module  = basename(dirname(dirname(dirname($enFile))));
            $bundle  = basename($enFile);
            $otherFile = dirname(dirname($enFile)) . '/' . $other . '/' . $bundle;

            $enFlat = $flattenValues(require $enFile);
            $enKeys = $flatten(require $enFile);
            $count  = count($enKeys);

            if (! is_file($otherFile)) {
                $missingTotal += $count;
                $lines[] = sprintf('  %-10s %-14s no file at all — %d string(s) fall back to English', $module, $bundle, $count);

                continue;
            }

            $otherFlat = $flattenValues(require $otherFile);
            $otherKeys = $flatten(require $otherFile);
            $missing   = array_diff_key($enKeys, $otherKeys);
            $extra     = array_diff_key($otherKeys, $enKeys);

            // Placeholders must survive the translation.
            //
            // `{0}` is CodeIgniter's positional argument, and a translation that
            // drops one prints a sentence with a hole where the price was; one
            // that renumbers them puts the seat count where the course title
            // belongs. Neither errors, and neither is visible to anyone reading
            // only the English.
            foreach ($enFlat as $key => $english) {
                if (! isset($otherFlat[$key])) {
                    continue;
                }
                preg_match_all('/\{\d+\}/', (string) $english, $a);
                preg_match_all('/\{\d+\}/', (string) $otherFlat[$key], $b);
                $want = array_count_values($a[0]);
                $got  = array_count_values($b[0]);
                ksort($want);
                ksort($got);
                if ($want !== $got) {
                    $placeholderProblems[] = sprintf(
                        '  %-10s %-14s %s expects %s, %s has %s',
                        $module,
                        $bundle,
                        $key,
                        $want === [] ? 'none' : implode(' ', array_keys($want)),
                        $other,
                        $got === [] ? 'none' : implode(' ', array_keys($got))
                    );
                }
            }

            $missingTotal += count($missing);
            $extraTotal   += count($extra);

            if ($missing !== [] || $extra !== []) {
                $lines[] = sprintf(
                    '  %-10s %-14s %d of %d translated%s',
                    $module,
                    $bundle,
                    $count - count($missing),
                    $count,
                    $extra === [] ? '' : sprintf(', %d key(s) not in English: %s', count($extra), implode(', ', array_slice(array_keys($extra), 0, 5)))
                );
            }
        }

        printf("%s: %d string(s) still fall back to English%s\n", $other, $missingTotal,
            $extraTotal > 0 ? sprintf(', %d key(s) exist only in %s', $extraTotal, $other) : '');
        foreach ($lines as $line) {
            echo $line . "\n";
        }

        if ($placeholderProblems !== []) {
            printf("%d placeholder mismatch(es):\n", count($placeholderProblems));
            foreach ($placeholderProblems as $line) {
                echo $line . "\n";
            }
        }

        if ($missingTotal > 0 || $extraTotal > 0 || $placeholderProblems !== []) {
            $exit = 1;
        }
    }

    exit($exit);
}

// ── Load every language file for the locale, as file => array ───────────────
$bundles     = [];
$bundleFiles = [];
foreach (array_merge(
    glob($root . '/app/Language/' . $locale . '/*.php') ?: [],
    glob($root . '/modules/*/Language/' . $locale . '/*.php') ?: [],
) as $file) {
    $bundles[basename($file, '.php')] = require $file;
    $bundleFiles[]                    = $file;
}

$has = static function (string $key) use ($bundles): bool {
    $parts = explode('.', $key);
    $file  = array_shift($parts);
    if (! isset($bundles[$file])) {
        return false;
    }
    $node = $bundles[$file];
    foreach ($parts as $part) {
        if (! is_array($node) || ! array_key_exists($part, $node)) {
            return false;
        }
        $node = $node[$part];
    }

    // An array is a legitimate answer: the certification page's FAQ list and
    // body copy are stored as lists and iterated, so "resolves to an array"
    // means present, not missing.
    return true;
};

// ── Keys declared twice in one file ─────────────────────────────────────────
//
// PHP keeps the last of two identical array keys and says nothing, so a second
// `'session' => [...]` further down a language file silently deletes the first.
// That is not hypothetical: adding a `session` and a `waitlist` group to the
// top of Catalog.php wiped both, and the only symptom was that the new keys
// "did not exist" — which reads like a typo rather than a whole group being
// dropped on the floor.
$duplicates = [];
foreach ($bundleFiles as $file) {
    $tokens = token_get_all((string) file_get_contents($file));
    $depth  = 0;
    $seen   = [];

    for ($i = 0, $n = count($tokens); $i < $n; $i++) {
        $t = $tokens[$i];

        if ($t === '[' || (is_array($t) && $t[0] === T_ARRAY)) {
            $depth++;
        } elseif ($t === ']') {
            $depth--;
        }

        // Only the top level of the returned array. Nested groups may of course
        // repeat a key name that a sibling group also uses.
        if ($depth !== 1 || ! is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }

        $j = $i + 1;
        while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if ($j >= $n || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_DOUBLE_ARROW) {
            continue;
        }

        $key = substr($t[1], 1, -1);
        if (isset($seen[$key])) {
            $duplicates[] = sprintf(
                '%s  %s declared on line %d and again on line %d — the first is discarded',
                str_replace($root . '/', '', $file),
                $key,
                $seen[$key],
                $t[2]
            );
        }
        $seen[$key] = $t[2];
    }
}

// ── Every lang() call in the source ─────────────────────────────────────────
$missing = [];
$dynamic = [];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/modules'));
$paths = [];
foreach ($files as $f) {
    if ($f->isFile() && $f->getExtension() === 'php' && ! str_contains($f->getPathname(), '/Language/')) {
        $paths[] = $f->getPathname();
    }
}
foreach (glob($root . '/app/**/*.php') ?: [] as $f) {
    $paths[] = $f;
}

foreach ($paths as $path) {
    $src = file_get_contents($path);
    $rel = str_replace($root . '/', '', $path);

    // Tokenised rather than matched with a regular expression.
    //
    // Two things defeat a regex here, and both produced false reports before
    // this was rewritten: `lang('Catalog.bundles.' . $key . '_title')` begins
    // with a quoted literal that is not a key, and a docblock explaining how
    // lang('Group.key') works is not a call at all. The tokeniser knows a
    // string from a comment and knows where an argument ends, so it does not
    // have to guess at either.
    $tokens = token_get_all($src);
    $count  = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (! is_array($token) || $token[0] !== T_STRING || strcasecmp($token[1], 'lang') !== 0) {
            continue;
        }

        // `->lang(` and `Foo::lang(` are somebody else's method.
        $before = $i - 1;
        while ($before >= 0 && is_array($tokens[$before]) && in_array($tokens[$before][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $before--;
        }
        if ($before >= 0 && is_array($tokens[$before]) && in_array($tokens[$before][0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
            continue;
        }

        // The next significant token must be the opening parenthesis.
        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $j++;
        }
        if ($j >= $count || $tokens[$j] !== '(') {
            continue;
        }

        // Then the first argument, and then what ends it.
        $k = $j + 1;
        while ($k < $count && is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $k++;
        }
        $line = is_array($token) ? $token[2] : 0;

        $isLiteral = $k < $count && is_array($tokens[$k]) && $tokens[$k][0] === T_CONSTANT_ENCAPSED_STRING;
        if ($isLiteral) {
            $after = $k + 1;
            while ($after < $count && is_array($tokens[$after]) && in_array($tokens[$after][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $after++;
            }
            // Anything other than a comma or the closing paren means the
            // literal is only the first half of a concatenated key.
            $isLiteral = $after < $count && ($tokens[$after] === ',' || $tokens[$after] === ')');
        }

        if (! $isLiteral) {
            $dynamic[] = sprintf('%s:%d', $rel, $line);

            continue;
        }

        $key = substr($tokens[$k][1], 1, -1);
        if (! str_contains($key, '.')) {
            // lang('something') with no group is not a bundle lookup.
            continue;
        }
        if (! $has($key)) {
            $missing[] = sprintf('%s:%d  %s', $rel, $line, $key);
        }
    }
}

sort($missing);
printf("Checked %d files against %d language bundles (%s).\n", count($paths), count($bundles), $locale);

if ($dynamic !== []) {
    printf("%d lang() call(s) build their key at runtime and cannot be checked here.\n", count($dynamic));
}

if ($duplicates !== []) {
    printf("\n%d duplicated key(s):\n", count($duplicates));
    foreach ($duplicates as $line) {
        echo '  ' . $line . "\n";
    }
}

if ($missing === [] && $duplicates === []) {
    echo "No missing keys.\n";
    exit(0);
}

if ($missing === []) {
    exit(1);
}

printf("\n%d missing key(s):\n", count($missing));
foreach ($missing as $line) {
    echo '  ' . $line . "\n";
}
exit(1);
