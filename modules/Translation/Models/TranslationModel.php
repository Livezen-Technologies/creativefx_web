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
    public function get(string $key, string $locale, string $group = 'general', ?string $default = null): ?string
    {
        $row = $this->where(['locale' => $locale, 'group' => $group, 'key' => $key])->first();
        if ($row === null && $locale !== 'en') {
            $row = $this->where(['locale' => 'en', 'group' => $group, 'key' => $key])->first();
        }

        return $row['value'] ?? $default;
    }
}
