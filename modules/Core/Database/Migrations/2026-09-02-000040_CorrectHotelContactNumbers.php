<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Take the previous brand's phone numbers off this hotel's site.
 *
 * RebrandToMagicCorn pointed the contact settings at Magic Corn, and
 * SettingSeeder inserts with `ignore`, so seeding this site with the hotel's
 * own numbers never touched the rows that already existed. The result was a
 * WhatsApp button that opened a chat with a corn snack company, and a phone
 * number in Colombo for a hotel in Kalawana.
 *
 * The WhatsApp one only became visible when the chat button shipped, but it had
 * been wrong in the contact block the whole time — a wrong number is not a
 * broken link, so nothing could have flagged it.
 *
 * Conditional on the stale value still being there, so anything since edited in
 * the admin is left alone.
 */
class CorrectHotelContactNumbers extends Migration
{
    private const CORRECTIONS = [
        ['contact', 'whatsapp', '+94714545610',    '+94765735600'],
        ['contact', 'phone',    '+94 11 366 4444', '+94 76 573 5600'],
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
