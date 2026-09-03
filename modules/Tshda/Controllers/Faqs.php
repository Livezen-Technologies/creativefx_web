<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Models\FaqModel;

/**
 * Categorised, searchable frequently asked questions (Clause 3.9 K).
 */
class Faqs extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $query  = trim((string) $this->request->getGet('q'));
        $model  = new FaqModel();
        $groups = $model->grouped();

        // Searching filters the same grouped structure rather than flattening
        // it, so a result set still tells you which part of the Authority's
        // work the answer comes from.
        if ($query !== '') {
            $needle = mb_strtolower($query);
            foreach ($groups as $category => $rows) {
                $groups[$category] = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                    return str_contains(mb_strtolower(t_field($row['question'])), $needle)
                        || str_contains(mb_strtolower(t_field($row['answer'])), $needle);
                }));
                if ($groups[$category] === []) {
                    unset($groups[$category]);
                }
            }
        }

        return view('Modules\Tshda\Views\faqs', [
            'groups'          => $groups,
            'query'           => $query,
            'title'           => lang('Site.faqs.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.faqs.meta'),
        ]);
    }
}
