<?php

namespace Modules\Analytics\Libraries;

/**
 * Reading the analytics log.
 *
 * Every figure here comes from the site's own table, filled by TrackPageView.
 * There is no third party involved and nothing identifying is stored, so what
 * can be asked is limited by design: how many pages were seen, by roughly how
 * many people, which pages, where they arrived from, and on what kind of
 * device. Not who.
 *
 * "Visitors" means distinct visitor hashes, and those rotate daily — so a
 * visitor counted across a week is a visitor-day, and the week's number is
 * larger than the number of humans. That is the honest reading of a measure
 * that deliberately cannot follow anyone between days, and the dashboard says
 * so rather than implying otherwise.
 */
final class Stats
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /** Page views and visitors for a range, with the previous period beside it. */
    public function summary(string $from, string $to): array
    {
        $length = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
        $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($length - 1) . ' days'));

        $now  = $this->totals($from, $to);
        $prev = $this->totals($prevFrom, $prevTo);

        foreach (['views', 'visitors', 'bookings', 'messages', 'whatsapp'] as $k) {
            $now[$k . '_prev']   = $prev[$k];
            $now[$k . '_change'] = $this->change($now[$k], $prev[$k]);
        }
        $now['range_days'] = $length;
        $now['prev_from']  = $prevFrom;
        $now['prev_to']    = $prevTo;

        return $now;
    }

    private function totals(string $from, string $to): array
    {
        $views = (int) ($this->row(
            "SELECT COUNT(*) AS n FROM analytics WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?",
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        )['n'] ?? 0);

        $visitors = (int) ($this->row(
            "SELECT COUNT(DISTINCT session_id) AS n FROM analytics WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?",
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        )['n'] ?? 0);

        $whatsapp = (int) ($this->row(
            "SELECT COUNT(*) AS n FROM analytics WHERE event = 'whatsapp_click' AND created_at >= ? AND created_at <= ?",
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        )['n'] ?? 0);

        // Enquiries come from the contacts table rather than the event log: a
        // form that was submitted is a fact, not a measurement, and it is
        // already recorded whether or not anyone is tracking page views.
        $bookings = $this->contactCount($from, $to, true);
        $messages = $this->contactCount($from, $to, false);

        return [
            'views'      => $views,
            'visitors'   => $visitors,
            'bookings'   => $bookings,
            'messages'   => $messages,
            'whatsapp'   => $whatsapp,
            // Conversion is enquiries per visitor: the question a hotel is
            // actually asking of its website.
            'conversion' => $visitors > 0 ? round((($bookings + $messages) / $visitors) * 100, 1) : 0.0,
        ];
    }

    /** A booking request is a contact row that carries dates; anything else is a message. */
    private function contactCount(string $from, string $to, bool $booking): int
    {
        try {
            $b = $this->db->table('contacts')
                ->where('created_at >=', $from . ' 00:00:00')
                ->where('created_at <=', $to . ' 23:59:59');
            $booking ? $b->where('check_in IS NOT NULL') : $b->where('check_in IS NULL');

            return (int) $b->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** One row per day in the range, so a chart has no gaps where nothing happened. */
    public function daily(string $from, string $to): array
    {
        $days = [];
        for ($d = strtotime($from); $d <= strtotime($to); $d = strtotime('+1 day', $d)) {
            $key        = date('Y-m-d', $d);
            $days[$key] = ['date' => $key, 'label' => date('j M', $d), 'views' => 0, 'visitors' => 0];
        }

        $rows = $this->rows(
            "SELECT substr(created_at, 1, 10) AS d, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors
             FROM analytics WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?
             GROUP BY substr(created_at, 1, 10)",
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        );

        foreach ($rows as $r) {
            if (isset($days[$r['d']])) {
                $days[$r['d']]['views']    = (int) $r['views'];
                $days[$r['d']]['visitors'] = (int) $r['visitors'];
            }
        }

        return array_values($days);
    }

    /** @return list<array{label:string, views:int, visitors:int}> */
    public function topPaths(string $from, string $to, int $limit = 10): array
    {
        return array_map(
            static fn (array $r): array => ['label' => $r['path'], 'views' => (int) $r['views'], 'visitors' => (int) $r['visitors']],
            $this->rows(
                "SELECT path, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors
                 FROM analytics WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?
                 GROUP BY path ORDER BY views DESC LIMIT " . (int) $limit,
                [$from . ' 00:00:00', $to . ' 23:59:59'],
            ),
        );
    }

    /** Where people arrived from. A null referrer is a direct visit, and says so. */
    public function topReferrers(string $from, string $to, int $limit = 10): array
    {
        $rows = $this->rows(
            "SELECT COALESCE(referrer, '') AS host, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors
             FROM analytics WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?
             GROUP BY COALESCE(referrer, '') ORDER BY views DESC LIMIT " . (int) $limit,
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        );

        return array_map(static fn (array $r): array => [
            'label'    => $r['host'] === '' ? 'Direct / none' : $r['host'],
            'views'    => (int) $r['views'],
            'visitors' => (int) $r['visitors'],
        ], $rows);
    }

    /**
     * Devices and browsers, parsed at read time.
     *
     * The user agent is stored as sent and interpreted here rather than being
     * bucketed on the way in, because the buckets change — a browser released
     * next year would otherwise be "unknown" forever in data already collected.
     */
    public function agents(string $from, string $to): array
    {
        $rows = $this->rows(
            "SELECT user_agent, COUNT(*) AS views FROM analytics
             WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?
             GROUP BY user_agent",
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        );

        $devices  = [];
        $browsers = [];
        foreach ($rows as $r) {
            $agent = (string) $r['user_agent'];
            $n     = (int) $r['views'];

            $device = $this->device($agent);
            $devices[$device] = ($devices[$device] ?? 0) + $n;

            $browser = $this->browser($agent);
            $browsers[$browser] = ($browsers[$browser] ?? 0) + $n;
        }

        arsort($devices);
        arsort($browsers);

        return [
            'devices'  => $this->asList($devices),
            'browsers' => $this->asList($browsers),
        ];
    }

    /** Languages, which is the closest thing to an audience this data has. */
    public function locales(string $from, string $to): array
    {
        return array_map(static fn (array $r): array => [
            'label' => $r['locale'] ?: 'Unknown',
            'views' => (int) $r['views'],
        ], $this->rows(
            "SELECT locale, COUNT(*) AS views FROM analytics
             WHERE event = 'pageview' AND created_at >= ? AND created_at <= ?
             GROUP BY locale ORDER BY views DESC",
            [$from . ' 00:00:00', $to . ' 23:59:59'],
        ));
    }

    /**
     * Desktop, tablet or phone.
     *
     * Written here rather than taken from the framework's UserAgent, which
     * reads the current request's own header and takes a config object, not the
     * stored string this needs to interpret. Three buckets is all the dashboard
     * asks for, and three buckets is a short function rather than a dependency.
     *
     * Order matters: an iPad's agent contains "Mobile", and Android tablets are
     * distinguished from Android phones only by the absence of that word.
     */
    private function device(string $agent): string
    {
        if (preg_match('~iPad|Tablet|PlayBook|Silk~i', $agent) === 1) {
            return 'Tablet';
        }
        if (preg_match('~Android~i', $agent) === 1 && preg_match('~Mobile~i', $agent) !== 1) {
            return 'Tablet';
        }
        if (preg_match('~Mobi|iPhone|iPod|Windows Phone|IEMobile~i', $agent) === 1) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * The browser family.
     *
     * Every one of these lies about the others — Edge calls itself Chrome and
     * Safari, Chrome calls itself Safari — so the checks run most-specific
     * first and Safari is only believed once nothing else has claimed it.
     */
    private function browser(string $agent): string
    {
        foreach ([
            'Edge'      => '~Edg[A-Z]?/~i',
            'Opera'     => '~OPR/|Opera~i',
            'Samsung Internet' => '~SamsungBrowser~i',
            'Firefox'   => '~Firefox/|FxiOS~i',
            'Chrome'    => '~Chrome/|CriOS~i',
            'Safari'    => '~Safari/~i',
            'Internet Explorer' => '~MSIE|Trident~i',
        ] as $name => $pattern) {
            if (preg_match($pattern, $agent) === 1) {
                return $name;
            }
        }

        return 'Other';
    }

    private function asList(array $counts): array
    {
        $out = [];
        foreach ($counts as $label => $views) {
            $out[] = ['label' => (string) $label, 'views' => (int) $views];
        }

        return $out;
    }

    /** Percentage change, or null when there is no previous figure to compare to. */
    private function change(float $now, float $prev): ?float
    {
        if ($prev <= 0.0) {
            return $now > 0 ? null : 0.0;
        }

        return round((($now - $prev) / $prev) * 100, 1);
    }

    private function row(string $sql, array $binds): array
    {
        try {
            return $this->db->query($sql, $binds)->getRowArray() ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function rows(string $sql, array $binds): array
    {
        try {
            return $this->db->query($sql, $binds)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
