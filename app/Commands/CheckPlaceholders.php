<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Placeholder text that reaches a reader.
 *
 * Draft copy is written with gaps in it — `{effective date}`, `{to be
 * confirmed}`, `{maximum class size}` — and the gaps are meant to be filled
 * before anybody sees the page. Nothing enforced that, so five policy pages
 * told visitors they took effect on "{effective date}", the terms and the
 * privacy notice named a registered office as "{to be confirmed}", and three
 * city pages printed the same where the street address belongs.
 *
 * A curly-braced token in prose is never correct: either the fact is known and
 * should be written, or it is not known and the sentence should not claim it.
 * There is no third case, which is what makes this checkable.
 *
 * Both halves are scanned. The seed files are where a placeholder is
 * introduced; the database is what a visitor actually reads, and a fix applied
 * to one without the other comes back on the next deploy.
 *
 *     php spark check:placeholders [--seeds|--db]
 */
class CheckPlaceholders extends BaseCommand
{
    protected $group       = 'Checks';
    protected $name        = 'check:placeholders';
    protected $description = 'Fail if draft placeholder text would be shown to a visitor.';
    protected $usage       = 'check:placeholders [--seeds|--db]';

    /**
     * Curly braces appear legitimately in code samples and in language strings,
     * where `{0}` is CodeIgniter's own positional argument. Only a token of
     * lower-case words is prose, so `{0}`, `{"a":1}` and `{$var}` are all
     * excluded by requiring three or more letters and nothing else.
     */
    private const TOKEN = '/\{[a-z][a-z_ ]{2,}\}/';

    public function run(array $params): int
    {
        // Spark strips `--flags` out of $params and puts them in getOption(),
        // so reading $params for them finds nothing and every run scans both.
        $onlyDb    = CLI::getOption('db') !== null;
        $onlySeeds = CLI::getOption('seeds') !== null;

        $wantSeed = ! $onlyDb;
        $wantDb   = ! $onlySeeds;

        $problems = [];
        if ($wantSeed) {
            $problems = array_merge($problems, $this->scanSeedFiles());
        }
        if ($wantDb) {
            $problems = array_merge($problems, $this->scanDatabase());
        }

        if ($problems === []) {
            CLI::write('No placeholder text reaches a reader.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::error(sprintf('%d placeholder(s) would be shown to a visitor:', count($problems)));
        foreach ($problems as $line) {
            CLI::write('  ' . $line);
        }
        CLI::write('');
        CLI::write('Either write the fact, or rewrite the sentence so it is not claimed.', 'yellow');

        return EXIT_ERROR;
    }

    /** @return list<string> */
    private function scanSeedFiles(): array
    {
        $out = [];
        foreach (glob(ROOTPATH . 'modules/*/Database/Seeds/data', GLOB_ONLYDIR) ?: [] as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'json') {
                    continue;
                }
                if (! preg_match_all(self::TOKEN, (string) file_get_contents($file->getPathname()), $m)) {
                    continue;
                }
                $rel = str_replace(ROOTPATH, '', $file->getPathname());
                foreach (array_unique($m[0]) as $token) {
                    $out[] = sprintf('seed  %-58s %s', $rel, $token);
                }
            }
        }
        sort($out);

        return $out;
    }

    /** @return list<string> */
    private function scanDatabase(): array
    {
        $db  = db_connect();
        $out = [];

        foreach ($db->listTables() as $table) {
            if (str_starts_with($table, 'sqlite_') || $table === 'migrations') {
                continue;
            }

            $columns = [];
            foreach ($db->getFieldData($table) as $field) {
                if (preg_match('/char|text|json|clob/i', (string) ($field->type ?? ''))) {
                    $columns[] = $field->name;
                }
            }
            if ($columns === []) {
                continue;
            }

            foreach ($db->table($table)->get()->getResultArray() as $row) {
                foreach ($columns as $column) {
                    if (! isset($row[$column]) || ! preg_match_all(self::TOKEN, (string) $row[$column], $m)) {
                        continue;
                    }
                    $id = $row['slug'] ?? $row['id'] ?? '?';
                    foreach (array_unique($m[0]) as $token) {
                        $out[] = sprintf('db    %-22s %-16s %-14s %s', $table, $id, $column, $token);
                    }
                }
            }
        }
        sort($out);

        return $out;
    }
}
