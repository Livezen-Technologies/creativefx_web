<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Admin dashboard — skeleton. Lists the planned CMS modules with live row
 * counts. Full CRUD screens are a later milestone; this proves the JWT gate
 * and the modular admin surface.
 */
class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();

        $count = static function (string $table) use ($db): int {
            try {
                return (int) $db->table($table)->countAllResults();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $widgets = [
            ['Pages', $count('pages'), 'cms'],
            ['Media', $count('media_library'), 'cms'],
            ['Product Categories', $count('product_categories'), 'catalog'],
            ['Showroom Items', $count('showroom_products'), 'showroom'],
            ['Videos', $count('videos'), 'video'],
            ['ESG Metrics', $count('esg_metrics'), 'esg'],
            ['Jobs', $count('jobs'), 'careers'],
            ['Applications', $count('job_applications'), 'careers'],
            ['Leads', $count('leads'), 'crm'],
            ['Contacts', $count('contacts'), 'crm'],
            ['Users', $count('users'), 'auth'],
            ['Translations', $count('translations'), 'i18n'],
        ];

        return view('Modules\Admin\Views\dashboard', ['widgets' => $widgets]);
    }
}
