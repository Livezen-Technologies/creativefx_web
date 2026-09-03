<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Admin dashboard: KPI cards with 7-day deltas, a 14-day traffic chart,
 * most-viewed pages, where visitors came from, recent enquiries, recently
 * updated content, and quick actions.
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
        // Cards for a hotel, not for the apparel manufacturer this codebase
        // started as: applications and open jobs pointed at screens that no
        // longer exist, and counted a pipeline nobody runs.
        $kpis = [
            ['label' => 'Contact messages', 'icon' => 'inbox', 'total' => $count('contacts'), 'week' => $countSince('contacts', 7), 'path' => 'admin/contacts'],
            ['label' => 'Leads', 'icon' => 'target', 'total' => $count('leads'), 'week' => $countSince('leads', 7), 'path' => 'admin/leads'],
            ['label' => 'Rooms', 'icon' => 'bed', 'total' => $count('rooms', ['status' => 'published']), 'week' => null, 'path' => 'admin/rooms'],
            ['label' => 'Tourist locations', 'icon' => 'pin', 'total' => $count('locations', ['status' => 'published']), 'week' => null, 'path' => 'admin/locations'],
            ['label' => 'Published pages', 'icon' => 'file', 'total' => $count('pages', ['status' => 'published']), 'week' => null, 'path' => 'admin/pages'],
            ['label' => 'Media files', 'icon' => 'image', 'total' => $count('media_library'), 'week' => $countSince('media_library', 7), 'path' => 'admin/media'],
        ];

        // ---- 14 days of traffic -----------------------------------------
        // Was applications and contact messages: a careers pipeline this hotel
        // does not run, so the chart was two rows of zeros with a legend over
        // them. Visits and visitors are what a hotel looks at first, and both
        // now have data behind them.
        $stats = new \Modules\Analytics\Libraries\Stats();
        $from  = date('Y-m-d', strtotime('-13 days'));
        $to    = date('Y-m-d');
        $days  = $stats->daily($from, $to);
        $traffic = $stats->summary($from, $to);
        $today   = $stats->summary($to, $to);

        // ---- Feeds ------------------------------------------------------
        $recent  = [];
        $content = [];
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

        // Which panels this administrator has chosen, and the full list for the
        // customiser. Both come from the same registry, so a widget added in a
        // release appears for people who already have a saved arrangement.
        $layout = new \Modules\Admin\Libraries\DashboardLayout();

        return view('Modules\Admin\Views\dashboard', [
            'widgets'      => $layout->visible(),
            'allWidgets'   => $layout->widgets(),
            'sizes'        => \Modules\Admin\Libraries\DashboardLayout::SIZES,
            'title'        => 'Dashboard',
            'active'       => 'dashboard',
            'kpis'         => $kpis,
            'days'         => array_values($days),
            'traffic'      => $traffic,
            'today'        => $today,
            'recent'       => $recent,
            'content'      => $content,
            'storage'      => $storage,
            'topPaths'     => $stats->topPaths($from, $to, 6),
            'referrers'    => $stats->topReferrers($from, $to, 6),
            'counts'       => [
                'pages'     => $count('pages'),
                'rooms'     => $count('rooms'),
                'locations' => $count('locations'),
                'menu'      => $count('menu_items'),
                'newMsgs'   => $count('contacts', ['status' => 'new']),
            ],
        ]);
    }

    /** Turning a panel on or off, resizing it, moving it, or starting again. */
    public function toggleWidget(string $key)
    {
        $layout  = new \Modules\Admin\Libraries\DashboardLayout();
        $current = [];
        foreach ($layout->widgets() as $w) {
            $current[$w['key']] = $w;
        }
        if (! isset($current[$key])) {
            return redirect()->to(site_url('admin'))->with('error', 'Unknown panel.');
        }

        $layout->setEnabled($key, ! $current[$key]['enabled']);

        return redirect()->to(site_url('admin'))
            ->with('message', $current[$key]['label'] . ($current[$key]['enabled'] ? ' hidden.' : ' shown.'));
    }

    public function resizeWidget(string $key)
    {
        (new \Modules\Admin\Libraries\DashboardLayout())->setSize($key, (string) $this->request->getPost('size'));

        return redirect()->to(site_url('admin'));
    }

    public function moveWidget(string $key)
    {
        $dir = $this->request->getPost('dir') === 'up' ? 'up' : 'down';
        (new \Modules\Admin\Libraries\DashboardLayout())->move($key, $dir);

        return redirect()->to(site_url('admin'));
    }

    public function resetLayout()
    {
        (new \Modules\Admin\Libraries\DashboardLayout())->reset();

        return redirect()->to(site_url('admin'))->with('message', 'Dashboard back to its default arrangement.');
    }
}
