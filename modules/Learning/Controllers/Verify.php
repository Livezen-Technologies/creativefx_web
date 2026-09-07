<?php

namespace Modules\Learning\Controllers;

use App\Controllers\BaseController;
use Config\App;
use Config\Services;
use Modules\Learning\Models\CertificateModel;

/**
 * Public certificate verification.
 *
 * This is the endpoint the whole certificate feature exists to serve. A PDF is
 * a picture and anybody can make one that says anything; what makes a
 * certificate worth issuing is that whoever is handed one can check it against
 * a record the holder does not control. So this address is printed on the
 * document, squared into a QR code, and has to work for somebody who has never
 * been to this site, is standing at a desk with a stack of applications, and
 * has about eight seconds.
 *
 * Four decisions are encoded here, and each is a constraint on what this
 * controller is allowed to do.
 *
 * **It sits outside the locale group.** `/verify/{code}`, not
 * `/en/verify/{code}`. The address goes on paper and cannot be reissued, so it
 * must not carry a segment that adding a language, dropping one or moving the
 * catalogue could invalidate — and an HR officer scanning a square has no
 * interest in choosing a language before being told whether a document is real.
 * The cost is that no filter has set a locale for the request; `settleLocale()`
 * below is what pays it.
 *
 * **It answers three ways and never falls silent.** Valid, withdrawn, and no
 * such certificate. A revoked certificate keeps answering, with "withdrawn" as
 * the answer, because a record that simply stops existing is indistinguishable
 * from one that never did — and that difference is the whole difference to the
 * person holding the document.
 *
 * **It says nothing the certificate does not.** The row behind this page also
 * carries a user id, an enrolment, an order behind that, a path to a PDF and
 * whatever note an administrator typed when they withdrew it. Anybody holding
 * a code can open this page, and a code travels wherever the document does, so
 * what the page may show is a whitelist rather than a list of exclusions —
 * see `printedFields()`.
 *
 * **It is rate limited and noindex.** A verification result is somebody's name
 * and what they studied, addressed by a code meant for whoever is holding the
 * paper. It is not something a search engine should hold, and it is not
 * something a script should be able to sweep.
 */
class Verify extends BaseController
{
    /**
     * Checks a minute, per address.
     *
     * Deliberately loose. `verify_code` is twelve characters of hex from
     * `random_bytes()`, so guessing one is hopeless at any rate this limit
     * could plausibly take; what the throttle actually buys is that the
     * endpoint cannot be swept by a script and cannot be made to answer a
     * thousand database queries a second.
     *
     * Loose, specifically, because an HR department checking a stack of
     * applications arrives from one corporate address. A limit tuned the way a
     * login form's is would lock an entire company out of the one page on this
     * site they came here to read, and they would have no idea why.
     */
    private const TRIES_PER_MINUTE = 20;

    public function show(?string $code = null)
    {
        helper(['norlanka', 'catalog', 'url']);

        $this->settleLocale();

        $code = trim((string) $code);

        // Counted before the code is looked at and before its shape is judged.
        // A throttle that only spends a token on plausible codes has told a
        // prober which of their guesses were the right shape, for free.
        $throttler = service('throttler');
        if ($throttler->check(md5('verify-' . $this->request->getIPAddress()), self::TRIES_PER_MINUTE, MINUTE) === false) {
            return $this->response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) max(1, $throttler->getTokenTime()))
                ->setBody($this->page('throttled', null));
        }

        // findByCode() answers null both for a code of the wrong shape and for
        // a well-formed code that is not ours, and the two are deliberately not
        // told apart here. To an honest reader "that is not a valid code" and
        // "no such certificate" are the same fact; to somebody probing they are
        // two, and the second one is a hint. The page says only the second.
        $certificate = (new CertificateModel())->findByCode($code);

        $outcome = match (true) {
            $certificate === null               => 'unknown',
            ! empty($certificate['revoked_at']) => 'revoked',
            default                             => 'valid',
        };

        // 200 for all three. The resource exists and it answered; which answer
        // it gave is in the page, not in the status line. A uniform status also
        // means a prober learns nothing from headers alone, and — the reason
        // that actually matters — a corporate proxy or a captive portal that
        // replaces 404s with a page of its own cannot swallow the answer in
        // front of the person who needs it.
        return $this->page($outcome, $certificate);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Give the request a locale, because no filter has.
     *
     * This route is outside the `(:locale)` group on purpose, so `LocaleFilter`
     * never runs and nothing has set a language beyond CodeIgniter's own
     * Accept-Language negotiation — which always lands on a supported locale,
     * and is the right default for a stranger.
     *
     * The cookie is the exception. A learner who reads this site in Sinhala and
     * follows the verify link from their own account should not be answered in
     * English because their browser's language list was set years ago and never
     * touched. That cookie is the one explicit choice available at this
     * address, so it wins; otherwise the negotiated locale stands.
     */
    private function settleLocale(): void
    {
        $chosen = (string) ($this->request->getCookie('locale') ?? '');

        if (in_array($chosen, config(App::class)->supportedLocales, true)) {
            Services::request()->setLocale($chosen);
        }
    }

    /**
     * The one view, in whichever of its four states this request reached.
     *
     * The code that was asked for is deliberately not handed on. Echoing it
     * would help somebody who mistyped, but it would also put an arbitrary
     * string from the address bar onto a page whose URL is printed on other
     * people's documents — and the "no such certificate" copy already tells the
     * reader to check the characters against the paper, which is the same help
     * without the reflection.
     */
    private function page(string $outcome, ?array $certificate): string
    {
        return view('Modules\Learning\Views\verify', [
            'outcome'         => $outcome,
            'certificate'     => $certificate === null ? null : $this->printedFields($certificate),
            'title'           => lang('Learning.verify.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Learning.verify.meta'),
            'noIndex'         => true,
            // Empty rather than the current URL, which suppresses the layout's
            // canonical tag *and* the hreflang block that hangs off it. Those
            // alternates are built by swapping a locale segment out of the
            // canonical, and this address has none — so every one of them would
            // come out as this same URL, announced as if it had a language.
            'canonical'       => '',
        ]);
    }

    /**
     * Only what is on the paper.
     *
     * A whitelist rather than an unset() list, because this is the shape that
     * stays safe when somebody adds a column to `certificates` next year. The
     * row carries a user id, an enrolment id, an order behind that, a path to a
     * PDF under `writable/` and a revocation note, and none of it belongs on a
     * page that anybody holding a code can open.
     *
     * `title` and `learner_name` are the snapshots frozen at issue, not the
     * course's current name or the name on the learner's profile today. This
     * record has to say what was awarded and to whom, not what either of them
     * happens to be called this year — and it is what the printed document
     * says, which is the whole point of being able to check one against the
     * other.
     *
     * `revoke_reason` is excluded deliberately. The *date* of a withdrawal is a
     * fact the holder is entitled to and the page states it plainly. The
     * school's internal sentence about why is a statement about a named person,
     * written for colleagues, and published to anybody with the code it would
     * be a disclosure nobody reviewed for that audience.
     */
    private function printedFields(array $certificate): array
    {
        return [
            'learner_name' => (string) $certificate['learner_name'],
            'title'        => (string) $certificate['title'],
            'mode'         => (string) ($certificate['mode'] ?? ''),
            'hours'        => (int) ($certificate['hours'] ?? 0),
            'issued_at'    => $certificate['issued_at'] ?? null,
            'serial'       => (string) $certificate['serial'],
            // Safe to show: it is already in the address bar and printed under
            // the QR square. Repeating it lets the reader confirm the page they
            // are looking at is the one they asked for.
            'verify_code'  => (string) $certificate['verify_code'],
            'revoked_at'   => $certificate['revoked_at'] ?? null,
        ];
    }
}
