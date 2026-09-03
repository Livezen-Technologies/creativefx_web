<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Analytics\Libraries\Stats;

/**
 * The analytics screen.
 *
 * Everything shown comes from the site's own table, which is filled server-side
 * by the page-view filter. No third-party script runs on the public site to
 * produce any of it, and nothing identifying is stored, so the questions this
 * can answer are deliberately bounded: how many pages were seen, by roughly how
 * many people, which pages, from where, on what kind of device.
 *
 * Country and city are not among them. Deriving those needs either an IP —
 * which is not kept — or a third-party service the visitor never agreed to.
 * The screen says so rather than showing an empty panel that looks broken.
 */
class Analytics extends BaseController
{
    private const RANGES = [
        '7'   => 'Last 7 days',
        '14'  => 'Last 14 days',
        '30'  => 'Last 30 days',
        '90'  => 'Last 90 days',
        '365' => 'Last 12 months',
    ];

    public function __construct()
    {
        helper(['admin', 'url']);
    }

    public function index()
    {
        if (! admin_can('settings.view')) {
            return redirect()->to(site_url('admin'))->with('error', 'You do not have permission to view analytics.');
        }

        [$from, $to, $range] = $this->range();
        $stats = new Stats();
        $agents = $stats->agents($from, $to);

        return view('Modules\Admin\Views\analytics', [
            'title'     => 'Analytics',
            'active'    => 'analytics',
            'ranges'    => self::RANGES,
            'range'     => $range,
            'from'      => $from,
            'to'        => $to,
            'summary'   => $stats->summary($from, $to),
            'days'      => $stats->daily($from, $to),
            'paths'     => $stats->topPaths($from, $to, 12),
            'referrers' => $stats->topReferrers($from, $to, 10),
            'devices'   => $agents['devices'],
            'browsers'  => array_slice($agents['browsers'], 0, 8),
            'locales'   => $stats->locales($from, $to),
            'ga4'       => (string) setting('ga4_measurement_id', '', 'analytics'),
        ]);
    }

    /** The same figures as a spreadsheet, one row per day. */
    public function export()
    {
        if (! admin_can('settings.view')) {
            return redirect()->to(site_url('admin'))->with('error', 'You do not have permission to export analytics.');
        }

        [$from, $to] = $this->range();
        $stats = new Stats();

        // Written straight to the output rather than assembled in memory: a
        // year of daily rows is small, but the pattern holds if the range grows.
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Date', 'Page views', 'Visitors']);
        foreach ($stats->daily($from, $to) as $d) {
            fputcsv($out, [$d['date'], $d['views'], $d['visitors']]);
        }
        fputcsv($out, []);
        fputcsv($out, ['Page', 'Page views', 'Visitors']);
        foreach ($stats->topPaths($from, $to, 100) as $p) {
            fputcsv($out, [$p['label'], $p['views'], $p['visitors']]);
        }
        fputcsv($out, []);
        fputcsv($out, ['Referrer', 'Page views', 'Visitors']);
        foreach ($stats->topReferrers($from, $to, 100) as $r) {
            fputcsv($out, [$r['label'], $r['views'], $r['visitors']]);
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="analytics-' . $from . '-to-' . $to . '.csv"')
            // Excel reads a CSV as the system's legacy encoding unless a byte
            // order mark says otherwise, which turns every accented name into
            // mojibake on a Windows machine.
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    /** @return array{0:string,1:string,2:string} from, to, range key */
    private function range(): array
    {
        $range = (string) $this->request->getGet('range');
        if (! isset(self::RANGES[$range])) {
            $range = '30';
        }

        // Explicit dates win over the preset, so a range can be linked to.
        $from = (string) $this->request->getGet('from');
        $to   = (string) $this->request->getGet('to');
        $valid = static fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        if ($valid($from) && $valid($to) && strtotime($from) <= strtotime($to)) {
            return [$from, $to, 'custom'];
        }

        return [date('Y-m-d', strtotime('-' . ((int) $range - 1) . ' days')), date('Y-m-d'), $range];
    }
}
