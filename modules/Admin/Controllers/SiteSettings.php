<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Admin\Config\SettingsSchema;
use Modules\Core\Models\SettingModel;

/**
 * The guided Settings screen.
 *
 * The raw key/value list is still there for anything not declared in the
 * schema, and for the rare case of adding a setting the code reads but this
 * form does not know about. This is the front an editor uses: labelled fields,
 * explanations, real inputs, and validation that says what is wrong.
 *
 * Reads and writes the same settings table, one row per field, so nothing about
 * how the site consumes them changes.
 */
class SiteSettings extends BaseController
{
    public function __construct()
    {
        helper(['admin', 'url', 'norlanka']);
    }

    public function index(?string $group = null)
    {
        if (! admin_can('settings.view')) {
            return redirect()->to(site_url('admin'))->with('error', 'You do not have permission to view settings.');
        }

        $groups  = SettingsSchema::groups();
        $current = $group !== null && isset($groups[$group]) ? $group : array_key_first($groups);

        return view('Modules\Admin\Views\site_settings', [
            'title'   => 'Settings',
            'active'  => 'site-settings',
            'groups'  => $groups,
            'current' => $current,
            'values'  => $this->currentValues(),
        ]);
    }

    public function save(string $group)
    {
        // Reading and changing are separate permissions: an editor who can see
        // how the site is configured is not necessarily one who may change it.
        if (! admin_can('settings.manage')) {
            return redirect()->to(site_url('admin/site-settings'))->with('error', 'You do not have permission to change settings.');
        }

        $groups = SettingsSchema::groups();
        if (! isset($groups[$group])) {
            return redirect()->to(site_url('admin/site-settings'))->with('error', 'Unknown settings group.');
        }

        $fields = $groups[$group]['fields'];

        // Validate before writing anything: a form that saves four fields and
        // then rejects the fifth leaves an editor unsure what actually landed.
        $rules  = [];
        $labels = [];
        foreach ($fields as $f) {
            $rule = $f['rules'] ?? $this->ruleFor($f);
            if ($rule !== '') {
                $rules[$f['key']]  = $rule;
                $labels[$f['key']] = $f['label'];
            }
        }
        if ($rules !== [] && ! $this->validateData($this->request->getPost(), $rules, $labels)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = model(SettingModel::class);
        $saved = 0;

        foreach ($fields as $f) {
            $value = $f['type'] === 'checkbox'
                ? ($this->request->getPost($f['key']) ? '1' : '0')
                : trim((string) $this->request->getPost($f['key']));

            $row = $model->where('group', $f['group'])->where('key', $f['key'])->first();

            if ($row === null) {
                $model->insert([
                    'group'     => $f['group'],
                    'key'       => $f['key'],
                    'value'     => $value,
                    'type'      => $f['type'] === 'checkbox' ? 'bool' : 'string',
                    'is_public' => 1,
                ]);
            } elseif ((string) $row['value'] !== $value) {
                $model->update($row['id'], ['value' => $value]);
            } else {
                continue;
            }
            $saved++;
        }

        return redirect()->to(site_url('admin/site-settings/' . $group))
            ->with('message', $saved === 0
                ? 'Nothing to save — no values changed.'
                : $groups[$group]['label'] . ' settings saved (' . $saved . ' ' . ($saved === 1 ? 'change' : 'changes') . ').');
    }

    /** Every declared setting's current value, keyed by field key. */
    private function currentValues(): array
    {
        $values = [];
        foreach (SettingsSchema::fields() as $f) {
            $values[$f['key']] = (string) setting($f['key'], '', $f['group']);
        }

        return $values;
    }

    /**
     * A sensible rule per type when the schema does not name one.
     *
     * permit_empty throughout: a blank setting is how the site is told not to
     * show something, and it must not be an error.
     */
    private function ruleFor(array $field): string
    {
        return match ($field['type']) {
            'email'  => 'permit_empty|valid_email',
            'url'    => 'permit_empty|valid_url_strict[https]|max_length[255]',
            'number' => 'permit_empty|numeric',
            'image'  => 'permit_empty|max_length[255]',
            'text', 'tel', 'color' => 'permit_empty|max_length[255]',
            default  => '',
        };
    }
}
