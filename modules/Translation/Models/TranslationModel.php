<?php

namespace Modules\Translation\Models;

use CodeIgniter\Model;

class TranslationModel extends Model
{
    protected $table         = 'translations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['locale', 'group', 'key', 'value'];

    /**
     * Editor-overridable string lookup with `en` fallback.
     */
    /**
     * Insert or update a single translation. Pass $onlyIfMissing to avoid
     * overwriting an editor's existing value (used by the importer).
     */
    public function put(string $locale, string $group, string $key, ?string $value, bool $onlyIfMissing = false): void
    {
        $row = $this->where(['locale' => $locale, 'group' => $group, 'key' => $key])->first();

        if ($row !== null) {
            if (! $onlyIfMissing) {
                $this->update($row['id'], ['value' => $value]);
            }
            return;
        }

        $this->insert(['locale' => $locale, 'group' => $group, 'key' => $key, 'value' => $value]);
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
