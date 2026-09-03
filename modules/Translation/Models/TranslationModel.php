<?php

namespace Modules\Translation\Models;

use CodeIgniter\Model;

class TranslationModel extends Model
{
    protected $table         = 'translations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['locale', 'group', 'key', 'value', 'is_custom'];

    /**
     * Editor-overridable string lookup with `en` fallback.
     */
    /**
     * Insert or update a single translation. Pass $onlyIfMissing to avoid
     * overwriting an editor's existing value (used by the importer).
     */
    /**
     * Write a value somebody typed. Always wins, and marks the row as edited so
     * that importing the language files will not undo it.
     */
    public function put(string $locale, string $group, string $key, ?string $value, bool $onlyIfMissing = false): void
    {
        $row = $this->where(['locale' => $locale, 'group' => $group, 'key' => $key])->first();

        if ($row !== null) {
            if (! $onlyIfMissing) {
                $this->update($row['id'], ['value' => $value, 'is_custom' => 1]);
            }
            return;
        }

        $this->insert([
            'locale' => $locale, 'group' => $group, 'key' => $key,
            'value'  => $value, 'is_custom' => $onlyIfMissing ? 0 : 1,
        ]);
    }

    /**
     * Import a value from a language file.
     *
     * Keeps file-managed rows in step with the files on every deploy, and
     * leaves anything a person has edited alone. Without this the two drift the
     * moment a file is reworded: the table is preferred over the files, so the
     * old string is served forever and only a bespoke migration can dislodge
     * it. That had been written six times before this existed.
     */
    public function putFromFile(string $locale, string $group, string $key, ?string $value): void
    {
        $row = $this->where(['locale' => $locale, 'group' => $group, 'key' => $key])->first();

        if ($row === null) {
            $this->insert([
                'locale' => $locale, 'group' => $group, 'key' => $key,
                'value'  => $value, 'is_custom' => 0,
            ]);
            return;
        }

        if ((int) ($row['is_custom'] ?? 0) === 1 || $row['value'] === $value) {
            return;
        }

        $this->update($row['id'], ['value' => $value]);
    }

    /** Flatten a nested language array into dot-notation keys. */
    public static function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
            if (is_array($v)) {
                $out += self::flatten($v, $key);
            } else {
                $out[$key] = (string) $v;
            }
        }
        return $out;
    }

    public function get(string $key, string $locale, string $group = 'general', ?string $default = null): ?string
    {
        $row = $this->where(['locale' => $locale, 'group' => $group, 'key' => $key])->first();
        if ($row === null && $locale !== 'en') {
            $row = $this->where(['locale' => 'en', 'group' => $group, 'key' => $key])->first();
        }

        return $row['value'] ?? $default;
    }
}
