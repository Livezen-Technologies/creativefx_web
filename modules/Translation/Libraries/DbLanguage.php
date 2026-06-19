<?php

namespace Modules\Translation\Libraries;

use CodeIgniter\Language\Language;

/**
 * Database-backed language overrides. For any `lang('Group.key')` call, this
 * checks the `translations` table first (current locale); if an editor has set
 * a value it wins, otherwise it falls back to the file-based language strings.
 *
 * This makes every existing UI string editable from the Translation Manager
 * with zero changes to views.
 */
class DbLanguage extends Language
{
    /** @var array<string, array<string,string>> locale => ["group.key" => value] */
    private array $dbCache = [];

    /** @var array<string,bool> */
    private array $dbLoaded = [];

    public function getLine(string $line, array $args = [])
    {
        if (str_contains($line, '.')) {
            [$group, $key] = explode('.', $line, 2);
            $value = $this->fromDb($this->locale, $group, $key);

            if ($value !== null) {
                return ($args === []) ? $value : $this->formatDb($value, $args);
            }
        }

        return parent::getLine($line, $args);
    }

    private function fromDb(string $locale, string $group, string $key): ?string
    {
        $this->ensureLoaded($locale);
        return $this->dbCache[$locale][$group . '.' . $key] ?? null;
    }

    private function ensureLoaded(string $locale): void
    {
        if (isset($this->dbLoaded[$locale])) {
            return;
        }
        $this->dbLoaded[$locale] = true;
        $this->dbCache[$locale]  = [];

        try {
            $rows = db_connect()->table('translations')->where('locale', $locale)->get()->getResultArray();
            foreach ($rows as $r) {
                if (($r['value'] ?? '') !== '') {
                    $this->dbCache[$locale][$r['group'] . '.' . $r['key']] = $r['value'];
                }
            }
        } catch (\Throwable $e) {
            // translations table not available yet — fall back to files.
        }
    }

    private function formatDb(string $message, array $args): string
    {
        if (class_exists('MessageFormatter')) {
            $formatted = \MessageFormatter::formatMessage($this->locale, $message, $args);
            if ($formatted !== false) {
                return $formatted;
            }
        }
        return $message;
    }
}
