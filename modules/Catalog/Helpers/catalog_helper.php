<?php

/**
 * Reading a course, a session and a date in a view.
 *
 * The dates are the interesting part. A class has three clocks around it — the
 * school's, the learner's, and UTC — and a page that shows only one of them is
 * a page somebody turns up to at the wrong hour. Everything here that renders a
 * time renders the class's own timezone alongside it.
 */

if (! function_exists('course_url')) {
    function course_url(string $slug): string
    {
        helper('norlanka');

        return locale_url('course/' . $slug);
    }
}

if (! function_exists('session_url')) {
    /**
     * A session's own page: /schedule/photoshop-level-1-214.
     *
     * The slug is decoration and the id is what resolves, so renaming a course
     * changes the URL's prose without breaking a link somebody bookmarked.
     */
    function session_url(array $session): string
    {
        helper('norlanka');
        $slug = $session['course_slug'] ?? 'session';

        return locale_url('schedule/' . $slug . '-' . (int) $session['id']);
    }
}

if (! function_exists('mode_label')) {
    function mode_label(?string $mode): string
    {
        return match ($mode) {
            'LIVE_ONLINE' => lang('Catalog.mode.live_online'),
            'CLASSROOM'   => lang('Catalog.mode.classroom'),
            'SELF_PACED'  => lang('Catalog.mode.self_paced'),
            'PRIVATE'     => lang('Catalog.mode.private'),
            default       => '',
        };
    }
}

if (! function_exists('session_dates')) {
    /**
     * "14–15 Oct 2026", or "15 Oct 2026", or the self-paced wording.
     *
     * The month is not repeated when both days share one, because "14 Oct – 15
     * Oct" is two words longer for no information.
     */
    function session_dates(array $session): string
    {
        if (empty($session['start_date'])) {
            return lang('Catalog.session.any_time');
        }

        $start = new DateTimeImmutable((string) $session['start_date']);
        $end   = empty($session['end_date']) ? $start : new DateTimeImmutable((string) $session['end_date']);

        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $start->format('j M Y');
        }
        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->format('j') . '–' . $end->format('j M Y');
        }

        return $start->format('j M') . ' – ' . $end->format('j M Y');
    }
}

if (! function_exists('session_times')) {
    /** "09:00–16:00 IST", with the class's own zone named. */
    function session_times(array $session): string
    {
        if (empty($session['daily_start'])) {
            return '';
        }

        $zone = new DateTimeZone((string) ($session['timezone'] ?: 'Asia/Colombo'));
        $day  = (string) ($session['start_date'] ?: date('Y-m-d'));

        return substr((string) $session['daily_start'], 0, 5)
            . '–' . substr((string) $session['daily_end'], 0, 5)
            . ' ' . (new DateTimeImmutable($day, $zone))->format('T');
    }
}

if (! function_exists('seats_note')) {
    /**
     * What the seat count should say, and how loudly.
     *
     * Deliberately vague above four: "6 seats left" on a twelve-seat class
     * reads as a slow month rather than as scarcity, and a number that is meant
     * to reassure should not be able to discourage. Below four it is exact,
     * because at that point it is true urgency rather than a technique.
     *
     * @return array{text:string, urgent:bool}
     */
    function seats_note(array $session): array
    {
        $total = (int) ($session['seats_total'] ?? 0);
        if ($total === 0) {
            return ['text' => lang('Catalog.session.unlimited'), 'urgent' => false];
        }

        $left = max(0, $total - (int) $session['seats_sold'] - (int) $session['seats_reserved']);

        if ($left === 0) {
            return ['text' => lang('Catalog.session.full'), 'urgent' => true];
        }
        if ($left <= 3) {
            return ['text' => lang('Catalog.session.seats_left', [$left]), 'urgent' => true];
        }

        return ['text' => lang('Catalog.session.places_available'), 'urgent' => false];
    }
}

if (! function_exists('level_label')) {
    function level_label(int $level): string
    {
        return match ($level) {
            1       => lang('Catalog.level.1'),
            2       => lang('Catalog.level.2'),
            3       => lang('Catalog.level.3'),
            default => '',
        };
    }
}

if (! function_exists('duration_label')) {
    /** "2 days · 12 hours", dropping whichever half is not set. */
    function duration_label(array $course): string
    {
        $parts = [];
        $days  = (float) ($course['duration_days'] ?? 0);
        $hours = (int) ($course['duration_hours'] ?? 0);

        if ($days > 0) {
            $parts[] = lang('Catalog.duration.days', [rtrim(rtrim(number_format($days, 1), '0'), '.')]);
        }
        if ($hours > 0) {
            $parts[] = lang('Catalog.duration.hours', [$hours]);
        }

        return implode(' · ', $parts);
    }
}
