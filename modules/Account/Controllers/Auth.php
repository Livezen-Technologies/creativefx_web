<?php

namespace Modules\Account\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Auth\Models\UserModel;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Models\CartModel;
use Modules\Commerce\Services\PricingService;
use Modules\Core\Libraries\Mailer;
use Modules\Core\Libraries\Recaptcha;

/**
 * The learner's front door: sign in, register, confirm, reset.
 *
 * There was no front-of-site authentication on this codebase at all — the only
 * login was the administrator's — so this is written from scratch on
 * `LearnerAuth`, which owns the session, the throttling, the constant-time
 * password check and the token hashing. Nothing in this controller repeats any
 * of that; it validates what was typed, decides what the visitor is told, and
 * hands the rest over.
 *
 * Three decisions run through the whole file.
 *
 * **The form must not answer "is this person a customer?".** A training school's
 * user table is its customer list. So a wrong password and an unknown address
 * produce the same sentence; registering with an address that already exists
 * produces the same page as registering with one that does not, and sends a
 * letter to the account holder instead; and asking to reset the password of an
 * address that has never been seen produces the same page as asking for a real
 * one. That property is easy to write and easier to lose — a "welcome back"
 * flash, a redirect that only happens in one branch, a validation rule that
 * says "this email is taken" — so each branch says so where it is made.
 *
 * **Registration does not sign anybody in.** It cannot: the already-registered
 * branch has nobody to sign in, and a form that signs in on one path and not
 * on the other has just answered the question above in the plainest possible
 * way. Confirming the address is what signs the learner in.
 *
 * **A GET never spends a token.** Corporate mail gateways fetch every link in
 * a message before a human sees it. A reset link that were redeemed by the GET
 * that renders the form would be used up by the scanner, and the learner would
 * meet a dead link on a page they had asked for thirty seconds earlier.
 */
class Auth extends BaseController
{
    /** Registrations and reset requests per address, and per machine, per hour. */
    private const PER_EMAIL = 5;
    private const PER_IP    = 15;

    /**
     * How long a reset link lives.
     *
     * Shorter than the 24 hours a confirmation gets: a confirmation link that
     * has leaked buys somebody a confirmed address, a reset link buys them the
     * account. Two hours is long enough to survive a slow mail queue and a
     * lunch break, which is the only reason it is not one.
     */
    private const RESET_HOURS = 2;

    /** The reCAPTCHA action name; the switch is Settings → reCAPTCHA → on_register. */
    private const FORM = 'register';

    /**
     * The countries a learner can say they are in.
     *
     * Sri Lanka first because that is where most of these bookings come from,
     * the rest alphabetically. This is the third copy of this list in the
     * codebase — `Corporate::COUNTRIES` and the checkout view hold the other
     * two — and all three should become one Config class. It is duplicated
     * rather than borrowed from another module's *controller* on purpose:
     * reaching across modules for a constant makes registration stop working
     * the day somebody makes that constant private, and it would fail as a
     * fatal error on the busiest form on the site.
     */
    private const COUNTRIES = [
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

    // ── Signing in ──────────────────────────────────────────────────────────

    public function loginForm(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        if (LearnerAuth::check()) {
            return redirect()->to($this->intended());
        }

        return view('Modules\Account\Views\auth\login', [
            'errors'          => session()->getFlashdata('errors') ?? [],
            // The shell draws the heading band, so the copy is chosen here
            // beside the rest of the page's vocabulary rather than inside the
            // form — the same shape the corporate quote page uses.
            'eyebrow'         => lang('Account.login.eyebrow'),
            'heading'         => lang('Account.login.heading'),
            'intro'           => lang('Account.login.intro'),
            'title'           => lang('Account.login.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Account.login.meta'),
            'canonical'       => locale_url('account/login'),
            // A sign-in form has nothing for a crawler and competes in search
            // with the pages that do.
            'noIndex'         => true,
        ]);
    }

    public function login(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'url']);

        $back = locale_url('account/login');

        if (! $this->validate([
            'email'    => ['label' => lang('Account.login.email'), 'rules' => 'required|valid_email|max_length[191]'],
            'password' => ['label' => lang('Account.login.password'), 'rules' => 'required'],
        ])) {
            return redirect()->to($back)->withInput()->with('errors', $this->validator->getErrors());
        }

        // LearnerAuth owns the throttling, the dummy verify that makes an
        // unknown address cost the same time as a wrong password, and the
        // session regeneration. This method only decides what to say.
        $result = LearnerAuth::attempt(
            (string) $this->request->getPost('email'),
            (string) $this->request->getPost('password')
        );

        if (! $result['ok']) {
            return redirect()->to($back)->withInput()->with('error', $this->loginError($result['reason']));
        }

        $user = (array) $result['user'];

        $this->attachCart((int) $user['id']);

        // `learner_intended` survives the sign-in because regenerating a
        // session id carries its contents to the new id; it is read here,
        // after the attempt, so a failed sign-in does not lose the destination.
        return redirect()->to($this->intended())
            ->with('notice', lang('Account.login.welcome', [LearnerAuth::displayName($user)]));
    }

    public function logout(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'url']);

        LearnerAuth::logout();

        // The flash is set after the session has been rotated, so it lands in
        // the session the next request will actually read.
        return redirect()->to(locale_url(''))->with('notice', lang('Account.logout.ok'));
    }

    // ── Registering ─────────────────────────────────────────────────────────

    public function registerForm(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        if (LearnerAuth::check()) {
            return redirect()->to($this->intended());
        }

        // Set only by a submission, so the "check your inbox" panel cannot be
        // reached by typing an address and a reload does not send a second
        // letter. It carries the address, so the panel can name it back.
        $sent = session()->getFlashdata('registered');

        return view('Modules\Account\Views\auth\register', [
            'errors'    => session()->getFlashdata('errors') ?? [],
            'countries' => self::COUNTRIES,
            // Cloudflare's country header when it is there, the browser's
            // Accept-Language region when it is not: a pre-selected country is
            // one fewer thing to answer, and it is what the price the visitor
            // has just been quoted was resolved from.
            'country'   => $this->defaultCountry(),
            'sent'      => $sent,
            'mailOff'   => (bool) session()->getFlashdata('mail_off'),
            'recaptcha' => Recaptcha::guards(self::FORM),

            'eyebrow'         => lang('Account.register.eyebrow'),
            'heading'         => $sent ? lang('Account.register.sent_heading') : lang('Account.register.heading'),
            'intro'           => $sent ? lang('Account.register.sent', [$sent]) : lang('Account.register.intro'),
            'title'           => lang('Account.register.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Account.register.meta'),
            'canonical'       => locale_url('account/register'),
            'noIndex'         => true,
        ]);
    }

    /**
     * Create the account — or, if the address is spoken for, do not, and say
     * exactly the same thing.
     */
    public function register(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'url']);

        $back  = locale_url('account/register');
        $typed = mb_substr(strtolower(trim((string) $this->request->getPost('email'))), 0, 191);

        // Honeypot: a field no human ever sees and nearly every script fills.
        // Answered with the response a success gets, so the script learns
        // nothing about why it failed.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to($back)->with('registered', $typed);
        }

        if (! $this->validate($this->registrationRules())) {
            return redirect()->to($back)->withInput()->with('errors', $this->validator->getErrors());
        }

        if (Recaptcha::guards(self::FORM)) {
            $check = Recaptcha::verify($this->request->getPost('recaptcha_token'), self::FORM);
            if (! ($check['ok'] ?? false)) {
                log_message('warning', 'Registration refused by reCAPTCHA: ' . ($check['reason'] ?? ''));

                return redirect()->to($back)->withInput()->with('error', lang('Site.booking.err_robot'));
            }
        }

        // Throttled after validation, not before: check() spends a token on
        // every call, so counting failed validations against the limit would
        // lock out somebody who mistyped their own address twice.
        if (! $this->withinRate('register', $typed)) {
            return redirect()->to($back)->withInput()->with('error', lang('Account.register.throttled'));
        }

        $users = new UserModel();

        // withDeleted(), because the unique index on `email` does not care that
        // a row was soft-deleted: an address that looks free to findByEmail()
        // can still make the insert fail. Treating it as taken keeps the
        // response identical instead of turning a closed account into a
        // visible error that says one existed.
        $existing = $users->withDeleted()->where('email', $typed)->first();

        if ($existing !== null) {
            // The non-disclosure rule, in one branch. Nothing is created and
            // nothing is said; the letter goes to the person who owns the
            // address, which is the only party entitled to know that somebody
            // just tried to use it.
            //
            // The password is hashed anyway and the result thrown away, for the
            // same reason LearnerAuth runs a dummy password_verify() against a
            // fixed hash when an account does not exist: bcrypt costs about a
            // tenth of a second, and a branch that skips it answers "is this
            // address registered?" with a stopwatch instead of with words.
            password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT);

            if (empty($existing['deleted_at'])) {
                $this->mailExistingAccount($existing);
            }

            return $this->registrationDone($back, $typed);
        }

        $userId = $this->createLearner($typed);

        if ($userId === null) {
            // Admitted rather than smoothed over: a "check your inbox" page for
            // an account that was never written is the worst outcome here.
            // Somebody waits for a letter that will never come and concludes
            // the school is broken, which it is.
            return redirect()->to($back)->withInput()->with('error', lang('Account.register.failed'));
        }

        $this->mailVerification($users->find($userId) ?? ['id' => $userId, 'email' => $typed]);

        return $this->registrationDone($back, $typed);
    }

    /**
     * Confirm an address, and sign its owner in.
     *
     * The one place a link in an email is allowed to change the session, and it
     * is safe for the same reason the reset link is: the token is single-use,
     * time-limited, and only its hash was ever stored.
     */
    public function verify(?string $locale = null, ?string $token = null)
    {
        helper(['norlanka', 'url']);

        $user = LearnerAuth::consumeToken((string) $token, 'verify');
        if ($user === null) {
            return redirect()->to(locale_url('account/login'))->with('error', lang('Account.verify.invalid'));
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            return redirect()->to(locale_url('account/login'))->with('error', lang('Account.login.err_suspended'));
        }

        $users = new UserModel();
        if (empty($user['email_verified_at'])) {
            $users->update((int) $user['id'], ['email_verified_at' => date('Y-m-d H:i:s')]);
        }

        // Re-read before signing in. LearnerAuth::login() copies `verified`
        // into the session from the row it is handed, so logging in with the
        // row as it was a line ago would leave a learner who has just confirmed
        // their address looking unconfirmed until they signed out and back in.
        $user = $users->find((int) $user['id']) ?? $user;

        LearnerAuth::login($user);
        $this->attachCart((int) $user['id']);

        return redirect()->to($this->intended())->with('notice', lang('Account.verify.ok'));
    }

    // ── Forgotten passwords ─────────────────────────────────────────────────

    public function forgotForm(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        $sent = session()->getFlashdata('reset_sent');

        return view('Modules\Account\Views\auth\forgot', [
            'errors'  => session()->getFlashdata('errors') ?? [],
            'sent'    => $sent,
            'mailOff' => (bool) session()->getFlashdata('mail_off'),

            'eyebrow'         => lang('Account.forgot.eyebrow'),
            'heading'         => $sent ? lang('Account.forgot.sent_heading') : lang('Account.forgot.heading'),
            'intro'           => $sent ? lang('Account.forgot.sent', [$sent]) : lang('Account.forgot.intro'),
            'title'           => lang('Account.forgot.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Account.forgot.meta'),
            'canonical'       => locale_url('account/forgot'),
            'noIndex'         => true,
        ]);
    }

    public function forgot(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'url']);

        $back  = locale_url('account/forgot');
        $typed = mb_substr(strtolower(trim((string) $this->request->getPost('email'))), 0, 191);

        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to($back)->with('reset_sent', $typed);
        }

        if (! $this->validate([
            'email' => ['label' => lang('Account.forgot.email'), 'rules' => 'required|valid_email|max_length[191]'],
        ])) {
            return redirect()->to($back)->withInput()->with('errors', $this->validator->getErrors());
        }

        if (! $this->withinRate('forgot', $typed)) {
            return redirect()->to($back)->withInput()->with('error', lang('Account.forgot.throttled'));
        }

        $user = (new UserModel())->findByEmail($typed);

        // A suspended account gets no link. Not for secrecy — the response is
        // the same either way — but because the link could not be used, and a
        // reset that silently achieves nothing is worse than none.
        if ($user !== null && ($user['status'] ?? 'active') !== 'suspended') {
            $this->mailReset($user);
        }

        $response = redirect()->to($back)->with('reset_sent', $typed);

        return Mailer::isConfigured() ? $response : $response->with('mail_off', true);
    }

    /**
     * The form behind a reset link.
     *
     * The token is neither checked nor redeemed here. Redeeming it would hand
     * the reset to whichever mail scanner opened the link first; checking it
     * would mean this controller knowing how LearnerAuth hashes tokens, which
     * is the one piece of knowledge that library exists to keep. The POST finds
     * out, which is the only moment the answer matters.
     */
    public function resetForm(?string $locale = null, ?string $token = null)
    {
        helper(['norlanka', 'url']);

        return view('Modules\Account\Views\auth\reset', [
            'errors' => session()->getFlashdata('errors') ?? [],
            'token'  => (string) $token,

            'eyebrow'         => lang('Account.reset.eyebrow'),
            'heading'         => lang('Account.reset.heading'),
            'intro'           => lang('Account.reset.intro'),
            'title'           => lang('Account.reset.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Account.reset.meta'),
            // Every visit to this page has a different address, because the
            // token is in it. The layout's default canonical is current_url(),
            // which would publish a live reset token in a <link> tag — so it
            // points at the page somebody would actually want to find instead.
            'canonical'       => locale_url('account/forgot'),
            'noIndex'         => true,
        ]);
    }

    public function reset(?string $locale = null, ?string $token = null): RedirectResponse
    {
        helper(['norlanka', 'url']);

        $token = (string) $token;
        $back  = locale_url('account/reset/' . rawurlencode($token));

        // Validated before the token is spent, so a mistyped confirmation costs
        // a retry rather than the whole link and a second trip to the inbox.
        if (! $this->validate([
            'password'         => [
                'label'  => lang('Account.reset.password'),
                'rules'  => 'required|min_length[12]|max_length[72]',
                'errors' => [
                    'min_length' => lang('Account.register.err_password'),
                    'max_length' => lang('Account.register.err_password_long'),
                ],
            ],
            'password_confirm' => [
                'label'  => lang('Account.reset.password_confirm'),
                'rules'  => 'required|matches[password]',
                'errors' => ['matches' => lang('Account.register.err_password_match')],
            ],
        ])) {
            return redirect()->to($back)->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = LearnerAuth::consumeToken($token, 'reset');
        if ($user === null) {
            return redirect()->to(locale_url('account/forgot'))->with('error', lang('Account.reset.invalid'));
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            return redirect()->to(locale_url('account/login'))->with('error', lang('Account.login.err_suspended'));
        }

        $users  = new UserModel();
        $update = [
            'password_hash'  => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            // Anything issued against the old password stops working here.
            'remember_token' => null,
        ];

        // Setting a password through a link sent to the address proves the
        // address. So this is where an account somebody's employer created for
        // them stops being an invitation and becomes theirs — otherwise it
        // would stay `invited` for ever and never count as verified, and the
        // parts of the account area that gate on verification would refuse the
        // person who has just proved they read that inbox.
        if (($user['status'] ?? '') === 'invited') {
            $update['status'] = 'active';
        }
        if (empty($user['email_verified_at'])) {
            $update['email_verified_at'] = date('Y-m-d H:i:s');
        }

        $users->update((int) $user['id'], $update);
        $user = $users->find((int) $user['id']) ?? $user;

        LearnerAuth::login($user);
        $this->attachCart((int) $user['id']);

        return redirect()->to($this->intended())->with('notice', lang('Account.reset.ok'));
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * What a failed sign-in is told.
     *
     * Every reason has its own sentence except the one that must not: a message
     * that separated "no account with that address" from "wrong password"
     * would answer, for anybody who cared to ask, whether a given person has
     * bought training here. An unrecognised reason falls to the same vague
     * line, so a new failure mode added to LearnerAuth cannot leak by default.
     */
    private function loginError(string $reason): string
    {
        return match ($reason) {
            'throttled'  => lang('Account.login.err_throttled'),
            'suspended'  => lang('Account.login.err_suspended'),
            'unverified' => lang('Account.login.err_unverified'),
            default      => lang('Account.login.err_credentials'),
        };
    }

    /** @return array<string, array{label:string, rules:string, errors?:array<string,string>}> */
    private function registrationRules(): array
    {
        return [
            'name'             => ['label' => lang('Account.register.name'), 'rules' => 'required|max_length[128]'],
            'email'            => ['label' => lang('Account.register.email'), 'rules' => 'required|valid_email|max_length[191]'],
            'password'         => [
                'label' => lang('Account.register.password'),
                // The upper bound is not arbitrary. bcrypt reads 72 bytes and
                // silently ignores the rest, so a 90-character passphrase and
                // its first 72 characters are the same password — and the
                // learner believes they chose the longer one. Refusing what
                // cannot be protected is more honest than accepting it.
                'rules'  => 'required|min_length[12]|max_length[72]',
                'errors' => [
                    'min_length' => lang('Account.register.err_password'),
                    'max_length' => lang('Account.register.err_password_long'),
                ],
            ],
            'password_confirm' => [
                'label'  => lang('Account.register.password_confirm'),
                'rules'  => 'required|matches[password]',
                'errors' => ['matches' => lang('Account.register.err_password_match')],
            ],
            'country'          => [
                'label' => lang('Account.register.country'),
                // The framework's own in_list message prints every permitted
                // value, which here is forty codes across the page — and the
                // only way to fail this rule is a forged or stale form, so the
                // message would be read by nobody it could help.
                'rules'  => 'required|in_list[' . implode(',', array_keys(self::COUNTRIES)) . ']',
                'errors' => [
                    'required' => lang('Account.register.err_country'),
                    'in_list'  => lang('Account.register.err_country'),
                ],
            ],
            // An unticked checkbox is not posted at all, so `required` is
            // exactly the right rule and needs no true/false comparison.
            'terms'            => [
                'label'  => lang('Account.register.terms'),
                'rules'  => 'required',
                'errors' => ['required' => lang('Account.register.err_terms')],
            ],
        ];
    }

    /**
     * Write the account and give it the learner role.
     *
     * @return int|null the new id, or null if the row could not be written
     */
    private function createLearner(string $email): ?int
    {
        $users = new UserModel();

        $name  = trim((string) $this->request->getPost('name'));
        $parts = preg_split('/\s+/', $name, 2) ?: [];
        $optIn = $this->request->getPost('marketing_opt_in') !== null;

        // `username` is deliberately absent. It is nullable but carries a
        // unique index, and several engines treat repeated empty strings as a
        // collision while allowing any number of nulls — so writing '' here
        // would let exactly one learner register and refuse the second with a
        // database error.
        $id = $users->insert([
            'email'         => $email,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'first_name'    => mb_substr($parts[0] ?? '', 0, 64),
            'last_name'     => mb_substr($parts[1] ?? '', 0, 64),
            'country'       => strtoupper(mb_substr((string) $this->request->getPost('country'), 0, 2)),
            'locale'        => current_locale(),
            'status'        => 'active',
            'marketing_opt_in' => $optIn ? 1 : 0,
            // The moment consent was given, not merely that it was. Both the
            // PDPA and the GDPR ask you to be able to show when, which a
            // boolean on its own cannot answer.
            'consent_at'    => $optIn ? date('Y-m-d H:i:s') : null,
        ], true);

        if (! $id) {
            log_message('error', 'A learner account could not be created for {email}.', ['email' => $email]);

            return null;
        }

        // The role is what makes "is this person a customer?" a question the
        // database can answer. It is never a staff role: AdminAuthFilter allows
        // only the slugs in RoleSeeder::STAFF, and `learner` is not one.
        $db   = db_connect();
        $role = $db->table('roles')->where('slug', 'learner')->get()->getRowArray();
        if ($role !== null) {
            $db->table('role_user')->insert(['user_id' => (int) $id, 'role_id' => (int) $role['id']]);
        }

        return (int) $id;
    }

    /**
     * The response both registration branches share.
     *
     * One method, called from both, because the property that matters here is
     * that the two are identical — and two `redirect()` lines written out
     * separately are two lines that can drift apart in a later edit without
     * anything failing.
     */
    private function registrationDone(string $back, string $email): RedirectResponse
    {
        $response = redirect()->to($back)->with('registered', $email);

        // Depends on the site, never on the address, so it cannot be used to
        // probe for accounts. Saying "check your inbox" when the site has no
        // mail server is a small lie that costs a booking.
        return Mailer::isConfigured() ? $response : $response->with('mail_off', true);
    }

    /**
     * Where to go after signing in.
     *
     * `learner_intended` is written by LearnerFilter from current_url(), so
     * somebody who followed a link to their certificate lands on their
     * certificate rather than on a dashboard they have to navigate out of. It
     * is still checked against this site's own base URL before it is followed:
     * an unchecked redirect target taken from the session is the shape every
     * credential-phishing link has, and the fact that only our own filter
     * writes the key today is not a reason to trust whatever is in it tomorrow.
     */
    private function intended(string $fallback = 'account'): string
    {
        $intended = (string) (session()->get('learner_intended') ?? '');
        session()->remove('learner_intended');

        if ($intended !== '' && str_starts_with($intended, rtrim(base_url(), '/') . '/')) {
            return $intended;
        }

        return locale_url($fallback);
    }

    /**
     * Hand a guest basket to the learner who has just signed in.
     *
     * CartModel::attachTo() decides what happens if they already had one — the
     * guest cart wins and the older is dropped — and it does the whole thing in
     * a transaction. Nothing here touches `carts` directly.
     */
    private function attachCart(int $userId): void
    {
        $cart = CartContext::existing();
        if ($cart === null || (int) ($cart['user_id'] ?? 0) === $userId) {
            return;
        }

        (new CartModel())->attachTo((int) $cart['id'], $userId);

        // The context caches the row it read; this request may go on to render
        // a page that asks for it again.
        CartContext::forget();
    }

    /** Per-address and per-machine limits on the two forms that send email. */
    private function withinRate(string $bucket, string $email): bool
    {
        $throttle = service('throttler');

        return $throttle->check(md5($bucket . '-ip-' . $this->request->getIPAddress()), self::PER_IP, HOUR)
            && $throttle->check(md5($bucket . '-' . $email), self::PER_EMAIL, HOUR);
    }

    /** The country to pre-select, from the same signals the price came from. */
    private function defaultCountry(): string
    {
        $country = (new PricingService())->resolveCountry($this->request);

        return isset(self::COUNTRIES[(string) $country]) ? (string) $country : 'LK';
    }

    // ── The letters ─────────────────────────────────────────────────────────

    private function mailVerification(array $user): void
    {
        $token = LearnerAuth::issueToken((int) $user['id'], 'verify');
        $link  = locale_url('account/verify/' . $token);
        $school = (string) setting('site_name', '');

        $this->letter((string) $user['email'], lang('Account.mail.verify_subject', [$school]), implode('', [
            $this->paragraph(lang('Account.mail.greeting', [LearnerAuth::displayName($user)])),
            $this->paragraph(lang('Account.mail.verify_body', [$school])),
            $this->button($link, lang('Account.mail.verify_cta')),
            $this->note(lang('Account.mail.verify_expiry')),
            $this->note(lang('Account.mail.verify_ignore')),
            $this->fallback($link),
        ]));
    }

    /**
     * The letter that goes out when somebody registers with an address that is
     * already in use.
     *
     * It is addressed to the account holder, not to whoever filled the form in,
     * because they are frequently not the same person: this is what a mistyped
     * address, and an attempt to find out whether somebody has an account here,
     * both look like from the server. It offers a way in rather than only an
     * explanation — an invited account has never had a password, and telling
     * its owner they "already have an account" without saying how to open it
     * would be true and useless.
     */
    private function mailExistingAccount(array $user): void
    {
        $invited = ($user['status'] ?? '') === 'invited';
        $school  = (string) setting('site_name', '');
        $forgot  = locale_url('account/forgot');

        // No token is minted here, and that is deliberate. This letter is sent
        // because a *stranger* typed this address into a form; putting a live
        // password-reset link in it would mean anybody who knows an address can
        // post a working reset into that person's inbox at will, and would also
        // silently kill a reset the owner had asked for a minute earlier — the
        // token columns hold one value each. The letter points at the forms
        // instead, which the owner reaches on their own terms.
        $body = [
            $this->paragraph(lang('Account.mail.greeting', [LearnerAuth::displayName($user)])),
            $this->paragraph($invited
                ? lang('Account.mail.exists_invited', [$school])
                : lang('Account.mail.exists_body', [$school])),
        ];

        if ($invited) {
            // An invited account has a password nobody can type, so "sign in"
            // is not an offer it can make. The reset form is the only door.
            $body[] = $this->button($forgot, lang('Account.mail.exists_invited_cta'));
            $body[] = $this->fallback($forgot);
        } else {
            $body[] = $this->button(locale_url('account/login'), lang('Account.mail.exists_cta'));
            $body[] = $this->note(lang('Account.mail.exists_reset'));
            $body[] = $this->button($forgot, lang('Account.mail.exists_reset_cta'));
            $body[] = $this->note(lang('Account.mail.exists_ignore'));
            $body[] = $this->fallback($forgot);
        }

        $this->letter((string) $user['email'], lang('Account.mail.exists_subject', [$school]), implode('', $body));
    }

    private function mailReset(array $user): void
    {
        $token  = LearnerAuth::issueToken((int) $user['id'], 'reset', self::RESET_HOURS);
        $link   = locale_url('account/reset/' . $token);
        $school = (string) setting('site_name', '');

        $this->letter((string) $user['email'], lang('Account.mail.reset_subject', [$school]), implode('', [
            $this->paragraph(lang('Account.mail.greeting', [LearnerAuth::displayName($user)])),
            $this->paragraph(lang('Account.mail.reset_body')),
            $this->button($link, lang('Account.mail.reset_cta')),
            $this->note(lang('Account.mail.reset_expiry')),
            $this->note(lang('Account.mail.reset_ignore')),
            $this->fallback($link),
        ]));
    }

    /**
     * Send one of the three, through the shell every transactional email on
     * this site already uses.
     *
     * A failure is logged and swallowed. The account has been written, or the
     * token issued, before this is ever called; turning a mail-server timeout
     * into an error page would tell the visitor their registration failed when
     * it did not, and the second attempt would then meet "that address is
     * already registered" — which, by design, looks exactly like success.
     */
    private function letter(string $to, string $subject, string $body): void
    {
        $result = Mailer::send($to, $subject, view('Modules\Learning\Views\emails\_layout', [
            'title'  => $subject,
            'body'   => $body,
            'school' => (string) setting('site_name', ''),
        ], ['saveData' => false]));

        if (! ($result['sent'] ?? false)) {
            // The address is not logged: a log line naming who registered when
            // is the same customer list this controller spends its whole length
            // refusing to disclose.
            log_message('error', 'An account email could not be sent: {reason}', ['reason' => $result['error'] ?? '']);
        }
    }

    // Inline styles and tables, because an email client is not a browser. These
    // four match the shell in Modules\Learning\Views\emails\_layout.
    private function paragraph(string $text): string
    {
        return '<p style="margin:0 0 16px;">' . esc($text) . '</p>';
    }

    private function note(string $text): string
    {
        return '<p style="margin:0 0 14px;font-size:14px;color:#55607a;">' . esc($text) . '</p>';
    }

    private function button(string $url, string $label): string
    {
        return '<p style="margin:0 0 18px;"><a href="' . esc($url, 'attr') . '"'
            . ' style="display:inline-block;background:#3f35c7;color:#ffffff;text-decoration:none;'
            . 'padding:11px 22px;border-radius:999px;font-weight:bold;font-size:14px;">'
            . esc($label) . '</a></p>';
    }

    /**
     * The address in plain text as well as behind the button.
     *
     * Some clients strip the anchor, some open links in a browser that is not
     * signed in, and some people forward the message to a colleague. A link
     * that can only be clicked is a link that fails silently for all three.
     */
    private function fallback(string $url): string
    {
        return '<p style="margin:0;font-size:12px;color:#55607a;word-break:break-all;">'
            . esc(lang('Account.mail.link_fallback')) . '<br>' . esc($url) . '</p>';
    }
}
