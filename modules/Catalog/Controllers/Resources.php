<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\ResourceModel;
use Modules\Commerce\Models\LeadModel;

/**
 * The lead magnets — the widest part of the funnel.
 *
 * Nobody arrives here intending to spend money, and that is the whole design.
 * A cheat sheet is read by ten times the number of people who will ever open a
 * course page, it ranks for queries a course page cannot ("photoshop keyboard
 * shortcuts" is not a buying query), and the address left behind is the start
 * of the relationship that eventually buys. The blueprint budgets for this as a
 * workstream rather than a launch afterthought.
 *
 * Three decisions are made in this controller rather than in the views.
 *
 * **The file is served by this controller, never linked to directly.** A bare
 * `<a href="/uploads/…">` would be faster to write and would break three
 * things: the download count would miss every direct hit and every share of the
 * link, a draft resource's file would be fetchable by anybody who guessed the
 * path, and the exchange the gate exists for could be skipped by anyone who
 * viewed source once. `download()` is the only route to the bytes, and it goes
 * through `findLive()` first.
 *
 * **The gate is a POST, including for an ungated resource.** An ungated one
 * still submits a one-button form rather than following a link, because a
 * counter incremented by GET is a counter incremented by every crawler,
 * link-preview bot and prefetching browser that ever sees the page — and a
 * download figure inflated by robots is one the marketing team will make real
 * decisions on.
 *
 * **A resource with no file is still a page, and still takes the address.** The
 * asset announced before the designer has finished it is the most valuable one
 * to collect against: everybody who asks for it is somebody who wants it enough
 * to have said so. Nothing pretends the file exists — the page says it is being
 * written, the button says we will send it, and `download_count` is untouched
 * because nothing was downloaded.
 */
class Resources extends BaseController
{
    /** The `training_leads.type` these enquiries carry. */
    private const LEAD_TYPE = 'resource';

    /**
     * How many downloads one address, and one machine, may take in an hour.
     *
     * Loose on purpose. This throttle exists to stop the counter and the leads
     * table being filled by a script, not to stop somebody collecting four
     * cheat sheets in one sitting — which is exactly the behaviour the page is
     * trying to produce. The machine limit is the wider of the two, because an
     * office or a mobile carrier is one address to us and many people to
     * itself.
     */
    private const PER_EMAIL = 12;
    private const PER_IP    = 60;

    // ── Everything on the shelf ─────────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $resources = (new ResourceModel())->live()->findAll();

        // Whether a file exists is decided once, here, rather than in the loop
        // in the view: `fileFor()` touches the filesystem, and a listing that
        // called it from inside the template would stat the disk twice per card
        // for no benefit.
        foreach ($resources as &$resource) {
            $resource['has_file'] = ResourceModel::fileFor($resource) !== null;
        }
        unset($resource);

        $crumbs = [['label' => lang('Catalog.resources.title')]];

        return view('Modules\Catalog\Views\resources\index', [
            'resources'       => $resources,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => lang('Catalog.resources.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.resources.meta'),
            'canonical'       => locale_url('resources'),
        ]);
    }

    // ── One resource, and the form that hands it over ───────────────────────

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $resource = (new ResourceModel())->findLive((string) $slug);
        if ($resource === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $hasFile = ResourceModel::fileFor($resource) !== null;

        $crumbs = [
            ['label' => lang('Catalog.resources.title'), 'url' => locale_url('resources')],
            ['label' => t_field($resource['title'])],
        ];

        return view('Modules\Catalog\Views\resources\show', [
            'resource'        => $resource,
            'hasFile'         => $hasFile,
            'asksForEmail'    => $this->asksForEmail($resource, $hasFile),
            // The campaign parameters have to ride on the form's action; see
            // utmQuery() for why an enquiry posted to a bare address is an
            // enquiry attributed to nothing.
            'utmQuery'        => $this->utmQuery(),
            'errors'          => session()->getFlashdata('errors') ?? [],
            // Set only by a successful submit against a resource with no file
            // yet, so the thank-you state cannot be reached by typing a URL.
            'queued'          => (bool) session()->getFlashdata('resource_queued'),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => t_field($resource['seo_title']) ?: (t_field($resource['title']) . ' — ' . setting('site_name', '')),
            'metaDescription' => t_field($resource['seo_description']) ?: t_field($resource['summary']),
            'metaKeywords'    => (string) $resource['seo_keywords'],
            'ogImage'         => ! empty($resource['hero_image']) ? media_src($resource['hero_image']) : null,
            // Declared rather than left to the layout's current_url(), which
            // keeps the campaign parameters the form's action put there and
            // would give one page as many addresses as there are adverts.
            'canonical'       => locale_url('resources/' . $resource['slug']),
            'lastUpdated'     => $resource['updated_at'] ?? null,
        ]);
    }

    // ── The exchange ────────────────────────────────────────────────────────

    /**
     * Take the address where one is asked for, then serve the file.
     *
     * The order is the point: validate, throttle, **save the lead**, count the
     * download, serve. Everything before the save may refuse the request;
     * nothing after it may — a visitor who has given their address and been
     * shown an error page has been charged and given nothing.
     */
    public function download(?string $locale = null, ?string $slug = null): DownloadResponse|RedirectResponse
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $resources = new ResourceModel();
        $resource  = $resources->findLive((string) $slug);

        // findLive() rather than find(): a draft resource is not a page, and it
        // is not a file either. Without this the gate could be walked past by
        // posting to the slug of something that has not been published.
        if ($resource === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $back = locale_url('resources/' . $resource['slug']) . $this->utmQuery();
        $file = ResourceModel::fileFor($resource);
        $asks = $this->asksForEmail($resource, $file !== null);

        // Honeypot. A field no human ever sees and nearly every script fills.
        // Answered with the redirect a success gets, so the script learns
        // nothing about why it got no file.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to($back);
        }

        $email = '';
        if ($asks) {
            if (! $this->validate(['email' => 'required|valid_email|max_length[191]'])) {
                return redirect()->to($back)
                    ->withInput()
                    ->with('errors', $this->validator->getErrors())
                    ->with('resource_error', lang('Catalog.waitlist.invalid'));
            }

            $email = strtolower(trim((string) $this->request->getPost('email')));
        }

        // Throttled after validation rather than before: `check()` spends a
        // token on every call, so counting a mistyped address against the limit
        // would lock somebody out for correcting it.
        $throttle = service('throttler');
        $allowed  = $throttle->check(md5('resource-ip-' . $this->request->getIPAddress()), self::PER_IP, HOUR);
        if ($asks) {
            $allowed = $allowed && $throttle->check(md5('resource-' . $email), self::PER_EMAIL, HOUR);
        }

        if (! $allowed) {
            return redirect()->to($back)->withInput()->with('resource_error', lang('Commerce.errors.throttled'));
        }

        if ($asks) {
            $this->recordLead($resource, $email, $file !== null);
        }

        if ($file === null) {
            // Nothing was downloaded, so nothing is counted. Inflating the
            // figure with leads captured against a file that does not exist
            // would make the one number this page produces useless.
            return redirect()->to($back)->with('resource_queued', true);
        }

        $resources->recordDownload((int) $resource['id']);

        return $this->serve($resource, $file);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Does the form on this page ask for an address?
     *
     * Two independent reasons, and either is enough. A gated resource asks
     * because that is what gating means. A resource with no file asks whether
     * it is gated or not, because there is nothing to hand over and an address
     * is the only thing the page can usefully do — an ungated asset that is
     * still being written would otherwise offer a button that does nothing.
     */
    private function asksForEmail(array $resource, bool $hasFile): bool
    {
        return ! $hasFile || (int) $resource['gated'] === 1;
    }

    /**
     * Store the enquiry.
     *
     * A failure here is logged and swallowed rather than shown. The visitor has
     * kept their side of the exchange, and refusing them the file because our
     * database was briefly unavailable turns a lost lead into a lost lead *and*
     * an angry one. The log line is what makes the loss visible to us.
     */
    private function recordLead(array $resource, string $email, bool $delivered): void
    {
        $lead = [
            'type'         => self::LEAD_TYPE,
            'email'        => $email,
            // No column exists for the asset, so it goes in the payload, with
            // the title snapshotted beside the slug: a resource renamed next
            // year must not silently rewrite what this person asked for.
            //
            // `delivered` is the field the marketing team will actually work
            // from. False means the file did not exist when they asked, so this
            // is somebody still owed an email — which is a list that cannot be
            // reconstructed afterwards, because by then the file will exist and
            // every row will look identical.
            'payload_json' => json_encode([
                'resource' => [
                    'slug'  => $resource['slug'],
                    'title' => t_field($resource['title']),
                ],
                'delivered'        => $delivered,
                'locale'           => current_locale(),
                // Consent to be marketed to is a separate permission from
                // asking for a download, and is stored as what it is: never
                // inferred from the request, never defaulted to 1.
                'marketing_opt_in' => $this->request->getPost('marketing_opt_in') ? 1 : 0,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'source'       => uri_string(),
            'utm_json'     => LeadModel::utm(),
            'status'       => 'new',
        ];

        if (! (new LeadModel())->insert($lead, true)) {
            log_message('error', 'A resource lead could not be stored for {email} against {slug}.', [
                'email' => $email,
                'slug'  => $resource['slug'],
            ]);
        }
    }

    /**
     * Hand over the bytes.
     *
     * The mime type is deliberately left unset, which makes it
     * `application/octet-stream`: a PDF served with its real type opens in the
     * browser's own viewer on most desktops, and the visitor who wanted a
     * printable sheet pinned by their screen ends up with a tab instead of a
     * file. This is a download, so it downloads.
     *
     * The name comes from the resource's title rather than from whatever the
     * file was called on the designer's machine, because that name is what
     * appears in a downloads folder six weeks later.
     */
    private function serve(array $resource, string $file): DownloadResponse
    {
        $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        $name      = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', t_field($resource['title'])), '-');

        // A title in a non-Latin script leaves nothing behind, so the slug is
        // the fallback rather than an empty filename.
        if ($name === '') {
            $name = (string) $resource['slug'];
        }

        return $this->response->download($file, null)
            ->setFileName($name . ($extension !== '' ? '.' . $extension : ''));
    }

    /**
     * The campaign parameters, back as a query string.
     *
     * `LeadModel::utm()` reads them from the query string of the request that
     * creates the lead — and that request is this POST. A form posting to a
     * bare address produces a lead attributed to nothing, which makes "which
     * advert produced these downloads" a question with no answer. Keeping the
     * parameters on the action, and on every redirect back to the page, is the
     * smallest fix that does not depend on a first-touch cookie existing. The
     * canonical is declared separately and stays clean, so none of this reaches
     * an index.
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
}
