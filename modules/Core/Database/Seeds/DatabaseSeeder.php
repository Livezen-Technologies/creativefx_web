<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Top-level orchestrator. Run with:
 *   php spark db:seed "Modules\Core\Database\Seeds\DatabaseSeeder"
 *
 * Order matters, and each dependency is stated where it is created rather than
 * in one comment at the top that goes stale:
 *
 *   roles → the admin user (which needs a role to hold)
 *   settings → everything that reads setting()
 *   currencies and price books → the catalogue, which prices sessions into them
 *   the catalogue → the menus, which link to categories that must exist
 *   everything → the translation backfill, which completes locales across it
 *
 * Every seeder below is idempotent in the way the house rule requires: content
 * rows upsert by slug and stop at anything an editor has touched, and list
 * tables seed only while empty. A release must not be able to undo somebody's
 * work, so this can be run on every deploy.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── The people who can sign in ──────────────────────────────────────
        $this->call('Modules\Auth\Database\Seeds\RoleSeeder');
        $this->call('Modules\Auth\Database\Seeds\PermissionSeeder');
        $this->call('Modules\Auth\Database\Seeds\AdminUserSeeder');

        // ── Configuration ───────────────────────────────────────────────────
        $this->call('Modules\Core\Database\Seeds\SettingSeeder');

        // ── Commercial ground rules ─────────────────────────────────────────
        // Currencies and price books first: the catalogue seeder writes a price
        // per currency for every session it creates, and a price in a currency
        // that does not exist is a row nothing will ever read.
        $this->call('Modules\Commerce\Database\Seeds\CommerceSeeder');

        // ── The catalogue ───────────────────────────────────────────────────
        // Categories, courses and everything under them, the faculty, the
        // venues, the bundles, and a rolling ninety-day schedule computed from
        // the day this runs.
        $this->call('Modules\Catalog\Database\Seeds\CatalogSeeder');

        // ── Editorial ───────────────────────────────────────────────────────
        // The pages the CMS serves — about, why us, corporate, the FAQ, the
        // policies — and the launch articles.
        $this->call('Modules\Site\Database\Seeds\PageSeeder');
        $this->call('Modules\Site\Database\Seeds\PostSeeder');

        // Header and footer navigation. Seeds each menu only while it is empty,
        // so reordering and renaming in the console is not undone by a release.
        $this->call('Modules\Cms\Database\Seeds\MenuSeeder');

        // Import UI chrome strings into the translations table, where they
        // become editable in the Translation Manager without a deployment.
        $this->call('Modules\Translation\Database\Seeds\TranslationSeeder');

        // Last: complete the other locales across everything just written. Only
        // empty locales are filled, so a translation somebody has made is never
        // overwritten.
        (new \Modules\Core\Libraries\ContentTranslator())->backfill();
    }
}
