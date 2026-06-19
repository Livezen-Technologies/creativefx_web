<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

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
            ['Pages', $count('pages'), 'admin/pages'],
            ['Product Categories', $count('product_categories'), 'admin/categories'],
            ['ESG Metrics', $count('esg_metrics'), 'admin/esg-metrics'],
            ['Jobs', $count('jobs'), 'admin/jobs'],
            ['Contacts', $count('contacts'), 'admin/contacts'],
            ['Leads', $count('leads'), 'admin/leads'],
            ['Media', $count('media_library'), 'admin/media'],
            ['Users', $count('users'), 'admin'],
        ];

        $recent = [];
        try {
            $recent = $db->table('contacts')->orderBy('id', 'DESC')->limit(5)->get()->getResultArray();
        } catch (\Throwable $e) {
        }

        return view('Modules\Admin\Views\dashboard', [
            'title'   => 'Dashboard',
            'active'  => 'dashboard',
            'widgets' => $widgets,
            'recent'  => $recent,
        ]);
    }
}
