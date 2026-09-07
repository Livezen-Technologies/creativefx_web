<?php

namespace Modules\Catalog\Libraries;

/**
 * Structured data.
 *
 * This is not decoration. `Course` with real `hasCourseInstance` entries — each
 * carrying a date, a mode and a price — is what qualifies a training site for
 * Google's course listing experiences, and it is the single highest-leverage
 * technical thing on the site after the pages themselves.
 *
 * Two rules run through everything here, and both are about not lying:
 *
 *   **Nothing is emitted that is not true on the page.** An `offers` block with
 *   a price the visitor cannot see, or a `CourseInstance` for a date that is not
 *   in the table below it, is a mismatch a search engine treats as spam and a
 *   buyer treats as a bait-and-switch.
 *
 *   **`aggregateRating` appears only when genuine reviews exist.** Never seeded,
 *   never defaulted, never "4.8 out of 5" because that is what everybody else
 *   has. A rating nobody left is a fabrication in a rich snippet, which is both
 *   a manual-action risk and a lie told to somebody deciding how to spend
 *   LKR 60,000.
 *
 * Every method returns an array. The view JSON-encodes it once, so a page with
 * four graphs emits one script rather than four.
 */
class Schema
{
    /**
     * The school itself. Rendered on every page.
     */
    public static function organisation(): array
    {
        helper(['norlanka', 'url']);

        $graph = [
            '@type'  => 'EducationalOrganization',
            '@id'    => rtrim(base_url(), '/') . '/#organisation',
            'name'   => setting('site_name', 'MyLearnPlus'),
            'url'    => rtrim(base_url(), '/'),
            'logo'   => rtrim(base_url(), '/') . '/favicon-512.png',
        ];

        if ($description = (string) setting('tagline', '')) {
            $graph['description'] = $description;
        }

        // Only the contact points that are actually configured. An empty
        // telephone in structured data is worse than none: it is a promise of a
        // number that does not answer.
        $email = (string) setting('email', '', 'contact');
        $phone = (string) setting('phone', '', 'contact');
        if ($email !== '' || $phone !== '') {
            $graph['contactPoint'] = array_filter([
                '@type'       => 'ContactPoint',
                'contactType' => 'customer service',
                'email'       => $email ?: null,
                'telephone'   => $phone ?: null,
            ]);
        }

        $social = array_values(array_filter([
            (string) setting('facebook', '', 'social'),
            (string) setting('linkedin', '', 'social'),
            (string) setting('instagram', '', 'social'),
            (string) setting('youtube', '', 'social'),
        ]));
        if ($social !== []) {
            $graph['sameAs'] = $social;
        }

        return $graph;
    }

    /**
     * A course, with its real dates as CourseInstances.
     *
     * @param array        $course    the course row
     * @param list<array>  $sessions  its bookable sessions, already priced
     * @param list<array>  $faqs      course FAQs (rendered separately as FAQPage)
     */
    public static function course(array $course, array $sessions, string $currency): array
    {
        helper(['norlanka', 'url', 'catalog']);

        $graph = [
            '@type'       => 'Course',
            '@id'         => course_url($course['slug']) . '#course',
            'name'        => t_field($course['title']),
            'description' => t_field($course['summary']) ?: strip_tags(t_field($course['description'])),
            'url'         => course_url($course['slug']),
            'provider'    => ['@id' => rtrim(base_url(), '/') . '/#organisation'],
            // Required by Google for course rich results, and genuinely useful:
            // it is what stops a course being listed as if it were free.
            'isAccessibleForFree' => false,
        ];

        if ($course['hero_image']) {
            $graph['image'] = rtrim(base_url(), '/') . media_src($course['hero_image']);
        }

        if ($alignment = t_field($course['certification_alignment'] ?? '')) {
            $graph['educationalCredentialAwarded'] = $alignment;
        }

        // Level, as a plain word rather than a number: "Beginner" is what a
        // reader and a search engine both understand.
        $graph['educationalLevel'] = level_label((int) $course['level']);

        $instances = [];
        foreach ($sessions as $session) {
            $instance = [
                '@type'      => 'CourseInstance',
                'courseMode' => self::courseMode((string) $session['mode']),
                // ISO 8601 duration. "P2D" for a two-day class; the workload is
                // what tells somebody whether "2 days" means six hours or
                // sixteen.
                'courseWorkload' => 'PT' . max(1, (int) $course['duration_hours']) . 'H',
            ];

            if (! empty($session['start_date'])) {
                $tz    = new \DateTimeZone((string) ($session['timezone'] ?: 'Asia/Colombo'));
                $start = new \DateTimeImmutable($session['start_date'] . ' ' . ($session['daily_start'] ?: '09:00'), $tz);
                $end   = new \DateTimeImmutable(($session['end_date'] ?: $session['start_date']) . ' ' . ($session['daily_end'] ?: '16:00'), $tz);

                $instance['startDate'] = $start->format('c');
                $instance['endDate']   = $end->format('c');
            }

            if (! empty($session['venue_name'])) {
                $instance['location'] = [
                    '@type'   => 'Place',
                    'name'    => $session['venue_name'],
                    'address' => array_filter([
                        '@type'           => 'PostalAddress',
                        'addressLocality' => $session['venue_city'] ?? null,
                        'addressCountry'  => $session['venue_country'] ?? null,
                    ]),
                ];
            } elseif (($session['mode'] ?? '') === 'LIVE_ONLINE') {
                $instance['location'] = ['@type' => 'VirtualLocation', 'url' => course_url($course['slug'])];
            }

            // The price the visitor is being shown, in the currency they are
            // being shown it in. Anything else is a mismatch.
            if (isset($session['price_cents'])) {
                $instance['offers'] = [
                    '@type'         => 'Offer',
                    'price'         => number_format((int) $session['price_cents'] / 100, 2, '.', ''),
                    'priceCurrency' => $currency,
                    'category'      => 'Paid',
                    'url'           => session_url($session),
                    'availability'  => self::availability($session),
                ];
            }

            $instances[] = $instance;
        }

        if ($instances !== []) {
            $graph['hasCourseInstance'] = $instances;
        }

        // Only when somebody actually left one. See the class comment.
        if ((int) $course['rating_count'] > 0) {
            $graph['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $course['rating_avg'],
                'reviewCount' => (int) $course['rating_count'],
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        return $graph;
    }

    /** One session, as an Event — for the session's own page. */
    public static function event(array $session, array $course, string $currency): array
    {
        helper(['norlanka', 'url', 'catalog']);

        if (empty($session['start_date'])) {
            return [];
        }

        $tz    = new \DateTimeZone((string) ($session['timezone'] ?: 'Asia/Colombo'));
        $start = new \DateTimeImmutable($session['start_date'] . ' ' . ($session['daily_start'] ?: '09:00'), $tz);
        $end   = new \DateTimeImmutable(($session['end_date'] ?: $session['start_date']) . ' ' . ($session['daily_end'] ?: '16:00'), $tz);

        $graph = [
            '@type'               => 'EducationEvent',
            'name'                => t_field($course['title']),
            'description'         => t_field($course['summary']),
            'startDate'           => $start->format('c'),
            'endDate'             => $end->format('c'),
            'eventAttendanceMode' => ($session['mode'] ?? '') === 'CLASSROOM'
                ? 'https://schema.org/OfflineEventAttendanceMode'
                : 'https://schema.org/OnlineEventAttendanceMode',
            'eventStatus'         => ($session['status'] ?? '') === 'cancelled'
                ? 'https://schema.org/EventCancelled'
                : 'https://schema.org/EventScheduled',
            'organizer'           => ['@id' => rtrim(base_url(), '/') . '/#organisation'],
            'url'                 => session_url($session + ['course_slug' => $course['slug']]),
        ];

        $graph['location'] = ! empty($session['venue_name'])
            ? [
                '@type'   => 'Place',
                'name'    => $session['venue_name'],
                'address' => array_filter([
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => t_field($session['venue_address'] ?? '') ?: null,
                    'addressLocality' => $session['venue_city'] ?? null,
                    'addressCountry'  => $session['venue_country'] ?? null,
                ]),
            ]
            : ['@type' => 'VirtualLocation', 'url' => course_url($course['slug'])];

        if (isset($session['price_cents'])) {
            $graph['offers'] = [
                '@type'         => 'Offer',
                'price'         => number_format((int) $session['price_cents'] / 100, 2, '.', ''),
                'priceCurrency' => $currency,
                'url'           => session_url($session + ['course_slug' => $course['slug']]),
                'availability'  => self::availability($session),
                'validFrom'     => date('c'),
            ];
        }

        return $graph;
    }

    /**
     * FAQs, as a FAQPage.
     *
     * @param list<array{question:mixed, answer:mixed}> $faqs
     */
    public static function faqPage(array $faqs): array
    {
        helper('norlanka');

        if ($faqs === []) {
            return [];
        }

        return [
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type'          => 'Question',
                'name'           => t_field($faq['question']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    // Answers are editorial HTML; the schema wants text.
                    'text'  => trim(strip_tags(t_field($faq['answer']))),
                ],
            ], $faqs),
        ];
    }

    /**
     * The breadcrumb trail, matching the one rendered on the page.
     *
     * @param list<array{label:string, url?:string}> $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        helper(['norlanka', 'url']);

        if ($crumbs === []) {
            return [];
        }

        $items = [[
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => lang('Site.nav.home'),
            'item'     => locale_url(''),
        ]];

        foreach ($crumbs as $i => $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $i + 2,
                'name'     => $crumb['label'],
            ];
            // The last crumb is the current page and carries no URL, which is
            // what schema.org expects — an item pointing at itself adds nothing.
            if (! empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            $items[] = $item;
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /**
     * A trainer.
     *
     * A faculty placeholder is deliberately NOT emitted: `Person` is a claim
     * about a human being, and making one about somebody who does not exist is
     * the sort of thing that is hard to explain afterwards.
     */
    public static function person(array $instructor): array
    {
        helper(['norlanka', 'url']);

        if (! empty($instructor['is_placeholder'])) {
            return [];
        }

        return array_filter([
            '@type'           => 'Person',
            'name'            => $instructor['name'],
            'jobTitle'        => t_field($instructor['headline'] ?? '') ?: null,
            'description'     => trim(strip_tags(t_field($instructor['bio'] ?? ''))) ?: null,
            'worksFor'        => ['@id' => rtrim(base_url(), '/') . '/#organisation'],
            'url'             => locale_url('instructors/' . $instructor['slug']),
        ]);
    }

    /**
     * The site itself, with the search box a sitelinks result can use.
     *
     * Emitted only on the home page. `WebSite` on every page would repeat the
     * same declaration a few hundred times and say nothing more than it says
     * once; Google reads it from the home page and that is where it belongs.
     *
     * The locale is baked into the search URL because /search is a localised
     * route: a query typed into a sitelinks box must land on a results page in
     * the language the searcher found the site in, not in the default one.
     */
    public static function website(): array
    {
        helper(['norlanka', 'url']);

        return [
            '@type'    => 'WebSite',
            '@id'      => rtrim(base_url(), '/') . '/#website',
            'url'      => rtrim(base_url(), '/') . '/',
            'name'     => setting('site_name', ''),
            'publisher' => ['@id' => rtrim(base_url(), '/') . '/#organisation'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => locale_url('search') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * The courses on a catalogue page, as an ordered list.
     *
     * An ItemList of URLs rather than a list of nested Course objects. The
     * nested form would restate every course's description, provider and price
     * on a page that is only a list of links to them, and search engines treat
     * the course page as the authority for those facts anyway — so the list
     * says what is on this page and in what order, and nothing it would then
     * have to keep in step with somewhere else.
     *
     * `position` counts from the start of the catalogue, not the start of the
     * page, so page 2 begins at 25 rather than at 1.
     *
     * @param list<array> $courses rows carrying at least a `slug`
     */
    public static function courseList(array $courses, int $startPosition = 1): array
    {
        helper(['norlanka', 'catalog', 'url']);

        if ($courses === []) {
            return [];
        }

        $items = [];
        foreach (array_values($courses) as $i => $course) {
            if (empty($course['slug'])) {
                continue;
            }
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $startPosition + $i,
                'url'      => course_url($course['slug']),
                'name'     => t_field($course['title'] ?? ''),
            ];
        }

        return $items === [] ? [] : ['@type' => 'ItemList', 'itemListElement' => $items];
    }

    /**
     * Wrap a set of graphs into the single script the page emits.
     *
     * @param list<array> $graphs
     */
    public static function render(array $graphs): string
    {
        $graphs = array_values(array_filter($graphs));
        if ($graphs === []) {
            return '';
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graphs],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // </script> inside a JSON string would close the block early and dump
        // the rest of the graph into the document as markup. Escaping the
        // slash is the standard fix and is still valid JSON.
        $json = str_replace('</', '<\/', (string) $json);

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    private static function courseMode(string $mode): string
    {
        return match ($mode) {
            'CLASSROOM'  => 'Onsite',
            'SELF_PACED' => 'Online',
            default      => 'Online',
        };
    }

    private static function availability(array $session): string
    {
        $total = (int) ($session['seats_total'] ?? 0);
        if ($total === 0) {
            return 'https://schema.org/InStock';
        }

        $left = $total - (int) ($session['seats_sold'] ?? 0) - (int) ($session['seats_reserved'] ?? 0);

        return $left > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut';
    }
}
