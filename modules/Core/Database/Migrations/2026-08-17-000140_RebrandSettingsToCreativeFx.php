<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rebrands the site settings from Norlanka to CreativeFX.
 *
 * SettingSeeder inserts with ignore(), so it only ever affects a fresh install;
 * an existing database keeps whatever is already in the `settings` table. This
 * migration is what actually renames the deployed site — the name in the
 * <title>, the footer strapline and the contact details the header, footer and
 * contact block all read.
 *
 * Written to touch only rows that still hold the Norlanka value, so anything an
 * admin has already corrected by hand survives. The new social/address keys are
 * inserted empty: the header and footer hide those links until someone fills
 * them in, so an empty value is a working state rather than a broken link.
 */
class RebrandSettingsToCreativeFx extends Migration
{
    /** [group, key, old value it must still hold, new value] */
    private const REPLACEMENTS = [
        ['general', 'site_name', 'Norlanka', 'CreativeFX'],
        ['general', 'tagline', 'Responsible Sourcing · Design · Innovation', 'Production · Advertising · Digital Growth'],
        ['contact', 'email', 'info@norlankamfg.com', 'hello@creativefx.lk'],
        ['social', 'linkedin', 'https://www.linkedin.com/company/norlanka', ''],
        ['social', 'instagram', 'https://instagram.com/norlanka', ''],
    ];

    /** Keys the CreativeFX front end reads that the old site never had. */
    private const ADDITIONS = [
        ['contact', 'address', '', 'string', 1],
        ['social', 'facebook', '', 'string', 1],
        ['social', 'youtube', '', 'string', 1],
        ['social', 'tiktok', '', 'string', 1],
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::REPLACEMENTS as [$group, $key, $old, $new]) {
            $this->db->table('settings')
                ->where('group', $group)->where('key', $key)->where('value', $old)
                ->update(['value' => $new, 'updated_at' => $now]);
        }

        // The old phone and WhatsApp numbers belong to Norlanka; blanking them
        // is deliberate — a wrong number on a new brand's site is worse than no
        // number, and both are hidden until set.
        foreach ([['contact', 'phone', '+94 11 300 3010'], ['contact', 'whatsapp', '+94770000000']] as [$group, $key, $old]) {
            $this->db->table('settings')
                ->where('group', $group)->where('key', $key)->where('value', $old)
                ->update(['value' => '', 'updated_at' => $now]);
        }

        foreach (self::ADDITIONS as [$group, $key, $value, $type, $public]) {
            $exists = $this->db->table('settings')
                ->where('group', $group)->where('key', $key)
                ->countAllResults() > 0;

            if (! $exists) {
                $this->db->table('settings')->insert([
                    'group'      => $group,
                    'key'        => $key,
                    'value'      => $value,
                    'type'       => $type,
                    'is_public'  => $public,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::REPLACEMENTS as [$group, $key, $old, $new]) {
            $this->db->table('settings')
                ->where('group', $group)->where('key', $key)->where('value', $new)
                ->update(['value' => $old, 'updated_at' => $now]);
        }

        foreach (self::ADDITIONS as [$group, $key]) {
            $this->db->table('settings')->where('group', $group)->where('key', $key)->delete();
        }
    }
}
