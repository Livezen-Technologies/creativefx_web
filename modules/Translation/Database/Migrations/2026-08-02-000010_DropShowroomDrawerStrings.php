<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The showroom product drawer was cut back to photos and description, so the
 * copy for its sizes, materials, wishlist, quick actions and enquiry form is
 * dead. It was seeded into the translations table, which TranslationSeeder
 * never rewrites (onlyIfMissing), so clear it out rather than leave orphans in
 * the Translation Manager.
 *
 * Deliberately narrow: `saved` still labels the wishlist counter, and `send`,
 * `success` and `f_company` are shared with the catalogue product enquiry form.
 */
class DropShowroomDrawerStrings extends Migration
{
    private const DEAD = [
        'showroom.sizes',
        'showroom.materials',
        'showroom.colours',
        'showroom.fabric',
        'showroom.moq',
        'showroom.save',
        'showroom.saved_btn',
        'showroom.req_sample',
        'showroom.download_cat',
        'showroom.email_inq',
        'showroom.whatsapp',
        'showroom.inquiry',
        'showroom.sending',
        'showroom.f_phone',
        'showroom.f_country',
        'showroom.f_qty',
    ];

    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->whereIn('key', self::DEAD)
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder from the language files.
    }
}
