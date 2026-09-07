<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Catalog\Libraries\Schema;
use Modules\Cms\Libraries\PageSeo;
use Modules\Cms\Models\PageModel;
use Modules\Commerce\Models\LeadModel;
use Modules\Core\Libraries\Mailer;
use Modules\Core\Libraries\Recaptcha;

/**
 * The business-to-business funnel: a landing page and a request for a quote.
 *
 * There is deliberately no checkout anywhere in this class. A company sending
 * twelve people on a course is not making a card purchase: it is asking for a
 * date, a scope, a price it can take to a finance department and an invoice
 * against a purchase order. Dropping that buyer into the same basket as a
 * single learner loses the sale twice over — the seats are wrong, and the
 * conversation that would have shaped the syllabus never happens.
 *
 * So the whole of this class ends at a row in `training_leads`. Nothing here
 * touches an order, a payment, a seat or `seats_sold`; a private cohort becomes
 * a `course_sessions` row with `is_private = 1` only after a human has agreed a
 * quote, and only from the console.
 *
 * Four decisions are worth stating because they are the ones that would
 * otherwise get quietly undone:
 *
 *   **The lead is saved before anything is emailed, and a mail failure can
 *   never fail the request.** On a fresh install there is no SMTP host at all.
 *   A form that returns an error because a mail server is unreachable has lost
 *   the enquiry as well as the email, and the enquiry was the valuable half.
 *
 *   **The editorial copy comes from the CMS when an editor has written it and
 *   from the language file when they have not.** A marketing page that only
 *   works once somebody has seeded content is a page that is broken on the day
 *   it launches, and a page that can only be changed by a deployment is a page
 *   that never gets changed.
 *
 *   **Nothing is claimed that is not true.** No client logos, no case studies,
 *   no named references and no learner counts: the school has none yet, and a
 *   corporate buyer checking one invented reference is a corporate buyer lost
 *   for good. Where those sections would sit, the page renders nothing.
 *
 *   **The budget question is asked in the visitor's own currency, from the
 *   school's own published price list.** The bounds below are integer minor
 *   units and are formatted exactly once, by `money()` in the view. A band
 *   table with no entry for the visitor's currency means the question is not
 *   asked at all, rather than asked in a currency they do not buy in.
 */
class Corporate extends BaseController
{
    /** The discriminator on `training_leads` this controller writes. */
    private const LEAD_TYPE = 'corporate';

    /** The reCAPTCHA v3 action, and the key of its per-form switch. */
    private const FORM = 'corporate';

    /**
     * How many quote requests one machine, and one address, may send in an hour.
     *
     * Generous on purpose. The throttle exists to stop the table being filled
     * by a script, not to punish somebody who mistyped their email and sent the
     * form again. The machine limit is the wider of the two because an office,
     * a campus or a mobile carrier is one address to us and a whole building to
     * itself — and a building is precisely where corporate enquiries come from.
     */
    private const PER_IP    = 10;
    private const PER_EMAIL = 5;

    /**
     * Team-size bands, stored verbatim in `training_leads.team_size`.
     *
     * Bands rather than a number because the honest answer at enquiry time is
     * usually a range: a manager knows they have "about fifteen" long before
     * they know which fifteen. Asking for an integer produces a confident wrong
     * one, which is then quoted against.
     */
    public const TEAM_SIZES = ['1-4', '5-9', '10-19', '20-49', '50+', 'not_sure'];

    /**
     * How a private cohort would be delivered.
     *
     * Deliberately *not* `CourseSessionModel::MODES`. Those are scheduling
     * facts about a session that exists; these are a preference expressed
     * before anything is scheduled, and one of them — hybrid — has no
     * scheduling meaning at all. An administrator turns the agreed answer into
     * a real mode when the quote becomes a date.
     */
    public const MODES = ['ONSITE', 'VIRTUAL', 'HYBRID', 'UNSURE'];

    /**
     * Budget bands, as [minimum, maximum] in integer minor units.
     *
     * Anchored to the school's own published seat prices rather than invented:
     * a one-day class is seeded at roughly Rs 28,000–42,000 / $425–575 a seat,
     * so a ten-person private cohort lands in the second band and a
     * twenty-person two-day programme in the third. A `null` bound is open.
     *
     * Per currency, because a band is a price and prices on this site are
     * published per currency and never converted at runtime. A currency absent
     * from this table is one nobody has set bands for, and the form then omits
     * the question entirely — see `budgetBands()`.
     */
    private const BUDGET_BANDS = [
        'LKR' => [
            'band_1' => [null, 25_000_00],
            'band_2' => [25_000_00, 75_000_00],
            'band_3' => [75_000_00, 200_000_00],
            'band_4' => [200_000_00, null],
        ],
        'USD' => [
            'band_1' => [null, 2_500_00],
            'band_2' => [2_500_00, 7_500_00],
            'band_3' => [7_500_00, 20_000_00],
            'band_4' => [20_000_00, null],
        ],
    ];

    /**
     * ISO 3166-1 alpha-2, for the country field.
     *
     * Held on the controller rather than in the view — which is where the
     * checkout keeps its copy — because here the same list has two jobs: it
     * renders the select *and* it validates what comes back. Two lists would
     * drift, and the way they drift is a country a visitor can choose and the
     * validator then rejects. Sri Lanka leads because most of these enquiries
     * come from there; the rest are alphabetical.
     */
    public const COUNTRIES = [
        'LK' => 'Sri Lanka',
        'AE' => 'United Arab Emirates', 'AU' => 'Australia', 'BD' => 'Bangladesh',
        'BH' => 'Bahrain', 'CA' => 'Canada', 'CH' => 'Switzerland', 'CN' => 'China',
        'DE' => 'Germany', 'DK' => 'Denmark', 'EG' => 'Egypt', 'ES' => 'Spain',
        'FR' => 'France', 'HK' => 'Hong Kong SAR China', 'ID' => 'Indonesia',
        'IE' => 'Ireland', 'IN' => 'India', 'IT' => 'Italy', 'JP' => 'Japan',
        'KE' => 'Kenya', 'KW' => 'Kuwait', 'MV' => 'Maldives', 'MY' => 'Malaysia',
        'NG' => 'Nigeria', 'NL' => 'Netherlands', 'NO' => 'Norway',
        'NZ' => 'New Zealand', 'OM' => 'Oman', 'PH' => 'Philippines',
        'PK' => 'Pakistan', 'QA' => 'Qatar', 'SA' => 'Saudi Arabia',
        'SE' => 'Sweden', 'SG' => 'Singapore', 'TH' => 'Thailand', 'TR' => 'Türkiye',
        'US' => 'United States', 'GB' => 'United Kingdom', 'VN' => 'Viet Nam',
        'ZA' => 'South Africa',
    ];

    // ── The landing page ────────────────────────────────────────────────────

    /**
     * What private training is, how it is delivered, and how a quote happens.
     *
     * Outcome-led rather than feature-led: the buyer here is a manager with a
     * capability gap and a budget cycle, not somebody browsing a catalogue.
     * The page therefore answers "what would this actually look like for us"
     * before it answers "what is in it".
     */
    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $pages = new PageModel();
        $page  = $pages->findPublishedBySlug('corporate');
        if ($page !== null) {
            $page = $pages->withStructure($page);
        }

        // The SEO fields when an editor has written a page, and the language
        // file's when they have not. PageSeo falls back to the site name for a
        // missing title, which on a page that does not exist is worse than the
        // copy we already have — so the fallbacks are applied here rather than
        // handing it an empty array and hoping.
        $seo = $page === null ? [] : PageSeo::for($page)->toViewData(current_url());

        $crumbs = [['label' => lang('Commerce.corporate.title')]];

        return view('Modules\Commerce\Views\corporate\index', [
            'heading'         => $page === null ? lang('Commerce.corporate.heading') : (t_field($page['title']) ?: lang('Commerce.corporate.heading')),
            'intro'           => ($seo['metaDescription'] ?? '') ?: lang('Commerce.corporate.intro'),
            // Only the editorial blocks. A `pagehero` is dropped because the
            // band at the top of this page already is the hero, and two
            // stacked heroes is the failure mode of mixing a controller page
            // with a CMS one.
            'blocks'          => $this->editorialBlocks($page),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => $seo['title'] ?? (lang('Commerce.corporate.title') . ' — ' . setting('site_name', '')),
            'metaDescription' => ($seo['metaDescription'] ?? '') ?: lang('Commerce.corporate.meta'),
            'metaKeywords'    => $seo['metaKeywords'] ?? '',
            'ogImage'         => $seo['ogImage'] ?? null,
            'lastUpdated'     => $page['updated_at'] ?? null,
        ]);
    }

    // ── The request for a quote ─────────────────────────────────────────────

    /**
     * The form.
     *
     * Reachable from every course page with `?course={slug}`, which pre-ticks
     * that course. The parameter is a convenience and nothing more: an unknown
     * or withdrawn slug is ignored rather than turned into a 404, because a
     * stale link from a course that has since been renamed should still let a
     * company ask for a quote.
     */
    public function request(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $currency = current_currency();
        $courses  = $this->courseOptions();

        $crumbs = [
            ['label' => lang('Commerce.corporate.title'), 'url' => locale_url('corporate')],
            ['label' => lang('Commerce.corporate.form.title')],
        ];

        return view('Modules\Commerce\Views\corporate\request', [
            'courses'      => $courses,
            'countries'    => self::COUNTRIES,
            'teamSizes'    => self::TEAM_SIZES,
            'modes'        => self::MODES,
            'budgetBands'  => $this->budgetBands($currency),
            'currency'     => $currency,
            'prefill'      => $this->prefilledCourse($courses),
            // Carried onto the form's own action so the campaign that produced
            // the visit is still on the request that produces the lead. See
            // utmQuery() for why this is not optional.
            'utmQuery'     => $this->utmQuery(),
            'errors'       => session()->getFlashdata('errors') ?? [],
            // Set only by a successful submit, so the thank-you state cannot be
            // reached by typing a URL and cannot be reloaded into a second
            // enquiry. It carries the reference the visitor should quote.
            'reference'    => session()->getFlashdata('quote_reference'),
            'crumbs'       => $crumbs,
            'schema'       => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'        => lang('Commerce.corporate.form.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Commerce.corporate.form.meta'),
            // Declared explicitly because the layout's default is current_url(),
            // which keeps `?course=`. Thirty course pages each linking here
            // would otherwise be thirty near-identical addresses competing with
            // one another for the same query.
            'canonical'    => locale_url('corporate/request-quote'),
        ]);
    }

    /**
     * Store the enquiry, tell the school, and thank them.
     *
     * The order is the whole of the design: validate, score, throttle, **save**,
     * then email. Everything before the save can refuse the request; nothing
     * after it may.
     */
    public function submit(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        // The campaign parameters travel with every redirect this method can
        // make, or a visitor who trips a validation error arrives back at a
        // form that has forgotten where they came from and the enquiry they
        // then send is attributed to nothing.
        $back = locale_url('corporate/request-quote') . $this->utmQuery();

        // Honeypot. A field no human ever sees, filled by nearly every scripted
        // submission. Answered with the same redirect a success gets, so the
        // script learns nothing about why it failed.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to($back);
        }

        $courses     = $this->courseOptions();
        $budgetBands = $this->budgetBands(current_currency());

        $rules = [
            'company'         => ['label' => lang('Commerce.corporate.form.company'), 'rules' => 'required|max_length[191]'],
            'name'            => ['label' => lang('Commerce.corporate.form.name'), 'rules' => 'required|max_length[128]'],
            'email'           => ['label' => lang('Commerce.corporate.form.email'), 'rules' => 'required|valid_email|max_length[191]'],
            'phone'           => ['label' => lang('Commerce.corporate.form.phone'), 'rules' => 'permit_empty|max_length[48]'],
            // The three closed lists carry their own messages. CodeIgniter's
            // default for in_list prints every permitted value, which for the
            // country field is forty codes across the page — and the only way
            // to fail one of these is a forged or stale form, so the message
            // is read by nobody it could help.
            'country'         => [
                'label'  => lang('Commerce.corporate.form.country'),
                'rules'  => 'required|in_list[' . implode(',', array_keys(self::COUNTRIES)) . ']',
                'errors' => ['in_list' => lang('Commerce.corporate.form.err_country')],
            ],
            'team_size'       => [
                'label'  => lang('Commerce.corporate.form.team_size'),
                'rules'  => 'required|in_list[' . implode(',', self::TEAM_SIZES) . ']',
                'errors' => ['in_list' => lang('Commerce.corporate.form.err_team_size')],
            ],
            'mode'            => [
                'label'  => lang('Commerce.corporate.form.mode'),
                'rules'  => 'required|in_list[' . implode(',', self::MODES) . ']',
                'errors' => ['in_list' => lang('Commerce.corporate.form.err_mode')],
            ],
            'preferred_dates' => ['label' => lang('Commerce.corporate.form.dates'), 'rules' => 'permit_empty|max_length[191]'],
            'location'        => ['label' => lang('Commerce.corporate.form.location'), 'rules' => 'permit_empty|max_length[128]'],
            'courses_other'   => ['label' => lang('Commerce.corporate.form.courses_other'), 'rules' => 'permit_empty|max_length[191]'],
            'message'         => ['label' => lang('Commerce.corporate.form.message'), 'rules' => 'permit_empty|max_length[4000]'],
        ];

        // The budget question is only asked when there are bands published in
        // this visitor's currency, so it is only validated then. Adding the
        // rule unconditionally would reject every submission from a currency
        // whose select was never rendered.
        if ($budgetBands !== []) {
            $rules['budget_band'] = [
                'label'  => lang('Commerce.corporate.form.budget'),
                'rules'  => 'permit_empty|in_list[not_sure,' . implode(',', array_keys($budgetBands)) . ']',
                'errors' => ['in_list' => lang('Commerce.corporate.form.err_budget')],
            ];
        }

        if (! $this->validate($rules)) {
            return redirect()->to($back)->withInput()->with('errors', $this->validator->getErrors());
        }

        // Only when reCAPTCHA is fully configured *and* switched on for this
        // form. A site with no keys accepts the enquiry rather than rejecting
        // everybody, which is how the rest of the platform already behaves.
        if (Recaptcha::guards(self::FORM)) {
            $check = Recaptcha::verify($this->request->getPost('recaptcha_token'), self::FORM);
            if (! ($check['ok'] ?? false)) {
                log_message('warning', 'Corporate quote request refused by reCAPTCHA: ' . ($check['reason'] ?? ''));

                return redirect()->to($back)->withInput()->with('error', lang('Site.booking.err_robot'));
            }
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));

        // Throttled after validation, not before: `check()` spends a token on
        // every call, so counting failed validations against the limit would
        // lock out somebody who mistyped their address twice.
        $throttle = service('throttler');
        $allowed  = $throttle->check(md5('corporate-ip-' . $this->request->getIPAddress()), self::PER_IP, HOUR)
            && $throttle->check(md5('corporate-' . $email), self::PER_EMAIL, HOUR);

        if (! $allowed) {
            return redirect()->to($back)->withInput()->with('error', lang('Commerce.corporate.form.throttled'));
        }

        $chosen = $this->chosenCourses($courses);
        $budget = $this->chosenBudget($budgetBands, current_currency());

        $lead = [
            'type'            => self::LEAD_TYPE,
            'name'            => trim((string) $this->request->getPost('name')),
            'email'           => $email,
            'phone'           => trim((string) $this->request->getPost('phone')),
            'company'         => trim((string) $this->request->getPost('company')),
            'country'         => strtoupper((string) $this->request->getPost('country')),
            'team_size'       => (string) $this->request->getPost('team_size'),
            // Titles are snapshotted alongside the slugs. A course renamed six
            // months from now must not silently rewrite what this company
            // asked for, and a course withdrawn must not erase it.
            'courses_json'    => json_encode($chosen, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'preferred_dates' => trim((string) $this->request->getPost('preferred_dates')),
            'mode'            => (string) $this->request->getPost('mode'),
            'location'        => trim((string) $this->request->getPost('location')),
            'budget_band'     => $budget['stored'],
            'message'         => trim((string) $this->request->getPost('message')),
            // Everything that has no column of its own, kept rather than
            // dropped: the exact band bounds in minor units so a quote can be
            // written against the same numbers the visitor was shown, the
            // course they could not find in the list, and the marketing
            // consent, which is a separate permission from making an enquiry.
            'payload_json'    => json_encode([
                'locale'        => current_locale(),
                'currency'      => current_currency(),
                'budget'        => $budget['bounds'],
                'courses_other' => trim((string) $this->request->getPost('courses_other')),
                'marketing_opt_in' => $this->request->getPost('marketing_opt_in') ? 1 : 0,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'source'          => uri_string(),
            'utm_json'        => LeadModel::utm(),
            'status'          => 'new',
        ];

        $leadId = (new LeadModel())->insert($lead, true);

        // The one failure that must be admitted rather than smoothed over. A
        // thank-you page shown for an enquiry that was never stored is the
        // worst outcome available here: the company waits, hears nothing, and
        // concludes the school ignored them.
        if (! $leadId) {
            log_message('error', 'A corporate quote request could not be stored for {email}.', ['email' => $email]);

            return redirect()->to($back)->withInput()->with('error', lang('Commerce.corporate.form.failed'));
        }

        // Saved. From here nothing may change the outcome of this request.
        $this->notify($lead, (int) $leadId, $chosen, $budget['label_parts']);

        return redirect()->to($back)->with('quote_reference', $this->reference((int) $leadId));
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * The published editorial blocks of the CMS page, if there is one.
     *
     * A `pagehero` is removed rather than rendered: this controller already
     * draws the band at the top of the page, and the two together produce a
     * page with two headings and eight inches of empty space between them.
     *
     * @return list<array{type:string, content:array}>
     */
    private function editorialBlocks(?array $page): array
    {
        if ($page === null) {
            return [];
        }

        $out = [];
        foreach ($page['sections'] ?? [] as $section) {
            foreach ($section['blocks'] ?? [] as $block) {
                // Sanitised because the type reaches the filesystem in the
                // view: a block type is editable in the admin, and a slash in
                // it would otherwise address any view in the application.
                $type = preg_replace('/[^a-z0-9_]/i', '', (string) ($block['type'] ?? 'richtext'));
                if ($type === 'pagehero') {
                    continue;
                }

                $out[] = [
                    'type'    => $type,
                    'content' => json_decode((string) ($block['content'] ?? '[]'), true) ?: [],
                ];
            }
        }

        return $out;
    }

    /**
     * Every published course, grouped by pillar, for the "courses of interest"
     * checkboxes.
     *
     * The whole catalogue, not just what happens to be scheduled: a private
     * cohort has no public date by definition, and filtering this list by the
     * calendar would hide exactly the courses a company is most likely to want
     * run for them alone.
     *
     * @return array<string, list<array{slug:string, title:string}>>
     */
    private function courseOptions(): array
    {
        // Memoised: `submit()` asks for this list to validate against and the
        // form asks for it to render, and on the failure path both happen in
        // one request.
        static $grouped = null;
        if ($grouped !== null) {
            return $grouped;
        }

        $rows = (new \Modules\Catalog\Models\CourseModel())
            ->live()
            ->select('courses.slug, courses.title, courses.pillar')
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            $pillar = (string) ($row['pillar'] ?: 'adobe');
            $out[$pillar][] = ['slug' => (string) $row['slug'], 'title' => t_field($row['title'])];
        }

        // Sorted on the resolved title rather than in SQL: the column is a JSON
        // locale map, and ordering by it orders by whichever language happens
        // to sit first inside the JSON.
        foreach ($out as &$list) {
            usort($list, static fn (array $a, array $b): int => strcasecmp($a['title'], $b['title']));
        }
        unset($list);

        // A stable pillar order so the three groups do not swap places between
        // page loads as courses are added.
        $ordered = [];
        foreach (['adobe', 'ai', 'design'] as $pillar) {
            if (! empty($out[$pillar])) {
                $ordered[$pillar] = $out[$pillar];
            }
        }

        return $grouped = $ordered + $out;
    }

    /**
     * The slug named by `?course=`, if it is one this form actually offers.
     *
     * @param array<string, list<array{slug:string, title:string}>> $courses
     */
    private function prefilledCourse(array $courses): string
    {
        $slug = trim((string) $this->request->getGet('course'));
        if ($slug === '') {
            return '';
        }

        foreach ($courses as $list) {
            foreach ($list as $course) {
                if ($course['slug'] === $slug) {
                    return $slug;
                }
            }
        }

        return '';
    }

    /**
     * The ticked courses, resolved against the catalogue.
     *
     * A slug that is not in the offered list is dropped in silence rather than
     * rejected: it means a forged field or a course withdrawn between the form
     * being rendered and being sent, and neither is a reason to lose an
     * enquiry that is otherwise complete.
     *
     * @param array<string, list<array{slug:string, title:string}>> $courses
     * @return list<array{slug:string, title:string}>
     */
    private function chosenCourses(array $courses): array
    {
        $posted = $this->request->getPost('courses');
        if (! is_array($posted)) {
            return [];
        }

        $known = [];
        foreach ($courses as $list) {
            foreach ($list as $course) {
                $known[$course['slug']] = $course['title'];
            }
        }

        $out = [];
        foreach ($posted as $slug) {
            $slug = (string) $slug;
            if (isset($known[$slug]) && ! isset($out[$slug])) {
                $out[$slug] = ['slug' => $slug, 'title' => $known[$slug]];
            }
        }

        return array_values($out);
    }

    /**
     * The budget bands published in this currency.
     *
     * Empty for a currency nobody has set bands for, and the form then does not
     * ask the question at all. Showing a Sri Lankan rupee band to a buyer being
     * quoted in dollars is worse than not asking: they answer it, and the
     * answer is wrong by a factor of three hundred.
     *
     * @return array<string, array{min:?int, max:?int}> minor units
     */
    private function budgetBands(string $currency): array
    {
        $table = self::BUDGET_BANDS[strtoupper($currency)] ?? [];

        $out = [];
        foreach ($table as $code => [$min, $max]) {
            $out[$code] = ['min' => $min, 'max' => $max];
        }

        return $out;
    }

    /**
     * The chosen band, in the three forms the rest of the request needs.
     *
     * `stored` goes in `training_leads.budget_band` and carries the currency,
     * because "band_2" on its own is meaningless to whoever opens the lead.
     * `bounds` goes in the payload as integer minor units, so a quote is
     * written against the same numbers the visitor was actually shown even
     * after the bands here are re-tuned. `label_parts` is for the notification
     * email, which has no view and no `money()` helper loaded.
     *
     * @param array<string, array{min:?int, max:?int}> $bands
     * @return array{stored:string, bounds:?array, label_parts:array{min:?int, max:?int, currency:string}|null}
     */
    private function chosenBudget(array $bands, string $currency): array
    {
        $code = (string) $this->request->getPost('budget_band');

        if ($code === '' || $code === 'not_sure' || ! isset($bands[$code])) {
            return ['stored' => 'not_sure', 'bounds' => null, 'label_parts' => null];
        }

        $band = $bands[$code];

        return [
            'stored'      => strtoupper($currency) . ':' . $code,
            'bounds'      => ['currency' => strtoupper($currency), 'min_cents' => $band['min'], 'max_cents' => $band['max']],
            'label_parts' => ['min' => $band['min'], 'max' => $band['max'], 'currency' => strtoupper($currency)],
        ];
    }

    /**
     * The campaign parameters on this request, as a query string to hang off
     * the form's action and off every redirect back to it.
     *
     * `LeadModel::utm()` reads the query string of the request that creates the
     * lead — and that request is a POST to a bare address, which carries none.
     * Its second source is a first-touch cookie that nothing on the platform
     * writes yet, so without this the attribution on every corporate lead would
     * be an empty object, and "which channel produced enquiries" would be a
     * question with no answer at all. Keeping the parameters on the action is
     * the smallest fix that does not depend on a cookie existing; the canonical
     * of the page is declared separately and stays clean, so none of this
     * reaches an index.
     *
     * @return string '' or '?utm_source=…', already encoded
     */
    private function utmQuery(): string
    {
        $out = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'] as $key) {
            $value = $this->request->getGet($key);
            if (is_string($value) && $value !== '') {
                $out[$key] = mb_substr($value, 0, 128);
            }
        }

        return $out === [] ? '' : '?' . http_build_query($out);
    }

    /** The reference a visitor is asked to quote when they follow up. */
    private function reference(int $leadId): string
    {
        return 'RFQ-' . str_pad((string) $leadId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Pass the enquiry to whoever handles quotes.
     *
     * Wrapped end to end. The lead is already committed by the time this runs,
     * and no exception thrown by a mail server may be allowed to turn a saved
     * enquiry into an error page — the visitor would send it again, or worse,
     * conclude the school is not taking enquiries.
     *
     * @param list<array{slug:string, title:string}> $courses
     * @param array{min:?int, max:?int, currency:string}|null $budget
     */
    private function notify(array $lead, int $leadId, array $courses, ?array $budget): void
    {
        try {
            if (! Mailer::isConfigured()) {
                // Not an error. Until an editor sets an SMTP host the site
                // still takes enquiries and still shows them in the console;
                // it simply cannot tell anybody by email yet.
                log_message('info', 'Corporate lead {id} saved; no SMTP host is configured so nothing was sent.', ['id' => $leadId]);

                return;
            }

            $to = Mailer::bookingRecipients() ?: Mailer::contactRecipients();
            if ($to === '') {
                log_message('warning', 'Corporate lead {id} saved, but no recipient address is configured.', ['id' => $leadId]);

                return;
            }

            $result = Mailer::send(
                $to,
                sprintf('%s — %s (%s)', $this->reference($leadId), $lead['company'], setting('site_name', '')),
                $this->notificationBody($lead, $leadId, $courses, $budget),
                // Reply-To is the enquirer, so answering the notification
                // answers the company rather than the website.
                $lead['email'],
            );

            if (! $result['sent']) {
                log_message('error', 'Corporate lead {id} saved, but the notification failed: {why}', [
                    'id'  => $leadId,
                    'why' => $result['detail'] ?: $result['error'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Corporate lead {id} saved, but the notification threw: {msg}', [
                'id'  => $leadId,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The enquiry as a sales person needs to read it.
     *
     * Built here rather than in a view because it is the only email this module
     * sends and it is a flat table of the fields that were typed. Every one of
     * them is shown rather than summarised: whoever opens this is about to
     * price a programme, and a field they have to come back for is a day lost.
     *
     * Money is formatted from the stored minor units at this single point, and
     * only for display — nothing is recomputed from it.
     *
     * @param list<array{slug:string, title:string}> $courses
     * @param array{min:?int, max:?int, currency:string}|null $budget
     */
    private function notificationBody(array $lead, int $leadId, array $courses, ?array $budget): string
    {
        helper('commerce');

        $payload = json_decode((string) $lead['payload_json'], true) ?: [];

        $budgetText = '—';
        if ($budget !== null) {
            $budgetText = $budget['min'] === null
                ? 'up to ' . money($budget['max'], $budget['currency'])
                : ($budget['max'] === null
                    ? 'over ' . money($budget['min'], $budget['currency'])
                    : money($budget['min'], $budget['currency']) . ' – ' . money($budget['max'], $budget['currency']));
        }

        $rows = [
            'Reference'        => $this->reference($leadId),
            'Company'          => $lead['company'],
            'Contact'          => $lead['name'],
            'Email'            => $lead['email'],
            'Phone'            => $lead['phone'],
            'Country'          => self::COUNTRIES[$lead['country']] ?? $lead['country'],
            'Team size'        => $lead['team_size'],
            'Courses'          => implode(', ', array_column($courses, 'title')),
            'Other course'     => (string) ($payload['courses_other'] ?? ''),
            'Preferred dates'  => $lead['preferred_dates'],
            'Delivery'         => $lead['mode'],
            'Location'         => $lead['location'],
            'Budget'           => $budgetText,
            'Marketing opt-in' => ! empty($payload['marketing_opt_in']) ? 'yes' : 'no',
            'Source'           => $lead['source'],
            'Attribution'      => (string) $lead['utm_json'],
        ];

        $html = '<h2 style="font-family:Helvetica,Arial,sans-serif;">Request for a corporate quote</h2>'
            . '<table cellpadding="6" cellspacing="0" style="font-family:Helvetica,Arial,sans-serif;font-size:14px;border-collapse:collapse;">';

        foreach ($rows as $label => $value) {
            if (trim((string) $value) === '' || $value === '[]' || $value === '{}') {
                continue;
            }
            $html .= '<tr><th align="left" style="color:#55607a;font-weight:600;vertical-align:top;">' . esc($label) . '</th>'
                . '<td>' . esc((string) $value) . '</td></tr>';
        }

        $html .= '</table>';

        if (trim((string) $lead['message']) !== '') {
            $html .= '<p style="font-family:Helvetica,Arial,sans-serif;font-size:14px;white-space:pre-wrap;">'
                . esc((string) $lead['message']) . '</p>';
        }

        return $html . '<p style="font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#55607a;">'
            . 'Sent from ' . esc(base_url()) . ' — reply to this email to answer them directly.</p>';
    }
}
