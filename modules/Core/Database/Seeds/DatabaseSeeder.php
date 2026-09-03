<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Top-level orchestrator. Run with:
 *   php spark db:seed "Modules\Core\Database\Seeds\DatabaseSeeder"
 *
 * Order matters: auth/roles first, then content that references them.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('Modules\Auth\Database\Seeds\RoleSeeder');
        $this->call('Modules\Auth\Database\Seeds\PermissionSeeder');
        $this->call('Modules\Auth\Database\Seeds\AdminUserSeeder');

        $this->call('Modules\Core\Database\Seeds\SettingSeeder');

        // The Authority's offices, senior posts and field structure. Runs
        // before the services and the pages, because both refer to it.
        $this->call('Modules\Tshda\Database\Seeds\DirectorySeeder');

        // The service catalogue (Clause 3.9 D and E) and the stakeholder
        // clusters the home page groups it into.
        $this->call('Modules\Tshda\Database\Seeds\ServiceSeeder');

        // Notices, FAQs, document categories, statistics, the Hantana calendar,
        // the society register, the discussion topic and the related-org links.
        $this->call('Modules\Tshda\Database\Seeds\TshdaDataSeeder');

        // The editorial pages, on the block CMS.
        $this->call('Modules\Tshda\Database\Seeds\PageSeeder');

        // Header and footer navigation. Seeds each menu only while it is empty,
        // so reordering and renaming in the console is not undone by a release.
        $this->call('Modules\Cms\Database\Seeds\MenuSeeder');

        // Import UI chrome strings into the translations table (editable in the
        // Translation Manager).
        $this->call('Modules\Translation\Database\Seeds\TranslationSeeder');

        // Vacancies (Clause 3.9 G) and the newsroom that carries press
        // releases, announcements and events (B.II, E.c). Both upsert by slug,
        // so an editor's changes survive a release.
        $this->call('Modules\Careers\Database\Seeds\CareersSeeder');
        $this->call('Modules\News\Database\Seeds\NewsSeeder');

        // Last: complete si/ta across everything the seeders just wrote.
        // Only empty locales are filled, so translated content is preserved.
        (new \Modules\Core\Libraries\ContentTranslator())->backfill();
    }
}
