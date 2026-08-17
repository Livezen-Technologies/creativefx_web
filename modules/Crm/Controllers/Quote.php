<?php

namespace Modules\Crm\Controllers;

use App\Controllers\BaseController;
use Modules\Crm\Models\LeadModel;
use Modules\Gear\Models\GearItemModel;
use Modules\Services\Models\ServiceModel;
use Throwable;

/**
 * The quote request flow — the site's primary conversion path. Every "Request a
 * Quote" button on the site lands here, and each request becomes a `leads` row
 * with source 'quote-form' that the team works in Admin -> Quote Requests.
 *
 * The form is presented as a six-step wizard, but it is one ordinary POST: the
 * whole form is in the DOM on first paint and submits in full without
 * JavaScript. The server is the only validator that counts.
 *
 * Briefs and reference files are stored privately under WRITEPATH, never under
 * public/ — admins download them through the authenticated panel, the same way
 * CVs work in Careers.
 */
class Quote extends BaseController
{
    /**
     * The six services, used to label and validate step one when the `services`
     * table cannot be read — a fresh install before seeding, or the test
     * database group, which is deliberately not provisioned. The registry is
     * the source of truth whenever it is reachable.
     */
    private const FALLBACK_SERVICES = [
        'photography-videography'  => 'Photography & Videography',
        'podcast-studio'           => 'Podcast Studio',
        'live-streaming'           => 'Live Streaming',
        'gear-renting'             => 'Gear Renting',
        'social-media-advertising' => 'Social Media Advertising',
        'digital-marketing'        => 'Digital Marketing',
    ];

    private const PROJECT_TYPES = [
        'Corporate',
        'Product',
        'Event',
        'Wedding',
        'Brand campaign',
        'Social media',
        'Podcast',
        'Live stream',
        'Other',
    ];

    /** Budget bands in LKR, the wording the sales team quotes in. */
    private const BUDGETS = [
        'Under LKR 100,000',
        'LKR 100,000 – 300,000',
        'LKR 300,000 – 750,000',
        'LKR 750,000 – 1.5M',
        'Over LKR 1.5M',
        'Not sure yet',
    ];

    /** Relative to WRITEPATH. Outside the document root by construction. */
    private const UPLOAD_DIR = 'uploads/quotes/';

    private const HERO_IMAGE = '/media/placeholders/hero-contact.svg';

    public function index(?string $locale = null)
    {
        helper('norlanka');

        $services = $this->services();

        // ?service=<slug> pre-selects step one, so a click from a service page
        // or a gear card arrives already answered. An unknown slug just leaves
        // the step blank rather than 404ing — the visitor came here to ask for
        // something, and the form still works.
        $selected = $this->request->getGet('service');
        if (! is_string($selected) || ! isset($services[$selected])) {
            $selected = '';
        }

        $siteName = setting('site_name', 'CreativeFX');

        return view('Modules\Crm\Views\quote\form', [
            'services'        => $services,
            'projectTypes'    => self::PROJECT_TYPES,
            'budgets'         => self::BUDGETS,
            'selectedService' => $selected,
            'messagePrefill'  => $this->itemNote(),
            'heroImage'       => self::HERO_IMAGE,
            'title'           => 'Request a Quote — ' . $siteName,
            'metaDescription' => 'Tell us about your shoot, stream, podcast or campaign and we will come back with a costed proposal in LKR within one working day.',
            'ogImage'         => self::HERO_IMAGE,
        ]);
    }

    public function submit(?string $locale = null)
    {
        helper('norlanka');

        // Honeypot: bots fill every field; humans never see this one. Anything
        // other than an untouched text box — including an array posted to
        // confuse the check — is treated as a bot.
        $honeypot = $this->request->getPost('website');
        if ($honeypot !== null && (! is_string($honeypot) || trim($honeypot) !== '')) {
            return redirect()->back();
        }

        $services = $this->services();

        $rules = [
            'name'           => 'required|max_length[128]',
            'email'          => 'required|valid_email|max_length[191]',
            'phone'          => 'permit_empty|max_length[48]',
            'company'        => 'permit_empty|max_length[191]',
            // Checked against the registry, not the markup: the select is a
            // suggestion, this is the rule.
            'service'        => 'required|in_list[' . implode(',', array_keys($services)) . ']',
            'location'       => 'permit_empty|max_length[191]',
            // A DATE column will not take a half-typed date, so it is caught
            // here and told back to the visitor rather than thrown by the DB.
            'preferred_date' => 'permit_empty|valid_date[Y-m-d]',
            'message'        => 'required|min_length[10]',
        ];

        // The file rules pass an empty upload but reject a missing field, so
        // they are only applied when the form actually carried one.
        $upload = $this->request->getFile('attachment');
        if ($upload !== null) {
            $rules['attachment'] = 'max_size[attachment,8192]|ext_in[attachment,pdf,jpg,jpeg,png,zip]';
        }

        $messages = [
            'name'    => ['required' => 'Tell us who to reply to.'],
            'email'   => [
                'required'    => 'We need an email address to send the quote to.',
                'valid_email' => 'That email address does not look right.',
            ],
            'service' => [
                'required' => 'Choose the service you need a quote for.',
                'in_list'  => 'Choose the service you need a quote for.',
            ],
            'preferred_date' => ['valid_date' => 'Enter the preferred date as YYYY-MM-DD.'],
            'message'        => [
                'required'   => 'Tell us about the project.',
                'min_length' => 'Tell us a little more about the project — a sentence or two is plenty.',
            ],
            'attachment' => [
                'max_size' => 'The attachment must be 8MB or smaller.',
                'ext_in'   => 'The attachment must be a PDF, JPG, PNG or ZIP file.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $attachment = null;
        if ($upload !== null && $upload->isValid() && ! $upload->hasMoved()) {
            $dir = WRITEPATH . self::UPLOAD_DIR;
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            // Random name: the visitor's filename never reaches the filesystem.
            $stored = $upload->getRandomName();
            $upload->move($dir, $stored);
            $attachment = self::UPLOAD_DIR . $stored;
        }

        // Optional text: an empty box is a null column, not an empty string, so
        // "no company given" reads the same however the request arrived.
        $blank = static function ($value): ?string {
            $value = is_string($value) ? trim($value) : '';

            return $value !== '' ? $value : null;
        };

        // The two pickers are whitelisted rather than validated. They are
        // context for the sales call, and dropping a real enquiry over a value
        // the visitor cannot even type would cost far more than it saves.
        $projectType = (string) $blank($this->request->getPost('project_type'));
        $budget      = (string) $blank($this->request->getPost('budget'));

        $leadId = (new LeadModel())->insert([
            'name'           => (string) $blank($this->request->getPost('name')),
            'email'          => (string) $blank($this->request->getPost('email')),
            'company'        => $blank($this->request->getPost('company')),
            'phone'          => $blank($this->request->getPost('phone')),
            'service'        => (string) $this->request->getPost('service'),
            'project_type'   => in_array($projectType, self::PROJECT_TYPES, true) ? $projectType : null,
            'budget'         => in_array($budget, self::BUDGETS, true) ? $budget : null,
            'preferred_date' => $blank($this->request->getPost('preferred_date')),
            'location'       => $blank($this->request->getPost('location')),
            'attachment'     => $attachment,
            'message'        => (string) $blank($this->request->getPost('message')),
            'status'         => 'new',
            // Fixed here rather than read from a hidden field: the report of
            // where leads come from is only worth keeping if it cannot be
            // rewritten by whoever is posting.
            'source'         => 'quote-form',
            'locale'         => current_locale(),
        ]);

        if ($leadId === false) {
            return redirect()->back()->withInput()->with('errors', [
                'form' => 'We could not record your request. Please try again, or email us directly.',
            ]);
        }

        return redirect()->to(locale_url('quote/thanks'))
            ->with('quoteReference', 'CFX-' . str_pad((string) $leadId, 5, '0', STR_PAD_LEFT));
    }

    /**
     * The footer newsletter signup.
     *
     * Deliberately its own endpoint rather than a mode of submit(): a signup
     * supplies nothing but an email, so putting it through the quote
     * validator would bounce every subscriber to the quote page with a list
     * of errors about fields the footer never asked them for.
     *
     * It lands in the same lead inbox, tagged 'newsletter', so signups are not
     * stranded in a third-party list.
     */
    public function subscribe(?string $locale = null)
    {
        helper('norlanka');

        if (! $this->validate(['email' => 'required|valid_email|max_length[191]'], [
            'email' => ['required' => 'Enter an email address.', 'valid_email' => 'That email address does not look right.'],
        ])) {
            return redirect()->back()->withInput()->with('error', 'That email address does not look right.');
        }

        $email = trim((string) $this->request->getPost('email'));
        $leads = new LeadModel();

        // Signing up twice is not an error — say thank you either way rather
        // than telling someone they are already on a list.
        if ($leads->where('email', $email)->where('source', 'newsletter')->first() === null) {
            $leads->insert([
                'name'   => '',
                'email'  => $email,
                'status' => 'new',
                'source' => 'newsletter',
                'locale' => current_locale(),
            ]);
        }

        return redirect()->back()->with('message', lang('Site.footer.newsletter.success'));
    }

    public function thanks(?string $locale = null)
    {
        helper('norlanka');

        $siteName = setting('site_name', 'CreativeFX');

        return view('Modules\Crm\Views\quote\thanks', [
            // Empty when the page is opened directly rather than reached by
            // submitting; the page reads sensibly either way.
            'reference'       => trim((string) session('quoteReference')),
            'title'           => 'Request received — ' . $siteName,
            'metaDescription' => 'Your quote request has reached the CreativeFX team in Colombo.',
        ]);
    }

    /**
     * slug => display name for the six services, read from the registry so the
     * wording on this form matches the header, the footer and the service
     * pages. Falls back to the constant when the table cannot be read.
     *
     * @return array<string, string>
     */
    private function services(): array
    {
        try {
            $services = [];

            foreach ((new ServiceModel())->published() as $row) {
                $slug = (string) ($row['slug'] ?? '');

                // These slugs go straight into an in_list rule, whose separator
                // is a comma — anything that is not a plain slug is not a
                // service, and is dropped rather than allowed to split a rule.
                if (preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $slug) !== 1) {
                    continue;
                }

                $name            = trim(t_field($row['name'] ?? []));
                $services[$slug] = $name !== '' ? $name : $slug;
            }

            if ($services !== []) {
                return $services;
            }
        } catch (Throwable $e) {
            // Registry unreachable — the six services are known regardless.
        }

        return self::FALLBACK_SERVICES;
    }

    /**
     * The opening line for the message box when the visitor arrived from a gear
     * card (?item=<gear-slug>), so a rental enquiry reaches the team already
     * saying which kit it is about. Empty when there is no item.
     */
    private function itemNote(): string
    {
        $slug = $this->request->getGet('item');
        if (! is_string($slug) || preg_match('/^[a-z0-9][a-z0-9-]{0,190}$/', $slug) !== 1) {
            return '';
        }

        // The slug alone already identifies the kit; the catalogue is only
        // consulted to say it in the words the visitor saw on the card.
        $label = $slug;

        try {
            $item = (new GearItemModel())->findPublishedBySlug($slug);
            if ($item !== null) {
                $name = trim(t_field($item['name'] ?? []));
                if ($name !== '') {
                    $label = $name;
                }
            }
        } catch (Throwable $e) {
            // Catalogue unreachable — fall through with the slug.
        }

        return 'Requesting a quote for: ' . $label . "\n\n";
    }
}
