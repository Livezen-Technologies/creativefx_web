<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Admin dashboard: KPI cards with 7-day deltas, a 14-day activity chart
 * (applications + contact messages), the application pipeline, recent
 * activity feeds, recently updated content, and quick actions.
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

        $since = static fn (int $days): string => date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $countSince = static function (string $table, int $days) use ($db, $since): int {
            try {
                return (int) $db->table($table)->where('created_at >=', $since($days))->countAllResults();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        // ---- KPI cards: total, 7-day delta ------------------------------
        $kpis = [
            ['label' => 'Applications', 'icon' => 'users', 'total' => $count('job_applications'), 'week' => $countSince('job_applications', 7), 'path' => 'admin/applications'],
            ['label' => 'Contact messages', 'icon' => 'inbox', 'total' => $count('contacts'), 'week' => $countSince('contacts', 7), 'path' => 'admin/contacts'],
            ['label' => 'Leads', 'icon' => 'target', 'total' => $count('leads'), 'week' => $countSince('leads', 7), 'path' => 'admin/leads'],
            ['label' => 'Open jobs', 'icon' => 'briefcase', 'total' => $count('jobs', ['status' => 'open']), 'week' => null, 'path' => 'admin/jobs'],
            ['label' => 'Published news', 'icon' => 'news', 'total' => $count('news_posts', ['status' => 'published']), 'week' => null, 'path' => 'admin/news-posts'],
            ['label' => 'Media files', 'icon' => 'image', 'total' => $count('media_library'), 'week' => $countSince('media_library', 7), 'path' => 'admin/media'],
        ];

        // ---- 14-day activity chart --------------------------------------
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $days[$d] = ['label' => date('j M', strtotime($d)), 'apps' => 0, 'contacts' => 0];
        }
        $bucket = static function (string $table, string $key) use ($db, &$days): void {
            try {
                $rows = $db->table($table)
                    ->select("substr(created_at, 1, 10) AS d, COUNT(*) AS n")
                    ->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-13 days')))
                    ->groupBy('d')->get()->getResultArray();
                foreach ($rows as $r) {
                    if (isset($days[$r['d']])) {
                        $days[$r['d']][$key] = (int) $r['n'];
                    }
                }
            } catch (\Throwable $e) {
            }
        };
        $bucket('job_applications', 'apps');
        $bucket('contacts', 'contacts');

        // ---- Application pipeline ---------------------------------------
        $pipeline = [];
        try {
            foreach ($db->table('job_applications')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $r) {
                $pipeline[$r['status']] = (int) $r['n'];
            }
        } catch (\Throwable $e) {
        }

        // ---- Feeds ------------------------------------------------------
        $applications = [];
        $recent       = [];
        $content      = [];
        try {
            $applications = $db->table('job_applications')
                ->select('job_applications.*, jobs.title AS job_title')
                ->join('jobs', 'jobs.id = job_applications.job_id', 'left')
                ->orderBy('job_applications.id', 'DESC')->limit(6)->get()->getResultArray();
        } catch (\Throwable $e) {
        }
        try {
            $recent = $db->table('contacts')->orderBy('id', 'DESC')->limit(6)->get()->getResultArray();
        } catch (\Throwable $e) {
        }
        try {
            $content = $db->table('pages')->select("id, slug, title, status, updated_at, 'page' AS kind")
                ->orderBy('updated_at', 'DESC')->limit(4)->get()->getResultArray();
            $news = $db->table('news_posts')->select("id, slug, title, status, updated_at, 'news' AS kind")
                ->orderBy('updated_at', 'DESC')->limit(4)->get()->getResultArray();
            $content = array_merge($content, $news);
            usort($content, static fn ($a, $b) => strcmp((string) $b['updated_at'], (string) $a['updated_at']));
            $content = array_slice($content, 0, 6);
        } catch (\Throwable $e) {
        }

        // ---- Storage ----------------------------------------------------
        $storage = ['bytes' => 0, 'files' => 0];
        try {
            $r = $db->table('media_library')->select('COALESCE(SUM(size_bytes),0) AS b, COUNT(*) AS n')->get()->getRowArray();
            $storage = ['bytes' => (int) ($r['b'] ?? 0), 'files' => (int) ($r['n'] ?? 0)];
        } catch (\Throwable $e) {
        }

        return view('Modules\Admin\Views\dashboard', [
            'title'        => 'Dashboard',
            'active'       => 'dashboard',
            'kpis'         => $kpis,
            'days'         => array_values($days),
            'pipeline'     => $pipeline,
            'applications' => $applications,
            'recent'       => $recent,
            'content'      => $content,
            'storage'      => $storage,
            'counts'       => [
                'pages'    => $count('pages'),
                'products' => $count('products'),
                'showroom' => $count('showroom_products'),
                'jobs'     => $count('jobs'),
                'newApps'  => $count('job_applications', ['status' => 'new']),
                'newMsgs'  => $count('contacts', ['status' => 'new']),
            ],
        ]);
    }
}
