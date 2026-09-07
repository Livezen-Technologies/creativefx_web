<?php helper(['url', 'norlanka', 'commerce', 'catalog']); $this->extend('Modules\Admin\Views\layout');

/**
 * One enquiry, whole.
 *
 * Everything the visitor sent is on this page and none of it is editable. What
 * they wrote is evidence — of what they asked for, when, and in which currency
 * they were shown a price — and a console that lets somebody tidy up a
 * customer's words has lost the only record of the conversation's beginning.
 * The school's own two fields, the stage and the owner, are in a form of their
 * own on the right, next to a note box that adds to the history rather than
 * replacing it.
 *
 * The reply-to address is a mailto link in three places, because answering is
 * the point of the screen. Every other action here — quoting, scheduling,
 * invoicing — happens elsewhere and after a person has replied.
 *
 * Nothing on this page is fetched live from the catalogue. The courses of
 * interest are printed from the snapshot taken at submission, so a course
 * renamed or withdrawn next year does not rewrite what this company asked for;
 * the link beside each one is a convenience, and a dead link there is the
 * honest signal that the course is gone.
 */

$id       = (int) $row['id'];
$type     = (string) $row['type'];
$email    = (string) $row['email'];
$name     = trim((string) ($row['name'] ?? ''));
$company  = trim((string) ($row['company'] ?? ''));
$siteName = (string) setting('site_name', '');

$payload = json_decode((string) ($row['payload_json'] ?? ''), true);
$payload = is_array($payload) ? $payload : [];
$courses = json_decode((string) ($row['courses_json'] ?? ''), true);
$courses = is_array($courses) ? $courses : [];
$utm     = json_decode((string) ($row['utm_json'] ?? ''), true);
$utm     = is_array($utm) ? $utm : [];

// Newest first. A note thread on a lead is read to find out where the
// conversation got to, not to relive it from the beginning.
$notes = isset($payload['notes']) && is_array($payload['notes']) ? array_reverse(array_values($payload['notes'])) : [];

$typeLabels = [
    'corporate'    => 'Corporate quote request',
    'contact'      => 'Contact message',
    'newsletter'   => 'Newsletter sign-up',
    'resource'     => 'Resource download',
    'waitlist'     => 'Waitlist request',
    'date_request' => 'Request for a date',
];

$teamLabels = [
    '1-4'      => '1–4 people',
    '5-9'      => '5–9 people',
    '10-19'    => '10–19 people',
    '20-49'    => '20–49 people',
    '50+'      => '50 or more',
    'not_sure' => 'Not decided yet',
];

/**
 * How a private cohort would be delivered.
 *
 * Deliberately not `mode_label()`. That helper speaks the scheduling vocabulary
 * — LIVE_ONLINE, CLASSROOM, SELF_PACED — and a corporate enquiry answers a
 * different question in different words. Passing one to the other prints the
 * helper's fallback on every corporate lead, which reads as data rather than as
 * the mistake it is.
 */
$modeLabels = [
    'ONSITE'  => 'At the client’s premises',
    'VIRTUAL' => 'Live online',
    'HYBRID'  => 'A mixture',
    'UNSURE'  => 'Not decided yet',
];

// The budget as the enquirer was actually shown it: bounds in integer minor
// units, frozen in the payload at submission. `budget_band` holds a code such
// as `LKR:band_2`, which means nothing on its own and would be re-read against
// a band table that has since been retuned.
$budgetLabel = static function (array $payload, ?string $stored): string {
    $bounds = $payload['budget'] ?? null;

    if (is_array($bounds)) {
        $currency = strtoupper((string) ($bounds['currency'] ?? 'USD'));
        $min      = isset($bounds['min_cents']) ? (int) $bounds['min_cents'] : null;
        $max      = isset($bounds['max_cents']) ? (int) $bounds['max_cents'] : null;

        if ($min !== null && $max !== null) {
            return money($min, $currency) . ' – ' . money($max, $currency);
        }
        if ($max !== null) {
            return 'Up to ' . money($max, $currency);
        }
        if ($min !== null) {
            return 'Over ' . money($min, $currency);
        }
    }

    $stored = (string) $stored;

    return $stored === '' || $stored === 'not_sure' ? 'Not decided yet' : $stored;
};

/**
 * The reference the visitor was given when they sent the form.
 *
 * Mirrors `Modules\Commerce\Controllers\Corporate::reference()`, which is
 * private to that controller. Repeated here rather than reached for, because an
 * enquirer replying "about RFQ-00042" has to be findable, and the alternative
 * is asking them for a database id they have never seen.
 */
$reference = 'RFQ-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

$subject = $type === 'corporate'
    ? 'Your training enquiry — ' . $reference . ($siteName !== '' ? ' · ' . $siteName : '')
    : 'Your enquiry' . ($siteName !== '' ? ' — ' . $siteName : '');
$mailto = 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject);

$when = static fn (?string $ts, string $format = 'j M Y, H:i'): string => $ts ? date($format, strtotime($ts)) : '—';

$card  = 'rounded-2xl border border-white/10 bg-white/[0.02] p-7';
$field = 'mt-2 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
$dt    = 'text-xs uppercase tracking-widest text-white/40';

// The plain facts, in the order somebody reads them when deciding whether to
// pick this up. Blank entries are dropped rather than printed as dashes: a
// newsletter sign-up has none of them and would otherwise be a wall of nothing.
$facts = array_filter([
    'Phone'           => (string) ($row['phone'] ?? ''),
    'Organisation'    => $company,
    'Country'         => $row['country'] ? strtoupper((string) $row['country']) : '',
    'Team size'       => $row['team_size'] ? ($teamLabels[(string) $row['team_size']] ?? (string) $row['team_size']) : '',
    'Budget'          => $row['budget_band'] || isset($payload['budget']) ? $budgetLabel($payload, $row['budget_band']) : '',
    'Delivery'        => $row['mode'] ? ($modeLabels[(string) $row['mode']] ?? (string) $row['mode']) : '',
    'Where'           => (string) ($row['location'] ?? ''),
    'Preferred dates' => (string) ($row['preferred_dates'] ?? ''),
    'Language'        => (string) ($payload['locale'] ?? ''),
    'Priced in'       => (string) ($payload['currency'] ?? ''),
], static fn (string $v): bool => trim($v) !== '');

// Anything a form put in the payload that this page has no block for. Printed
// rather than dropped: six forms write to this table today and the seventh will
// carry a field nobody has thought about here yet, and a lead that silently
// loses half of what it arrived with is worse than an ugly row.
$handled = ['notes', 'budget', 'resource', 'marketing_opt_in', 'courses_other', 'locale', 'currency'];
$extra   = array_diff_key($payload, array_flip($handled));

$linkable = static fn (string $url): bool => str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
?>
<?= $this->section('content') ?>

<a href="<?= site_url('admin/training-leads') ?>" class="text-xs uppercase tracking-widest text-white/50 hover:text-white">← All enquiries</a>

<div class="mt-4 grid gap-6 lg:grid-cols-3">
    <!-- What they sent -->
    <div class="space-y-6 lg:col-span-2">
        <div class="<?= $card ?>">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold"><?= esc($name !== '' ? $name : $email) ?></h2>
                    <p class="mt-1 text-sm text-white/60">
                        <?= esc($typeLabels[$type] ?? $type) ?>
                        <?php if ($company !== ''): ?> · <span class="text-white/80"><?= esc($company) ?></span><?php endif; ?>
                        · <?= esc($when($row['created_at'])) ?>
                    </p>
                    <p class="mt-2">
                        <?php // The fastest useful action on this screen. ?>
                        <a href="<?= esc($mailto, 'attr') ?>" class="text-sm text-brand-red hover:underline"><?= esc($email) ?></a>
                    </p>
                    <?php if ($type === 'corporate'): ?>
                        <p class="mt-1 font-mono text-xs text-white/40"><?= esc($reference) ?> — the reference they were given</p>
                    <?php endif; ?>
                </div>
                <a href="<?= esc($mailto, 'attr') ?>"
                   class="rounded-lg bg-brand-red px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-brand-red-dark">Reply by email</a>
            </div>

            <?php if ($facts !== []): ?>
                <dl class="mt-6 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                    <?php foreach ($facts as $label => $value): ?>
                        <div>
                            <dt class="<?= $dt ?>"><?= esc($label) ?></dt>
                            <dd class="mt-1 text-white/85">
                                <?php if ($label === 'Phone'): ?>
                                    <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $value), 'attr') ?>" class="hover:text-brand-red"><?= esc($value) ?></a>
                                <?php else: ?>
                                    <?= esc($value) ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>

            <?php if ($courses !== []): ?>
                <h3 class="mt-8 <?= $dt ?>">Courses of interest</h3>
                <ul class="mt-2 space-y-1 text-sm">
                    <?php foreach ($courses as $course):
                        $slug  = is_array($course) ? (string) ($course['slug'] ?? '') : '';
                        $label = is_array($course) ? (string) ($course['title'] ?? $slug) : (string) $course; ?>
                        <li>
                            <?php if ($slug !== ''): ?>
                                <a href="<?= esc(course_url($slug), 'attr') ?>" target="_blank" rel="noopener" class="text-white/85 hover:text-brand-red"><?= esc($label) ?></a>
                            <?php else: ?>
                                <span class="text-white/85"><?= esc($label) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (! empty($payload['courses_other'])): ?>
                <h3 class="mt-8 <?= $dt ?>">Something else they asked for</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-white/75"><?= esc((string) $payload['courses_other']) ?></p>
            <?php endif; ?>

            <?php if (isset($payload['resource']) && is_array($payload['resource'])): ?>
                <h3 class="mt-8 <?= $dt ?>">What they downloaded</h3>
                <p class="mt-2 text-sm text-white/85"><?= esc((string) ($payload['resource']['title'] ?? $payload['resource']['slug'] ?? '')) ?></p>
                <?php // `delivered` false means the file was missing when they asked, so this
                      // is somebody still owed an email. It cannot be reconstructed later:
                      // once the file exists, every row looks identical. ?>
                <?php if (array_key_exists('delivered', $payload) && ! $payload['delivered']): ?>
                    <p class="mt-1 text-sm text-amber-300">The file was not available when they asked — they are still owed it.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (! empty($row['message'])): ?>
                <h3 class="mt-8 <?= $dt ?>">What they wrote</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-white/75"><?= esc((string) $row['message']) ?></p>
            <?php endif; ?>

            <?php if ($extra !== []): ?>
                <h3 class="mt-8 <?= $dt ?>">Also carried</h3>
                <dl class="mt-2 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                    <?php foreach ($extra as $key => $value): ?>
                        <div>
                            <dt class="text-xs text-white/40"><?= esc((string) $key) ?></dt>
                            <dd class="mt-0.5 break-words text-white/75">
                                <?= esc(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>

        <!-- Where it came from -->
        <div class="<?= $card ?>">
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Attribution</h3>
            <p class="mt-2 text-xs leading-relaxed text-white/45">
                Captured at the moment the form was sent. Cost per enrolment by channel is a launch metric,
                and attribution that was not recorded then cannot be worked out afterwards — so a lead with
                nothing here is a lead that genuinely arrived with no campaign on it, not one that lost it.
            </p>

            <dl class="mt-5 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                <?php if (! empty($row['source'])): ?>
                    <div>
                        <dt class="<?= $dt ?>">Page they were on</dt>
                        <dd class="mt-1 break-all">
                            <?php $source = (string) $row['source']; ?>
                            <?php if (! str_contains($source, '://') && ! str_starts_with($source, '//')): ?>
                                <a href="<?= esc(site_url($source), 'attr') ?>" target="_blank" rel="noopener" class="text-white/85 hover:text-brand-red"><?= esc($source) ?></a>
                            <?php else: ?>
                                <span class="text-white/85"><?= esc($source) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php foreach ($utm as $key => $value):
                    $value = is_scalar($value) ? (string) $value : json_encode($value); ?>
                    <div>
                        <dt class="<?= $dt ?>"><?= esc(str_replace('_', ' ', (string) $key)) ?></dt>
                        <dd class="mt-1 break-all">
                            <?php // A referrer is a URL somebody else's site handed us. Only http
                                  // and https are turned into a link: anything else in an href is
                                  // a scheme the browser will happily run. ?>
                            <?php if ($linkable($value)): ?>
                                <a href="<?= esc($value, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="text-white/85 hover:text-brand-red"><?= esc($value) ?></a>
                            <?php else: ?>
                                <span class="text-white/85"><?= esc($value) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>

            <?php if ($utm === [] && empty($row['source'])): ?>
                <p class="mt-5 text-sm text-white/40">Nothing was recorded against this enquiry.</p>
            <?php endif; ?>

            <?php // Consent to be marketed to is a separate permission from making an
                  // enquiry, and is shown as what it is rather than assumed. "Not asked"
                  // is not "no": some forms never put the question. ?>
            <div class="mt-6 border-t border-white/10 pt-5 text-sm">
                <span class="<?= $dt ?>">Marketing consent</span>
                <?php if (! array_key_exists('marketing_opt_in', $payload)): ?>
                    <p class="mt-1 text-white/50">Never asked on this form. Do not add this address to a mailing list on the strength of the enquiry.</p>
                <?php elseif (! empty($payload['marketing_opt_in'])): ?>
                    <p class="mt-1 text-emerald-300">Given. They may be emailed about courses and dates.</p>
                <?php else: ?>
                    <p class="mt-1 text-white/70">Declined. Answer the enquiry, and nothing else.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- What the school does about it -->
    <div class="space-y-6">
        <form method="post" action="<?= site_url('admin/training-leads/' . $id) ?>" class="<?= $card ?>">
            <?= csrf_field() ?>
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Pipeline</h3>

            <label class="mt-4 block">
                <span class="<?= $dt ?>">Stage</span>
                <select name="status" class="<?= $field ?> capitalize">
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= esc($status, 'attr') ?>" <?= (string) $row['status'] === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="mt-5 block">
                <span class="<?= $dt ?>">Owner</span>
                <select name="assigned_to" class="<?= $field ?>">
                    <option value="0">— Nobody yet —</option>
                    <?php
                    // The owner the lead already has, when they no longer hold a
                    // console role — somebody who has left. Listed anyway, and
                    // accepted by the controller, so that saving anything else on
                    // this form does not quietly erase who was handling it.
                    $assigned = (int) ($row['assigned_to'] ?? 0);
                    if ($assigned > 0 && ! in_array($assigned, array_column($owners, 'id'), true)):
                        $gone = trim(((string) ($row['owner_first'] ?? '')) . ' ' . ((string) ($row['owner_last'] ?? '')));
                        $gone = $gone !== '' ? $gone : (string) ($row['owner_email'] ?? 'Unknown'); ?>
                        <option value="<?= $assigned ?>" selected><?= esc($gone) ?> (no longer staff)</option>
                    <?php endif; ?>
                    <?php foreach ($owners as $owner):
                        $label = trim(((string) $owner['first_name']) . ' ' . ((string) $owner['last_name'])); ?>
                        <option value="<?= (int) $owner['id'] ?>" <?= $assigned === $owner['id'] ? 'selected' : '' ?>>
                            <?= esc($label !== '' ? $label : $owner['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="mt-5 block">
                <span class="<?= $dt ?>">Add a note</span>
                <?php // Not a running edit of one field: each note is stored with the time
                      // and the person who wrote it, and nothing already written can be
                      // altered from here. Left empty, the stage and the owner still save. ?>
                <textarea name="note" rows="5" maxlength="2000"
                          class="<?= $field ?>"
                          placeholder="What was said, what was promised, what happens next…"><?= esc(old('note', '')) ?></textarea>
            </label>
            <p class="mt-2 text-xs text-white/40">Added to the history below with the time and your name. Nothing already written is replaced.</p>

            <button type="submit" class="mt-5 w-full rounded-lg bg-brand-red px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-brand-red-dark">Save</button>
            <a href="<?= esc($mailto, 'attr') ?>"
               class="mt-3 block rounded-lg border border-white/15 px-5 py-2.5 text-center text-xs font-semibold uppercase tracking-widest hover:border-white">Reply by email</a>

            <p class="mt-5 border-t border-white/10 pt-4 text-xs leading-relaxed text-white/40">
                Marking this <span class="text-white/60">won</span> records a conversation and nothing more.
                It creates no quote, no order, no seat and no enrolment — a private cohort becomes a date on
                the calendar and an invoice through Orders, once a price has been agreed.
            </p>
        </form>

        <div class="<?= $card ?>">
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">History</h3>
            <?php if ($notes === []): ?>
                <p class="mt-3 text-sm text-white/40">No notes yet.</p>
            <?php else: ?>
                <ol class="mt-4 space-y-4">
                    <?php foreach ($notes as $note): ?>
                        <li class="border-l-2 border-white/10 pl-4">
                            <p class="text-[11px] uppercase tracking-widest text-white/35">
                                <?= esc($when(isset($note['at']) ? (string) $note['at'] : null)) ?>
                                <?php if (! empty($note['by'])): ?> · <?= esc((string) $note['by']) ?><?php endif; ?>
                            </p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-white/80"><?= esc((string) ($note['text'] ?? '')) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
