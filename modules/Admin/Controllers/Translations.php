<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Translation\Models\TranslationModel;

/**
 * Translation Manager — a per-locale matrix editor over the translations table.
 * Edits take effect immediately because DbLanguage reads this table before the
 * file-based strings (see app/Config/Services::language()).
 */
class Translations extends BaseController
{
    /** @return list<string> */
    private function locales(): array
    {
        return config('App')->supportedLocales;
    }

    private function model(): TranslationModel
    {
        return model(TranslationModel::class);
    }

    public function index()
    {
        $model  = $this->model();
        $groups = array_values(array_filter(array_column(
            $model->select('group')->distinct()->orderBy('group', 'ASC')->findAll(),
            'group'
        )));
        if ($groups === []) {
            $groups = ['Site'];
        }

        $group = $this->request->getGet('group') ?: $groups[0];

        $matrix = [];
        foreach ($model->where('group', $group)->orderBy('key', 'ASC')->findAll() as $r) {
            $matrix[$r['key']][$r['locale']] = $r['value'];
        }
        ksort($matrix);

        return view('Modules\Admin\Views\translations', [
            'title'   => 'Translations',
            'active'  => 'translations',
            'groups'  => $groups,
            'group'   => $group,
            'locales' => $this->locales(),
            'matrix'  => $matrix,
        ]);
    }

    public function save()
    {
        $group = $this->request->getPost('group') ?: 'Site';
        $items = $this->request->getPost('items') ?? [];
        $model = $this->model();
        $count = 0;

        foreach ($items as $item) {
            $key = trim((string) ($item['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            foreach ($this->locales() as $locale) {
                if (array_key_exists($locale, $item)) {
                    $model->put($locale, $group, $key, (string) $item[$locale]);
                    $count++;
                }
            }
        }

        return redirect()->to(site_url('admin/translations?group=' . urlencode($group)))
            ->with('message', "Saved {$count} values.");
    }

    public function addKey()
    {
        $group = trim((string) $this->request->getPost('group')) ?: 'Site';
        $key   = trim((string) $this->request->getPost('key'));

        if ($key === '') {
            return redirect()->back()->with('error', 'A key is required.');
        }

        $model = $this->model();
        foreach ($this->locales() as $locale) {
            $model->put($locale, $group, $key, (string) $this->request->getPost($locale));
        }

        return redirect()->to(site_url('admin/translations?group=' . urlencode($group)))
            ->with('message', 'Key added.');
    }

    public function import()
    {
        $model = $this->model();
        $count = 0;

        foreach ($this->locales() as $locale) {
            $file = ROOTPATH . 'modules/Site/Language/' . $locale . '/Site.php';
            if (! is_file($file)) {
                continue;
            }
            $data = require $file;
            if (! is_array($data)) {
                continue;
            }
            foreach (TranslationModel::flatten($data) as $key => $value) {
                $model->putFromFile($locale, 'Site', $key, $value);
                $count++;
            }
        }

        return redirect()->to(site_url('admin/translations?group=Site'))
            ->with('message', "Imported UI strings ({$count} checked, existing values preserved).");
    }
}
