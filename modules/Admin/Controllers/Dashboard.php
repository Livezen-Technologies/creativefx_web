<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Admin dashboard: live counts across the CMS (blueprint §1) plus the most
 * recent applications and contact inquiries as an activity feed.
 */
class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();

        $count = static function (string $table, array $where = []) use ($db): int {
            try {
                $b = $db->table($table);
                foreach ($where as $k => $v) {
                    $b->where($k, $v);
                }
                return (int) $b->countAllResults();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $widgets = [
            ['Pages', $count('pages'), 'admin/pages'],
            ['News Posts', $count('news_posts'), 'admin/news-posts'],
            ['Published News', $count('news_posts', ['status' => 'published']), 'admin/news-posts'],
            ['Showroom Products', $count('showroom_products'), 'admin/showroom-products'],
            ['Showroom Categories', $count('showroom_categories'), 'admin/showroom-categories'],
            ['Open Jobs', $count('jobs', ['status' => 'open']), 'admin/jobs'],
            ['Applications', $count('job_applications'), 'admin/applications'],
            ['New Applications', $count('job_applications', ['status' => 'new']), 'admin/applications?status=new'],
            ['Contact Inquiries', $count('contacts'), 'admin/contacts'],
            ['Leads', $count('leads'), 'admin/leads'],
            ['Media Files', $count('media_library'), 'admin/media'],
            ['Product Categories', $count('product_categories'), 'admin/categories'],
            ['ESG Metrics', $count('esg_metrics'), 'admin/esg-metrics'],
            ['Users', $count('users'), 'admin'],
        ];

        // Recent activity: latest applications and contact messages.
        $applications = [];
        $recent       = [];
        try {
            $applications = $db->table('job_applications')
                ->select('job_applications.*, jobs.title AS job_title')
                ->join('jobs', 'jobs.id = job_applications.job_id', 'left')
                ->orderBy('job_applications.id', 'DESC')->limit(5)->get()->getResultArray();
        } catch (\Throwable $e) {
        }
        try {
            $recent = $db->table('contacts')->orderBy('id', 'DESC')->limit(5)->get()->getResultArray();
        } catch (\Throwable $e) {
        }

        return view('Modules\Admin\Views\dashboard', [
            'title'        => 'Dashboard',
            'active'       => 'dashboard',
            'widgets'      => $widgets,
            'recent'       => $recent,
            'applications' => $applications,
        ]);
    }
}
