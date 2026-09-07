<?php
helper(['norlanka', 'catalog', 'commerce', 'url']);

/**
 * Every published date for a course, in one table.
 *
 * Not a filtered view of the booking panel: this is the full list, all modes
 * together, because somebody scrolling this far is comparing dates rather than
 * choosing a mode. Each row books directly — a date that takes three clicks to
 * buy is a date that does not get bought.
 *
 * A course with no dates at all still renders something. "No dates available"
 * is the most reliable way to lose a visitor on a training site, so the empty
 * state is a request-a-date form rather than a dead end.
 *
 * @var array  $byMode
 * @var list<string> $modes
 * @var string $currency
 * @var array  $course
 */
$rows = [];
foreach ($modes as $m) {
    foreach ($byMode[$m] ?? [] as $session) {
        $rows[] = $session;
    }
}
usort($rows, static function (array $a, array $b): int {
    // Dated classes first, in date order; self-paced last, since "any time" has
    // no place in a chronological list but should still be buyable here.
    if (empty($a['start_date']) !== empty($b['start_date'])) {
        return empty($a['start_date']) ? 1 : -1;
    }

    return strcmp((string) $a['start_date'], (string) $b['start_date']);
});
?>

<?php if ($rows === []): ?>
    <div class="mt-5 rounded-2xl border border-line bg-surface p-6">
        <p class="text-white/70"><?= esc(lang('Catalog.dates.none')) ?></p>
        <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="mt-4 flex flex-wrap gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
            <label class="sr-only" for="dates-email"><?= esc(lang('Catalog.panel.email')) ?></label>
            <input id="dates-email" name="email" type="email" required autocomplete="email"
                   class="field max-w-xs flex-1" placeholder="<?= esc(lang('Catalog.panel.email'), 'attr') ?>">
            <button type="submit" class="btn-brand"><?= esc(lang('Catalog.panel.tell_me')) ?></button>
        </form>
    </div>
<?php else: ?>
    <div class="mt-5 overflow-x-auto rounded-2xl border border-line">
        <table class="w-full min-w-[42rem] border-collapse text-sm">
            <caption class="sr-only"><?= esc(lang('Catalog.dates.caption')) ?></caption>
            <thead>
                <tr class="bg-surface text-left text-xs uppercase tracking-wider text-white/50">
                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.when')) ?></th>
                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.mode')) ?></th>
                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.where')) ?></th>
                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.seats')) ?></th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold"><?= esc(lang('Catalog.dates.price')) ?></th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only"><?= esc(lang('Catalog.dates.book')) ?></span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($rows as $session):
                    $seats = seats_note($session);
                    $full  = ($session['seats_left'] ?? null) === 0; ?>
                    <tr class="align-middle">
                        <td class="px-4 py-3">
                            <a href="<?= esc(session_url($session)) ?>" class="font-medium underline decoration-line underline-offset-4 hover:text-brand-red">
                                <?= esc(session_dates($session)) ?>
                            </a>
                            <?php if ($times = session_times($session)): ?>
                                <span class="block text-xs text-white/45"><?= esc($times) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-white/70"><?= esc(mode_label($session['mode'])) ?></td>
                        <td class="px-4 py-3 text-white/70">
                            <?= esc($session['venue_city'] ?? ($session['mode'] === 'CLASSROOM' ? '' : lang('Catalog.dates.online'))) ?>
                        </td>
                        <td class="px-4 py-3 <?= $seats['urgent'] ? 'font-semibold text-gold' : 'text-white/60' ?>">
                            <?= esc($seats['text']) ?>
                            <?php if (\Modules\Catalog\Models\CourseSessionModel::isConfirmed($session)): ?>
                                <?php // Worth saying loudly. "Confirmed to run"
                                      // removes the biggest hesitation there is
                                      // in booking a dated course. ?>
                                <span class="mt-0.5 block text-xs font-medium text-brand-red"><?= esc(lang('Catalog.dates.confirmed')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= esc(money((int) $session['price_cents'], $currency)) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($full): ?>
                                <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                    <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                                    <button type="submit" class="btn-ghost text-xs"><?= esc(lang('Catalog.dates.waitlist')) ?></button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= esc(locale_url('cart/add')) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_type" value="session">
                                    <input type="hidden" name="item_id" value="<?= (int) $session['id'] ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit" class="btn-brand px-5 py-2 text-xs"><?= esc(lang('Catalog.dates.book')) ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
