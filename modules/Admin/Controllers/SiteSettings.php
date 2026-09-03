<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Admin\Config\SettingsSchema;
use Modules\Core\Libraries\SecretBox;
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

            if (! empty($f['secret'])) {
                // A blank secret field means "leave it alone", not "clear it" —
                // the form never shows the stored value, so an editor saving
                // the page for an unrelated reason would otherwise wipe it.
                if ($value === '') {
                    continue;
                }
                try {
                    $value = SecretBox::encrypt($value);
                } catch (\RuntimeException $e) {
                    return redirect()->back()->withInput()->with('error', $e->getMessage());
                }
            }

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

    /**
     * Send a message to whoever is signed in, to prove the settings work.
     *
     * Sent to the administrator's own address rather than to a field on the
     * form: a test that lets you type any recipient is an open relay for
     * anyone who reaches this page, and the person testing is the person who
     * needs to see whether it arrived.
     */
    public function testEmail()
    {
        if (! admin_can('settings.manage')) {
            return redirect()->to(site_url('admin/site-settings/email'))->with('error', 'You do not have permission to send a test.');
        }

        $to = trim((string) (session()->get('admin_user')['email'] ?? ''));
        if ($to === '') {
            return redirect()->to(site_url('admin/site-settings/email'))
                ->with('error', 'Your account has no email address, so there is nowhere to send a test.');
        }

        $result = \Modules\Core\Libraries\Mailer::send(
            $to,
            (string) setting('site_name', '') . ' — test message',
            '<p>This is a test from the ' . esc((string) setting('site_name', '')) . ' admin console.</p>'
            . '<p>If you are reading it, the SMTP settings work and booking requests will reach their recipients.</p>'
            . '<p style="color:#666;font-size:12px">Sent ' . esc(date('j M Y, H:i')) . '.</p>',
        );

        $redirect = redirect()->to(site_url('admin/site-settings/email'));

        if ($result['sent']) {
            return $redirect->with('message', 'Test message sent to ' . $to . '. If it does not arrive within a few minutes, check the spam folder and that "Send from" is an address this account may send as.');
        }

        // The reason, not "sending failed" — the whole point of a test is to
        // find out what is wrong.
        log_message('error', 'SMTP test failed: ' . ($result['detail'] ?: $result['error']));

        return $redirect->with('error', 'Could not send: ' . $result['error']);
    }

    /**
     * Every declared setting's current value, keyed by field key.
     *
     * A secret is reported as set or not set, never as its value. Rendering a
     * password into an input puts it into every proxy log, browser cache and
     * password manager between the server and the editor's screen, to save one
     * person one retype.
     */
    private function currentValues(): array
    {
        $secret = [];
        foreach (SettingsSchema::fields() as $f) {
            if (! empty($f['secret'])) {
                $secret[$f['key']] = true;
            }
        }

        $values = [];
        foreach (SettingsSchema::fields() as $f) {
            $stored = (string) setting($f['key'], '', $f['group']);
            $values[$f['key']] = isset($secret[$f['key']]) ? '' : $stored;
            if (isset($secret[$f['key']])) {
                $values['__set_' . $f['key']] = SecretBox::isSet($stored) ? '1' : '';
            }
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
            'password' => 'permit_empty|max_length[255]',
            'text', 'tel', 'color' => 'permit_empty|max_length[255]',
            default  => '',
        };
    }
}
