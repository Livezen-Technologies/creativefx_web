<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Clears the navigation and footer strings the rebrand replaced.
 *
 * `DbLanguage` answers every lang() call from the `translations` table before
 * it looks at the language files, and that table was seeded with Norlanka's
 * wording — so updating modules/Site/Language/en/Site.php alone left the old
 * strapline and the old menu labels on the page.
 *
 * Deleting these rows hands the keys back to the (rebranded) language files
 * immediately, and TranslationSeeder re-imports them on the next seed so the
 * Translation Manager still has rows to edit. Only the `nav.*` and `footer.*`
 * keys are touched: they are the ones whose meaning changed with the brand.
 * Every other translated string is left alone.
 */
class ClearRebrandedSiteStrings extends Migration
{
    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->groupStart()
                ->like('key', 'nav.', 'after')
                ->orLike('key', 'footer.', 'after')
            ->groupEnd()
            ->delete();
    }

    public function down(): void
    {
        // Nothing to restore: the rows are regenerated from the language files
        // by TranslationSeeder, which is the source of truth for them.
    }
}
