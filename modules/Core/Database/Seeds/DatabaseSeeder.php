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

        $this->call('Modules\Catalog\Database\Seeds\ProductCategorySeeder');

        // Seeds the CMS Home page + sections/blocks + the launch video,
        // its per-language audio tracks and subtitle files.
        $this->call('Modules\Cms\Database\Seeds\HomeContentSeeder');

        // Corporate content pages (Our Story, Expertise, Manufacturing,
        // Impact, Careers, Contact, Showroom).
        $this->call('Modules\Cms\Database\Seeds\CorporateContentSeeder');

        // Import UI chrome strings into the translations table (editable in the
        // Translation Manager).
        $this->call('Modules\Translation\Database\Seeds\TranslationSeeder');

        // Virtual showroom: themed categories + products.
        $this->call('Modules\Showroom\Database\Seeds\ShowroomSeeder');

        // Careers portal: sample vacancies (upsert-by-slug; HR edits preserved).
        $this->call('Modules\Careers\Database\Seeds\CareersSeeder');
    }
}
