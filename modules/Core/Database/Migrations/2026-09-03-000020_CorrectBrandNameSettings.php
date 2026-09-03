<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Take the corn company's name off the hotel's site.
 *
 * The loading screen every visitor sees first still reads "Magic Corn". So does
 * the logo's alt text, the fallback page title, and the message the WhatsApp
 * button pre-fills — "Hello Magic Corn, I would like to ask about a room."
 *
 * Same shape as the phone numbers: RebrandToMagicCorn set these, SettingSeeder
 * inserts with `ignore` and so never revisited them, and until the loading
 * screen and the chat button started reading `site_name` there was nothing on
 * screen to give it away.
 *
 * Conditional on the stale value, so anything edited in the admin survives.
 */
class CorrectBrandNameSettings extends Migration
{
    private const CORRECTIONS = [
        ['general', 'site_name', 'Magic Corn',   'Kukuleganga Giants Forest'],
        ['general', 'tagline',   'Corn in a Cup', 'Stay in Sapphire Land'],
    ];

    public function up(): void
    {
        $this->apply(static fn (array $c): array => [$c[2], $c[3]]);
    }

    public function down(): void
    {
        $this->apply(static fn (array $c): array => [$c[3], $c[2]]);
    }

    private function apply(callable $pick): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        foreach (self::CORRECTIONS as $correction) {
            [$from, $to] = $pick($correction);
            $this->db->table('settings')
                ->where('group', $correction[0])->where('key', $correction[1])
                ->where('value', $from)
                ->update(['value' => $to, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
