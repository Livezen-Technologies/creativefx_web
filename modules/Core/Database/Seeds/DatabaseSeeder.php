<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Top-level orchestrator. Run with:
 *   php spark db:seed "Modules\Core\Database\Seeds\DatabaseSeeder"
 *
 * Order matters: auth/roles first, then the registries other content links to
 * (services, portfolio categories), then the CMS pages that reference them.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('Modules\Auth\Database\Seeds\RoleSeeder');
        $this->call('Modules\Auth\Database\Seeds\PermissionSeeder');
        $this->call('Modules\Auth\Database\Seeds\AdminUserSeeder');

        $this->call('Modules\Core\Database\Seeds\SettingSeeder');

        // CreativeFX registries: the six services (which drive the header
        // dropdown, footer and services grid), the portfolio, and the rental
        // gear catalogue. All upsert-by-slug, so editors' changes survive.
        $this->call('Modules\Services\Database\Seeds\ServiceSeeder');
        $this->call('Modules\Portfolio\Database\Seeds\PortfolioSeeder');
        $this->call('Modules\Gear\Database\Seeds\GearSeeder');

        // Every CMS page: home, our story, services overview, the six service
        // pages and contact.
        $this->call('Modules\Cms\Database\Seeds\CreativeFxContentSeeder');

        // Import UI chrome strings into the translations table (editable in the
        // Translation Manager). Locales with no language file of their own are
        // seeded from English, ready to translate.
        $this->call('Modules\Translation\Database\Seeds\TranslationSeeder');

        /*
         * Retired with the CreativeFX rebuild — the Norlanka apparel content.
         * The seeders are still on disk; re-enable a line to bring a section's
         * content back (its routes live in modules/Site/Config/Routes.php).
         *
         *   $this->call('Modules\Catalog\Database\Seeds\ProductCategorySeeder');
         *   $this->call('Modules\Catalog\Database\Seeds\ProductSeeder');
         *   $this->call('Modules\Cms\Database\Seeds\HomeContentSeeder');
         *   $this->call('Modules\Cms\Database\Seeds\CorporateContentSeeder');
         *   $this->call('Modules\Showroom\Database\Seeds\ShowroomSeeder');
         *   $this->call('Modules\Careers\Database\Seeds\CareersSeeder');
         *   $this->call('Modules\News\Database\Seeds\NewsSeeder');
         */

        // Last: complete any locale left empty across everything just seeded.
        // Only empty locales are filled, so translated content is preserved.
        (new \Modules\Core\Libraries\ContentTranslator())->backfill();
    }
}
