<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;
use Modules\Core\Libraries\ContentTranslator;

/**
 * Fills the missing ja/es/zh values across page blocks and every localized
 * content table from the translation dictionary. Only empty locales are
 * written, so anything already translated (including admin edits) is kept.
 */
class BackfillContentTranslations extends Migration
{
    public function up(): void
    {
        (new ContentTranslator())->backfill();
    }

    public function down(): void
    {
        // Content backfill — nothing to undo.
    }
}
