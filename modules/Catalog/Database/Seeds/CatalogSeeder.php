<?php

namespace Modules\Catalog\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The launch catalogue: the category tree, the courses and everything that
 * hangs off them, the faculty, the venues, the bundles, and a rolling schedule
 * of real dates.
 *
 * ── Idempotence ─────────────────────────────────────────────────────────────
 *
 * The house rule, unchanged: **a release must never undo the content team's
 * work.** Three different mechanisms, matching what the rest of the platform
 * already does.
 *
 *   - Courses and bundles are upserted by slug, and a row whose `is_custom`
 *     flag is set — which the admin sets on any save — is skipped entirely,
 *     including all of its child rows.
 *   - The category tree, the faculty and the venues seed only while their table
 *     is empty.
 *   - Sessions are generated only for courses that have none in the future, so
 *     re-seeding tops up a schedule that has run down rather than duplicating
 *     one that has not.
 *
 * ── The schedule ────────────────────────────────────────────────────────────
 *
 * "No dates available" kills conversion more reliably than any other single
 * thing on a training site, so this seeds a rolling ninety days rather than a
 * fixed list that is stale the month after launch. The dates are computed
 * relative to the day the seeder runs, so a site stood up in March gets March
 * dates.
 *
 * ── What is deliberately NOT seeded ─────────────────────────────────────────
 *
 * Reviews and ratings. There are none, because there have been no learners.
 * The reviews section renders an honest empty state; an invented testimonial on
 * a commercial site is a lie a customer can act on, and a seeded
 * `AggregateRating` is the same lie told to a search engine.
 */
class CatalogSeeder extends Seeder
{
    /**
     * List prices in minor units, by band and by level, from the ranges the
     * blueprint publishes. Level 1 sits near the bottom of each band and
     * level 3 near the top, so the catalogue has an honest spread rather than
     * one number repeated forty times.
     *
     * These are provisional and flagged as such in the README: the final
     * numbers are a commercial decision, and the point of the price book is
     * that changing them is an afternoon in the admin rather than a deployment.
     */
    private const BANDS = [
        '1day'      => ['USD' => [42500, 49500, 57500],      'LKR' => [2800000, 3500000, 4200000]],
        '2day'      => ['USD' => [69500, 79500, 94500],      'LKR' => [4800000, 6000000, 7200000]],
        'bootcamp'  => ['USD' => [119500, 149500, 174500],   'LKR' => [8900000, 11000000, 13200000]],
        'selfpaced' => ['USD' => [4900, 9900, 14900],        'LKR' => [450000, 850000, 1250000]],
    ];

    /** A classroom seat costs more than an online one: a room, and lunch. */
    private const CLASSROOM_UPLIFT = 1.2;

    /**
     * What a programme saves against buying its courses one at a time.
     *
     * Every certificate used to be seeded at a flat $2,995 and every bootcamp
     * at a flat $1,495, whatever they contained — so a four-course certificate
     * and a two-course one cost the same, and five of the seven programmes cost
     * MORE than their own parts. The pages say "cheaper than buying them one at
     * a time" and carry a struck-through price and a "Save …" badge, none of
     * which could ever appear, because `saving()` floors at zero.
     *
     * A programme is now priced from its own contents, so the sentence is true
     * by construction rather than by somebody remembering to re-check it after
     * a course price changes.
     */
    private const BUNDLE_DISCOUNT = 0.15;

    public function run(): void
    {
        $data = rtrim(__DIR__, '/') . '/data';

        $this->categories();
        $this->instructors($data);
        $this->venues($data);
        $this->courses($data);
        $this->bundles($data);
        $this->resources($data);
        $this->schedule();
    }

    // ── Taxonomy ────────────────────────────────────────────────────────────

    /**
     * Two levels: the pillar, then the application. The deep URL this produces
     * — /courses/adobe-creative-cloud/photoshop — is the entire basis of the
     * SEO plan, so the tree is structure rather than decoration.
     */
    private function categories(): void
    {
        if ($this->db->table('course_categories')->countAllResults() > 0) {
            return;
        }

        $tree = [
            ['adobe-creative-cloud', 'Adobe Creative Cloud', 'adobe', [
                ['photoshop', 'Photoshop'], ['illustrator', 'Illustrator'], ['indesign', 'InDesign'],
                ['premiere-pro', 'Premiere Pro'], ['after-effects', 'After Effects'],
                ['firefly', 'Adobe Firefly'], ['adobe-express', 'Adobe Express'],
                ['acrobat-pro', 'Acrobat Pro'], ['lightroom', 'Lightroom'],
            ]],
            ['ai-and-generative-ai', 'AI & Generative AI', 'ai', [
                ['ai-foundations', 'AI Foundations'], ['prompt-engineering', 'Prompt Engineering'],
                ['ai-assistants', 'AI Assistants'], ['generative-ai-design', 'Generative AI for Designers'],
                ['ai-video-audio', 'AI Video & Audio'], ['ai-marketing', 'AI for Marketing'],
                ['ai-automation', 'AI Automation & Agents'], ['llm-apis', 'Building with LLM APIs'],
                ['ai-governance', 'AI Governance & Responsible Use'],
            ]],
            ['design-and-digital', 'Design & Digital', 'design', [
                ['figma-uiux', 'Figma & UI/UX'], ['web-wordpress', 'Web Design & WordPress'],
                ['digital-marketing', 'Digital Marketing & Analytics'],
                ['presentation-dataviz', 'Presentation & Data Visualisation'],
            ]],
        ];

        $now  = date('Y-m-d H:i:s');
        $sort = 0;

        foreach ($tree as [$slug, $name, $pillar, $children]) {
            $this->db->table('course_categories')->insert([
                'parent_id'  => null,
                'slug'       => $slug,
                // A locale map with only English in it is a valid locale map:
                // t_field() falls back, so the site works today and the Sinhala
                // can be added in the admin without a migration.
                'name'       => json_encode(['en' => $name], JSON_UNESCAPED_UNICODE),
                'pillar'     => $pillar,
                'sort_order' => ++$sort,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $parentId = (int) $this->db->insertID();

            $childSort = 0;
            foreach ($children as [$childSlug, $childName]) {
                $this->db->table('course_categories')->insert([
                    'parent_id'  => $parentId,
                    'slug'       => $childSlug,
                    'name'       => json_encode(['en' => $childName], JSON_UNESCAPED_UNICODE),
                    'pillar'     => $pillar,
                    'sort_order' => ++$childSort,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    // ── Faculty and venues ──────────────────────────────────────────────────

    private function instructors(string $dataDir): void
    {
        if ($this->db->table('instructors')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->readJson($dataDir . '/instructors') as $sort => $row) {
            $this->db->table('instructors')->insert([
                'slug'             => $row['slug'],
                'name'             => $row['name'],
                'headline'         => json_encode(['en' => $row['headline'] ?? ''], JSON_UNESCAPED_UNICODE),
                'bio'              => json_encode(['en' => $row['bio_html'] ?? ''], JSON_UNESCAPED_UNICODE),
                'credentials_json' => json_encode($row['credentials'] ?? [], JSON_UNESCAPED_UNICODE),
                'links_json'       => json_encode($row['links'] ?? [], JSON_UNESCAPED_UNICODE),
                // The honest flag. These describe the faculty, not a person:
                // named trainers with photographs and verifiable credentials
                // are the school's to supply, and inventing four of them would
                // be a fabrication a buyer could act on. The admin lists every
                // row carrying this so nobody forgets.
                'is_placeholder'   => ! empty($row['placeholder']) ? 1 : 0,
                'sort_order'       => $sort + 1,
                'status'           => 'published',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    private function venues(string $dataDir): void
    {
        if ($this->db->table('venues')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->readJson($dataDir . '/locations') as $sort => $row) {
            $this->db->table('venues')->insert([
                'slug'            => $row['slug'],
                'name'            => $row['name'],
                'type'            => $row['type'] ?? 'classroom',
                // Street addresses are left as the placeholder the copy carries
                // rather than invented. A wrong address on a training school's
                // location page sends somebody to the wrong building.
                'address'         => json_encode(['en' => $row['address'] ?? ''], JSON_UNESCAPED_UNICODE),
                'city'            => $row['name'],
                'country'         => $row['country'] ?? 'LK',
                'timezone'        => $row['timezone'] ?? 'Asia/Colombo',
                'capacity'        => (int) ($row['capacity'] ?? 12),
                'heading'         => json_encode(['en' => $row['heading'] ?? ''], JSON_UNESCAPED_UNICODE),
                'summary'         => json_encode(['en' => $row['summary'] ?? ''], JSON_UNESCAPED_UNICODE),
                'body'            => json_encode(['en' => $row['body_html'] ?? ''], JSON_UNESCAPED_UNICODE),
                'seo_title'       => json_encode(['en' => $row['seo']['title'] ?? ''], JSON_UNESCAPED_UNICODE),
                'seo_description' => json_encode(['en' => $row['seo']['description'] ?? ''], JSON_UNESCAPED_UNICODE),
                'seo_keywords'    => $row['seo']['keywords'] ?? null,
                'sort_order'      => $sort + 1,
                'status'          => 'published',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    // ── Courses ─────────────────────────────────────────────────────────────

    private function courses(string $dataDir): void
    {
        $categories = array_column(
            $this->db->table('course_categories')->select('id, slug')->get()->getResultArray(),
            'id',
            'slug'
        );

        $now     = date('Y-m-d H:i:s');
        $pending = [];   // related links, resolved after every course exists

        foreach ($this->readJson($dataDir . '/courses') as $row) {
            $existing = $this->db->table('courses')->where('slug', $row['slug'])->get()->getRowArray();

            // Somebody has edited this course in the admin. Leave it, and
            // leave its children, entirely alone.
            if ($existing !== null && (int) $existing['is_custom'] === 1) {
                continue;
            }

            $fields = [
                'category_id'             => $categories[$row['category']] ?? null,
                'slug'                    => $row['slug'],
                'title'                   => $this->map($row['title'] ?? ''),
                'subtitle'                => $this->map($row['subtitle'] ?? ''),
                'summary'                 => $this->map($row['summary'] ?? ''),
                'description'             => $this->map($row['description_html'] ?? ''),
                'level'                   => (int) ($row['level'] ?? 1),
                'duration_hours'          => (int) ($row['duration_hours'] ?? 0),
                'duration_days'           => (float) ($row['duration_days'] ?? 0),
                'software_version'        => $row['software_version'] ?: null,
                'certification_alignment' => $this->map($row['certification_alignment'] ?? ''),
                'pillar'                  => $row['pillar'] ?? 'adobe',
                'default_mode'            => $row['default_mode'] ?? 'LIVE_ONLINE',
                'price_band'              => $row['price_band'] ?? '1day',
                'seo_title'               => $this->map($row['seo']['title'] ?? ''),
                'seo_description'         => $this->map($row['seo']['description'] ?? ''),
                'seo_keywords'            => $row['seo']['keywords'] ?? null,
                'status'                  => 'published',
                'published_at'            => $now,
                'updated_at'              => $now,
            ];

            if ($existing !== null) {
                $this->db->table('courses')->where('id', (int) $existing['id'])->update($fields);
                $courseId = (int) $existing['id'];
            } else {
                $this->db->table('courses')->insert($fields + ['created_at' => $now]);
                $courseId = (int) $this->db->insertID();
            }

            $this->courseChildren($courseId, $row);
            $pending[$courseId] = $row['related'] ?? [];
        }

        // Related courses, once every slug resolves. Doing this inside the loop
        // would silently drop every forward reference — the first course
        // written would have no related links at all, which is exactly the kind
        // of quiet, partial failure that survives a review.
        $slugToId = array_column(
            $this->db->table('courses')->select('id, slug')->get()->getResultArray(),
            'id',
            'slug'
        );

        foreach ($pending as $courseId => $slugs) {
            $this->db->table('course_related')->where('course_id', $courseId)->delete();
            $sort = 0;
            foreach ($slugs as $slug) {
                if (! isset($slugToId[$slug]) || (int) $slugToId[$slug] === $courseId) {
                    continue;
                }
                $this->db->table('course_related')->insert([
                    'course_id'  => $courseId,
                    'related_id' => (int) $slugToId[$slug],
                    'sort_order' => ++$sort,
                ]);
            }
        }

        $this->assignInstructors();
    }

    /**
     * The lists under a course, replaced wholesale.
     *
     * Replace rather than merge: these are ordered lists an editor rewrites as
     * a whole, and merging by position produces the worst of both — an outcome
     * that was deleted upstream lingering at number seven.
     */
    private function courseChildren(int $courseId, array $row): void
    {
        foreach ([
            'course_outcomes'      => $row['outcomes'] ?? [],
            'course_prerequisites' => $row['prerequisites'] ?? [],
            'course_audiences'     => $row['audiences'] ?? [],
        ] as $table => $items) {
            $this->db->table($table)->where('course_id', $courseId)->delete();
            $sort = 0;
            foreach ($items as $text) {
                $this->db->table($table)->insert([
                    'course_id'  => $courseId,
                    'text'       => $this->map((string) $text),
                    'sort_order' => ++$sort,
                ]);
            }
        }

        $this->db->table('course_includes')->where('course_id', $courseId)->delete();
        $sort = 0;
        foreach ($row['includes'] ?? [] as $include) {
            $this->db->table('course_includes')->insert([
                'course_id'  => $courseId,
                'text'       => $this->map((string) ($include['text'] ?? '')),
                'icon'       => $include['icon'] ?? null,
                'sort_order' => ++$sort,
            ]);
        }

        // Topics are deleted through their modules, so the modules go last —
        // the other way round orphans every topic row.
        $moduleIds = array_column(
            $this->db->table('course_modules')->select('id')->where('course_id', $courseId)->get()->getResultArray(),
            'id'
        );
        if ($moduleIds !== []) {
            $this->db->table('course_topics')->whereIn('module_id', $moduleIds)->delete();
        }
        $this->db->table('course_modules')->where('course_id', $courseId)->delete();

        $sort = 0;
        foreach ($row['curriculum'] ?? [] as $module) {
            $this->db->table('course_modules')->insert([
                'course_id'  => $courseId,
                'title'      => $this->map((string) ($module['title'] ?? '')),
                'summary'    => $this->map((string) ($module['summary'] ?? '')),
                'sort_order' => ++$sort,
            ]);
            $moduleId  = (int) $this->db->insertID();
            $topicSort = 0;
            foreach ($module['topics'] ?? [] as $topic) {
                $this->db->table('course_topics')->insert([
                    'module_id'    => $moduleId,
                    'title'        => $this->map((string) ($topic['title'] ?? '')),
                    'duration_min' => (int) ($topic['duration_min'] ?? 0),
                    'sort_order'   => ++$topicSort,
                ]);
            }
        }

        $this->db->table('course_faqs')->where('course_id', $courseId)->delete();
        $sort = 0;
        foreach ($row['faqs'] ?? [] as $faq) {
            $this->db->table('course_faqs')->insert([
                'course_id'  => $courseId,
                'question'   => $this->map((string) ($faq['question'] ?? '')),
                'answer'     => $this->map((string) ($faq['answer'] ?? '')),
                'sort_order' => ++$sort,
            ]);
        }

        $this->db->table('course_delivery_modes')->where('course_id', $courseId)->delete();
        foreach ($row['delivery_modes'] ?? ['LIVE_ONLINE'] as $mode) {
            $this->db->table('course_delivery_modes')->insert([
                'course_id'  => $courseId,
                'mode'       => $mode,
                'is_default' => $mode === ($row['default_mode'] ?? 'LIVE_ONLINE') ? 1 : 0,
            ]);
        }
    }

    /** Faculty by pillar, until named trainers replace them. */
    private function assignInstructors(): void
    {
        $faculty = array_column(
            $this->db->table('instructors')->select('id, slug')->get()->getResultArray(),
            'id',
            'slug'
        );

        $byPillar = [
            'adobe'  => $faculty['adobe-faculty'] ?? null,
            'ai'     => $faculty['ai-faculty'] ?? null,
            'design' => $faculty['design-faculty'] ?? null,
        ];

        foreach ($this->db->table('courses')->select('id, pillar')->get()->getResultArray() as $course) {
            $instructorId = $byPillar[$course['pillar']] ?? null;
            if ($instructorId === null) {
                continue;
            }
            $exists = $this->db->table('course_instructor')
                ->where('course_id', (int) $course['id'])->where('instructor_id', (int) $instructorId)
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('course_instructor')->insert([
                    'course_id'     => (int) $course['id'],
                    'instructor_id' => (int) $instructorId,
                    'sort_order'    => 1,
                ]);
            }
        }
    }

    // ── Bundles ─────────────────────────────────────────────────────────────

    private function bundles(string $dataDir): void
    {
        $slugToId = array_column(
            $this->db->table('courses')->select('id, slug')->get()->getResultArray(),
            'id',
            'slug'
        );

        $now  = date('Y-m-d H:i:s');
        $sort = 0;

        foreach ($this->readJson($dataDir . '/bundles') as $row) {
            $existing = $this->db->table('bundles')->where('slug', $row['slug'])->get()->getRowArray();
            if ($existing !== null && (int) $existing['is_custom'] === 1) {
                continue;
            }

            $fields = [
                'slug'            => $row['slug'],
                'type'            => $row['type'] ?? 'certificate',
                'title'           => $this->map($row['title'] ?? ''),
                'subtitle'        => $this->map($row['subtitle'] ?? ''),
                'summary'         => $this->map($row['summary'] ?? ''),
                'description'     => $this->map($row['description_html'] ?? ''),
                'seo_title'       => $this->map($row['seo']['title'] ?? ''),
                'seo_description' => $this->map($row['seo']['description'] ?? ''),
                'seo_keywords'    => $row['seo']['keywords'] ?? null,
                'status'          => 'published',
                'sort_order'      => ++$sort,
                'updated_at'      => $now,
            ];

            if ($existing !== null) {
                $this->db->table('bundles')->where('id', (int) $existing['id'])->update($fields);
                $bundleId = (int) $existing['id'];
            } else {
                $this->db->table('bundles')->insert($fields + ['created_at' => $now]);
                $bundleId = (int) $this->db->insertID();
            }

            $this->bundleChildren($bundleId, $row);

            $this->db->table('bundle_items')->where('bundle_id', $bundleId)->delete();
            $itemSort = 0;
            foreach ($row['courses'] ?? [] as $slug) {
                if (! isset($slugToId[$slug])) {
                    continue;
                }
                $this->db->table('bundle_items')->insert([
                    'bundle_id'   => $bundleId,
                    'course_id'   => (int) $slugToId[$slug],
                    'is_required' => in_array($slug, $row['required'] ?? [], true) ? 1 : 0,
                    'sort_order'  => ++$itemSort,
                ]);
            }

            $price = $this->bundlePriceFor($bundleId, $row);

            foreach ($price as $currency => $cents) {
                $this->db->table('bundle_prices')
                    ->where('bundle_id', $bundleId)->where('currency', $currency)->delete();
                $this->db->table('bundle_prices')->insert([
                    'bundle_id'   => $bundleId,
                    'currency'    => $currency,
                    'price_cents' => $cents,
                ]);
            }
        }
    }

    // ── Lead magnets ────────────────────────────────────────────────────────

    /**
     * The downloadable resources.
     *
     * Four of these have been sitting in `data/resources` since the catalogue
     * was written and nothing read them, so /resources answered 404 and the
     * widest part of the funnel did not exist.
     *
     * **No `file_path` is set, and that is deliberate.** There is no PDF on
     * this server yet. `Resources::download()` already treats a resource with
     * no file as one that must be asked for rather than handed over — the
     * button says we will send it, the address is saved as a lead, and the
     * download counter is left alone because nothing was downloaded. Pointing
     * `file_path` at a file that is not there would turn that honest path into
     * a broken one, and the fix when the artwork lands is to fill in one
     * column rather than to change any code.
     *
     * Webinars are not seeded: `data/webinars` is empty, and the page's own
     * empty state is a truer answer than an invented broadcast with a date.
     */
    private function resources(string $dataDir): void
    {
        if ($this->db->table('resources')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->readJson($dataDir . '/resources') as $sort => $row) {
            $this->db->table('resources')->insert([
                'slug'            => $row['slug'],
                'type'            => $row['type'] ?? 'guide',
                'title'           => $this->map($row['title'] ?? ''),
                'summary'         => $this->map($row['summary'] ?? ''),
                'body'            => $this->map($row['body_html'] ?? ''),
                'file_path'       => null,
                'gated'           => (int) (bool) ($row['gated'] ?? false),
                'seo_title'       => $this->map($row['seo']['title'] ?? ''),
                'seo_description' => $this->map($row['seo']['description'] ?? ''),
                'seo_keywords'    => $row['seo']['keywords'] ?? null,
                'sort_order'      => $sort + 1,
                'status'          => 'published',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    /**
     * A programme's own outcomes, inclusions and FAQs.
     *
     * These sat unread in `data/bundles/*.json` while the seeder took seven of
     * the file's fourteen keys, so a certificate programme — the most expensive
     * thing on sale — had a thinner page than any single course inside it.
     *
     * Cleared and rewritten each run, like `bundle_items` above, so editing a
     * JSON file and re-seeding is the whole workflow rather than editing a file
     * and then wondering why the page has not changed.
     */
    private function bundleChildren(int $bundleId, array $row): void
    {
        foreach (['bundle_outcomes', 'bundle_includes', 'bundle_faqs'] as $table) {
            $this->db->table($table)->where('bundle_id', $bundleId)->delete();
        }

        foreach (array_values($row['outcomes'] ?? []) as $i => $text) {
            $this->db->table('bundle_outcomes')->insert([
                'bundle_id'  => $bundleId,
                'text'       => $this->map((string) $text),
                'sort_order' => $i + 1,
            ]);
        }

        foreach (array_values($row['includes'] ?? []) as $i => $item) {
            // A bare string is accepted as well as {text, icon}: the icon is
            // decoration, and a list that only renders when somebody remembered
            // to name an icon is a list that loses content to a typo.
            $text = is_array($item) ? ($item['text'] ?? '') : $item;
            $this->db->table('bundle_includes')->insert([
                'bundle_id'  => $bundleId,
                'text'       => $this->map((string) $text),
                'icon'       => is_array($item) ? ($item['icon'] ?? null) : null,
                'sort_order' => $i + 1,
            ]);
        }

        foreach (array_values($row['faqs'] ?? []) as $i => $faq) {
            if (! is_array($faq) || trim((string) ($faq['question'] ?? '')) === '') {
                continue;
            }
            $this->db->table('bundle_faqs')->insert([
                'bundle_id'  => $bundleId,
                'question'   => $this->map((string) $faq['question']),
                'answer'     => $this->map((string) ($faq['answer'] ?? '')),
                'sort_order' => $i + 1,
            ]);
        }
    }

    // ── The rolling schedule ────────────────────────────────────────────────

    /**
     * Give every course dates for the next ninety days.
     *
     * Only courses with nothing scheduled ahead of today are touched, so this
     * tops a schedule up rather than duplicating one. Dates are computed from
     * the day the seeder runs: a site stood up in March gets March dates, which
     * is the whole point of a rolling schedule rather than a fixed list that is
     * stale the month after launch.
     */
    private function schedule(): void
    {
        $colombo = $this->db->table('venues')->where('slug', 'colombo')->get()->getRowArray();
        $now     = date('Y-m-d H:i:s');
        $today   = new \DateTimeImmutable('today');

        $courses = $this->db->table('courses')
            ->select('id, slug, level, price_band, duration_days, default_mode')
            ->where('status', 'published')->get()->getResultArray();

        foreach ($courses as $index => $course) {
            $courseId = (int) $course['id'];

            $future = $this->db->table('course_sessions')
                ->where('course_id', $courseId)
                ->groupStart()->where('start_date >=', $today->format('Y-m-d'))->orWhere('start_date IS NULL')->groupEnd()
                ->countAllResults();
            if ($future > 0) {
                continue;
            }

            $modes = array_column(
                $this->db->table('course_delivery_modes')->select('mode')->where('course_id', $courseId)->get()->getResultArray(),
                'mode'
            );

            $days = max(1, (int) ceil((float) $course['duration_days']));

            // Staggered by course, so the schedule page is not forty classes
            // all starting on the same Tuesday.
            $offset = 7 + ($index * 5) % 21;

            if (in_array('LIVE_ONLINE', $modes, true)) {
                for ($n = 0; $n < 4; $n++) {
                    $this->makeSession($courseId, 'LIVE_ONLINE', $this->nextTuesday($today, $offset + $n * 21), $days, null, $course, $now);
                }
            }

            if (in_array('CLASSROOM', $modes, true) && $colombo !== null) {
                for ($n = 0; $n < 2; $n++) {
                    $this->makeSession($courseId, 'CLASSROOM', $this->nextTuesday($today, $offset + 10 + $n * 42), $days, (int) $colombo['id'], $course, $now);
                }
            }

            if (in_array('SELF_PACED', $modes, true)) {
                // No dates, no seat limit: on-demand study riding the same
                // pipeline as everything else rather than a parallel one.
                $this->makeSession($courseId, 'SELF_PACED', null, $days, null, $course, $now);
            }
        }
    }

    private function makeSession(int $courseId, string $mode, ?\DateTimeImmutable $start, int $days, ?int $venueId, array $course, string $now): void
    {
        $selfPaced = $mode === 'SELF_PACED';
        $end       = $start?->modify('+' . ($days - 1) . ' days');

        $this->db->table('course_sessions')->insert([
            'course_id'      => $courseId,
            'mode'           => $mode,
            'venue_id'       => $venueId,
            'language'       => 'en',
            'start_date'     => $start?->format('Y-m-d'),
            'end_date'       => $end?->format('Y-m-d'),
            'timezone'       => 'Asia/Colombo',
            'daily_start'    => $selfPaced ? null : '09:00:00',
            'daily_end'      => $selfPaced ? null : '16:00:00',
            'seats_total'    => $selfPaced ? 0 : ($mode === 'CLASSROOM' ? 10 : 12),
            'seats_reserved' => 0,
            'seats_sold'     => 0,
            'min_to_run'     => $selfPaced ? 0 : 3,
            'status'         => 'open',
            'is_private'     => 0,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $sessionId = (int) $this->db->insertID();

        if (! $selfPaced && $start !== null) {
            for ($d = 0; $d < $days; $d++) {
                $day = $start->modify('+' . $d . ' days');
                $this->db->table('session_days')->insert([
                    'session_id' => $sessionId,
                    'day_date'   => $day->format('Y-m-d'),
                    'start_time' => '09:00:00',
                    'end_time'   => '16:00:00',
                    'sort_order' => $d + 1,
                ]);
            }
        }

        foreach ($this->priceFor($course, $mode) as $currency => $cents) {
            $this->db->table('session_prices')->insert([
                'session_id'  => $sessionId,
                'currency'    => $currency,
                'price_cents' => $cents,
            ]);
        }
    }

    /**
     * A programme's price: its taught courses, less the programme discount,
     * rounded to something a price list can print.
     *
     * Uses `CardPricer` so the sum is the same figure the programme page shows
     * in its course column — the number a buyer can add up themselves. Anything
     * else is a saving that does not survive arithmetic.
     *
     * @return array<string,int>
     */
    private function bundlePriceFor(int $bundleId, array $row): array
    {
        $courseIds = array_map(
            'intval',
            array_column(
                $this->db->table('bundle_items')->select('course_id')
                    ->where('bundle_id', $bundleId)->get()->getResultArray(),
                'course_id'
            )
        );

        $out = [];
        foreach (['USD', 'LKR'] as $currency) {
            $rows = (new \Modules\Catalog\Libraries\CardPricer())->decorate(
                array_map(static fn (int $id): array => ['id' => $id], $courseIds),
                $currency
            );

            $sum = 0;
            foreach ($rows as $r) {
                $sum += (int) ($r['from_cents'] ?? 0);
            }

            if ($sum === 0) {
                continue;
            }

            // Rounded to the currency's own step, down, so the discount is
            // never quietly smaller than advertised: $5 and Rs 1,000.
            $step  = $currency === 'LKR' ? 100000 : 500;
            $out[$currency] = (int) (floor($sum * (1 - self::BUNDLE_DISCOUNT) / $step) * $step);
        }

        return $out;
    }

    /** @return array<string,int> */
    private function priceFor(array $course, string $mode): array
    {
        $band  = $mode === 'SELF_PACED' ? 'selfpaced' : ($course['price_band'] ?? '1day');
        $table = self::BANDS[$band] ?? self::BANDS['1day'];
        $tier  = max(0, min(2, (int) $course['level'] - 1));

        $out = [];
        foreach ($table as $currency => $tiers) {
            $cents = $tiers[$tier];
            if ($mode === 'CLASSROOM') {
                // Rounded to the currency's own step so an uplift never
                // produces $594.00 or Rs 42,371.
                $step  = $currency === 'LKR' ? 10000 : 100;
                $cents = (int) (round($cents * self::CLASSROOM_UPLIFT / $step) * $step);
            }
            $out[$currency] = $cents;
        }

        return $out;
    }

    /** The first Tuesday at least `$offsetDays` from today. Classes start midweek. */
    private function nextTuesday(\DateTimeImmutable $from, int $offsetDays): \DateTimeImmutable
    {
        $date = $from->modify('+' . $offsetDays . ' days');
        while ((int) $date->format('N') !== 2) {
            $date = $date->modify('+1 day');
        }

        return $date;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** A JSON locale map holding the English. Sinhala is added in the admin. */
    private function map(string $value): string
    {
        return json_encode($value === '' ? [] : ['en' => $value], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Every .json file in a directory, decoded.
     *
     * A malformed file is skipped with a log line rather than taking the whole
     * seed down: one bad course must not stop the other thirty-one from
     * publishing, and a silent `null` merged into the insert would be far
     * harder to find than a warning.
     *
     * @return list<array>
     */
    private function readJson(string $dir): array
    {
        $out = [];
        foreach (glob(rtrim($dir, '/') . '/*.json') ?: [] as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (! is_array($decoded)) {
                log_message('error', 'Catalogue seed file is not valid JSON: {f}', ['f' => $file]);

                continue;
            }
            $out[] = $decoded;
        }

        // glob() sorts, but only by filename. Sorting by slug keeps the seeded
        // ids stable when a file is renamed.
        usort($out, static fn (array $a, array $b): int => strcmp($a['slug'] ?? '', $b['slug'] ?? ''));

        return $out;
    }
}
