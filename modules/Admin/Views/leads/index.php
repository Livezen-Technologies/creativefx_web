<?php helper(['url', 'norlanka', 'commerce']); $this->extend('Modules\Admin\Views\layout');

/**
 * The pipeline, as a queue rather than as a report.
 *
 * Two rows of chips, because a lead has two axes and only one of them is the
 * pipeline. Type says which form this arrived through and therefore who ought
 * to pick it up; stage says how far the conversation has got. Collapsing them
 * into one row of eleven chips would hide the only question the screen exists
 * to answer, which is "what is new and who is it for".
 *
 * Corporate rows carry the company, the team size and the budget on the row
 * itself. Those three together are what decides whether an enquiry is a private
 * cohort worth a trainer's diary or two people who should be sent to a public
 * date, and making somebody open twelve records to find that out is how the
 * good one gets answered on Thursday.
 */

$base = 'admin/training-leads';

// One place that rebuilds the query string with a value changed, so a stage
// survives choosing a type, and neither survives paging into nonsense.
$link = static function (array $over = []) use ($filters): string {
    $query = array_filter(
        array_merge($filters, $over),
        static fn ($v): bool => $v !== '' && $v !== null
    );

    return site_url('admin/training-leads') . ($query === [] ? '' : '?' . http_build_query($query));
};

$typeLabels = [
    'corporate'    => 'Corporate',
    'contact'      => 'Contact',
    'newsletter'   => 'Newsletter',
    'resource'     => 'Download',
    'waitlist'     => 'Waitlist',
    'date_request' => 'Date request',
];

// Coloured by what the enquiry is worth rather than by its name. A request for
// a quote is a sale in progress; a waitlist or a date request is demand for a
// class that is not on the calendar, which is the second most useful thing on
// this screen; a download or a sign-up is a name, and nothing more yet.
$typeTones = [
    'corporate'    => 'bg-emerald-500/15 text-emerald-300',
    'contact'      => 'bg-sky-500/15 text-sky-300',
    'newsletter'   => 'bg-white/10 text-white/55',
    'resource'     => 'bg-white/10 text-white/55',
    'waitlist'     => 'bg-amber-500/15 text-amber-300',
    'date_request' => 'bg-amber-500/15 text-amber-300',
];

// Warm while the ball is in the school's court, cool once it is with the
// customer, green when it closed. `quoted` takes its colour from the text and
// keeps the neutral background every other screen already uses: a tinted violet
// background would be the only one of its kind on the site, and a Tailwind
// utility written exactly once produces no CSS until the next build — which is
// the silent failure check-tailwind-classes.mjs exists to catch.
$statusTones = [
    'new'       => 'bg-amber-500/15 text-amber-300',
    'contacted' => 'bg-sky-500/15 text-sky-300',
    'quoted'    => 'bg-white/10 text-violet-300',
    'won'       => 'bg-emerald-500/15 text-emerald-300',
    'lost'      => 'bg-white/10 text-white/40',
];

$teamLabels = [
    '1-4'      => '1–4 people',
    '5-9'      => '5–9 people',
    '10-19'    => '10–19 people',
    '20-49'    => '20–49 people',
    '50+'      => '50 or more',
    'not_sure' => 'Not decided',
];

/**
 * The budget the enquirer was actually shown.
 *
 * Read from the payload's bounds, which are integer minor units frozen at the
 * moment they answered, and not from `budget_band` — that column holds a code
 * like `LKR:band_2`, which means nothing on its own and, worse, would be read
 * against whatever the band table says next year. money() is the only division
 * by 100 anywhere near this.
 */
$budget = static function (array $row): string {
    $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
    $bounds  = is_array($payload) ? ($payload['budget'] ?? null) : null;

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

    $stored = (string) ($row['budget_band'] ?? '');

    return $stored === '' || $stored === 'not_sure' ? 'Not decided' : $stored;
};

/** The one line under the type chip that says what this particular form asked for. */
$detail = static function (array $row): string {
    $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
    $payload = is_array($payload) ? $payload : [];

    if (isset($payload['resource']) && is_array($payload['resource'])) {
        return (string) ($payload['resource']['title'] ?? $payload['resource']['slug'] ?? '');
    }

    return trim((string) ($row['preferred_dates'] ?? ''));
};
?>
<?= $this->section('content') ?>

<!-- Which form it arrived through -->
<div class="mb-3 flex flex-wrap items-center gap-2">
    <a href="<?= esc($link(['type' => '', 'page' => '']), 'attr') ?>"
       class="rounded-full border px-4 py-1.5 text-sm font-medium transition <?= $filters['type'] === '' ? 'border-brand-red bg-brand-red/15 text-white' : 'border-white/10 text-white/60 hover:text-white' ?>">
        Everything
        <span class="ml-1.5 text-xs text-white/40"><?= esc((string) $allCount) ?></span>
    </a>
    <?php foreach ($types as $type): ?>
        <a href="<?= esc($link(['type' => $type, 'page' => '']), 'attr') ?>"
           class="rounded-full border px-4 py-1.5 text-sm font-medium transition <?= $filters['type'] === $type ? 'border-brand-red bg-brand-red/15 text-white' : 'border-white/10 text-white/60 hover:text-white' ?>">
            <?= esc($typeLabels[$type] ?? $type) ?>
            <span class="ml-1.5 text-xs <?= ($typeCounts[$type] ?? 0) > 0 ? 'text-brand-red' : 'text-white/40' ?>"><?= esc((string) ($typeCounts[$type] ?? 0)) ?></span>
        </a>
    <?php endforeach; ?>

    <?php // The export takes the filter above and beside it, which is how the
          // newsletter list is got out: choose Newsletter, then this. ?>
    <a href="<?= esc($link(['export' => 'csv', 'page' => '']), 'attr') ?>"
       class="ml-auto rounded-lg border border-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest hover:border-white">Export CSV</a>
</div>

<?php // The stage tabs are the shared queue chips, so this screen counts and
      // colours a pipeline the same way the moderation queue does. The chosen
      // type rides along in `extra`, or clicking a stage would silently throw
      // it away. ?>
<?= view('Modules\Admin\Views\partials\queue_filter', [
    'base'     => $base,
    'current'  => $filters['status'],
    'statuses' => $statuses,
    'counts'   => $statusCounts,
    'extra'    => ['type' => $filters['type']],
], ['saveData' => false]) ?>

<div class="relative overflow-x-auto rounded-xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <th class="px-4 py-3">Enquiry</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Team &amp; budget</th>
                <th class="hidden px-4 py-3 lg:table-cell">Received</th>
                <th class="hidden px-4 py-3 md:table-cell">Owner</th>
                <th class="px-4 py-3">Stage</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($rows as $r):
                $id    = (int) $r['id'];
                $type  = (string) $r['type'];
                // A newsletter sign-up carries an address and nothing else, so
                // the address is the name rather than a blank where a person
                // should be.
                $name  = trim((string) ($r['name'] ?? ''));
                $owner = trim(((string) ($r['owner_first'] ?? '')) . ' ' . ((string) ($r['owner_last'] ?? '')));
                $note  = $detail($r); ?>
                <tr class="align-top hover:bg-white/[0.02]">
                    <td class="px-4 py-3">
                        <a href="<?= site_url($base . '/' . $id) ?>" class="font-medium hover:text-brand-red">
                            <?= esc($name !== '' ? $name : (string) $r['email']) ?>
                        </a>
                        <?php if ($name !== ''): ?>
                            <div class="text-xs text-white/45"><?= esc((string) $r['email']) ?></div>
                        <?php endif; ?>
                        <?php if (! empty($r['company'])): ?>
                            <div class="text-xs font-medium text-white/70">
                                <?= esc((string) $r['company']) ?><?= ! empty($r['country']) ? ' · ' . esc(strtoupper((string) $r['country'])) : '' ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-3">
                        <span class="whitespace-nowrap rounded-full <?= esc($typeTones[$type] ?? 'bg-white/10 text-white/60') ?> px-2.5 py-0.5 text-[11px] font-semibold">
                            <?= esc($typeLabels[$type] ?? $type) ?>
                        </span>
                        <?php if ($note !== ''): ?>
                            <div class="mt-1 max-w-xs truncate text-xs text-white/45"><?= esc($note) ?></div>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-3">
                        <?php if (! empty($r['team_size'])): ?>
                            <div class="font-medium"><?= esc($teamLabels[(string) $r['team_size']] ?? (string) $r['team_size']) ?></div>
                            <div class="text-xs text-white/50"><?= esc($budget($r)) ?></div>
                        <?php else: ?>
                            <span class="text-white/25">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="hidden whitespace-nowrap px-4 py-3 text-white/50 lg:table-cell">
                        <?= esc($r['created_at'] ? date('j M Y', strtotime((string) $r['created_at'])) : '—') ?>
                    </td>

                    <td class="hidden px-4 py-3 md:table-cell">
                        <?php if ($owner !== '' || ! empty($r['owner_email'])): ?>
                            <span class="text-white/70"><?= esc($owner !== '' ? $owner : (string) $r['owner_email']) ?></span>
                        <?php else: ?>
                            <span class="text-white/25">Nobody</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-3">
                        <span class="rounded-full <?= esc($statusTones[(string) $r['status']] ?? 'bg-white/10 text-white/60') ?> px-2.5 py-0.5 text-[11px] font-semibold capitalize">
                            <?= esc((string) $r['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-white/40">
                        <?php if ($filters['type'] !== '' || $filters['status'] !== ''): ?>
                            Nothing matches that filter.
                        <?php else: ?>
                            No enquiries yet. The first request for a quote, newsletter sign-up or resource
                            download will appear here the moment somebody sends one.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <div class="mt-4 flex items-center justify-between text-xs text-white/40">
        <p><?= number_format($total) ?> enquir<?= $total === 1 ? 'y' : 'ies' ?> · page <?= esc((string) $page) ?> of <?= esc((string) $pages) ?></p>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
                <a href="<?= esc($link(['page' => $page - 1]), 'attr') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Previous</a>
            <?php endif; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= esc($link(['page' => $page + 1]), 'attr') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Next</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
