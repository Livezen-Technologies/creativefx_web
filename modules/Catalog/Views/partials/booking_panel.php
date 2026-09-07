<?php
helper(['norlanka', 'catalog', 'commerce', 'url']);

/**
 * The sticky booking panel — the single highest-leverage element on the site.
 *
 * "How much is it?" has three different answers on this site: live online, in
 * person, and self-paced are three different prices for the same course. A page
 * that shows one of them and makes the reader hunt for theirs loses the booking
 * somewhere in the hunting. So the mode switch, the price and the call to
 * action are one component and move together.
 *
 * The switch is Alpine rather than links, because changing tab must not reload
 * the page and lose the reader's place two thousand pixels down the curriculum.
 * Every tab's markup is rendered server-side and hidden — not fetched — so the
 * price for every mode is in the HTML a search engine reads, and switching is
 * instant on a slow connection.
 *
 * @var array  $course
 * @var array  $byMode   sessions grouped by mode, each with price_cents and seats_left
 * @var array  $prices   cheapest price per mode
 * @var list<string> $modes
 * @var string $defaultMode
 * @var string $currency
 */
$state = [
    'mode'  => $defaultMode,
    'modes' => $modes,
];
?>
<aside class="lg:sticky lg:top-28 lg:self-start" x-data="<?= esc(json_encode($state), 'attr') ?>">
    <div class="overflow-hidden rounded-3xl border border-line bg-surface shadow-lg shadow-brand-black/5">

        <?php if (count($modes) > 1): ?>
            <div class="grid border-b border-line" style="grid-template-columns: repeat(<?= count($modes) ?>, minmax(0, 1fr));"
                 role="tablist" aria-label="<?= esc(lang('Catalog.panel.mode_label'), 'attr') ?>">
                <?php foreach ($modes as $m): ?>
                    <button type="button" role="tab"
                            :aria-selected="mode === '<?= esc($m, 'attr') ?>' ? 'true' : 'false'"
                            :class="mode === '<?= esc($m, 'attr') ?>' ? 'bg-brand-red text-white' : 'text-white/60 hover:text-white'"
                            @click="mode = '<?= esc($m, 'attr') ?>'"
                            class="px-3 py-3 text-xs font-semibold uppercase tracking-wider transition">
                        <?= esc(mode_label($m)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php foreach ($modes as $m):
            $sessions = $byMode[$m] ?? [];
            $price    = $prices[$m] ?? null;
            $next     = $sessions[0] ?? null;
        ?>
            <div x-show="mode === '<?= esc($m, 'attr') ?>'" x-cloak role="tabpanel" class="p-6">

                <?php if ($price !== null): ?>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Catalog.panel.from')) ?></p>
                    <p class="mt-1 flex items-baseline gap-3">
                        <span class="text-3xl font-bold"><?= esc(money($price['price_cents'], $currency)) ?></span>
                        <?php if (! empty($price['compare_at_cents']) && $price['compare_at_cents'] > $price['price_cents']): ?>
                            <span class="text-base text-white/40 line-through"><?= esc(money($price['compare_at_cents'], $currency)) ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="mt-1 text-xs text-white/50"><?= esc(lang('Catalog.panel.per_seat')) ?></p>
                <?php else: ?>
                    <?php // No published price in this currency. Said plainly
                          // rather than converted from the other one — see
                          // PricingService for why nothing here is ever
                          // converted at runtime. ?>
                    <p class="text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                <?php endif; ?>

                <?php if ($m === 'SELF_PACED'): ?>
                    <p class="mt-5 text-sm text-white/70"><?= esc(lang('Catalog.panel.self_paced_note')) ?></p>
                <?php elseif ($sessions !== []): ?>
                    <p class="mt-5 text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Catalog.panel.next_dates')) ?></p>
                    <ul class="mt-2 space-y-1.5">
                        <?php foreach (array_slice($sessions, 0, 3) as $session):
                            $seats = seats_note($session); ?>
                            <li class="flex items-baseline justify-between gap-3 text-sm">
                                <a href="<?= esc(session_url($session)) ?>" class="font-medium underline decoration-line underline-offset-4 hover:text-brand-red">
                                    <?= esc(session_dates($session)) ?>
                                </a>
                                <span class="shrink-0 text-xs <?= $seats['urgent'] ? 'font-semibold text-gold' : 'text-white/45' ?>">
                                    <?= esc($seats['text']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($sessions) > 3): ?>
                        <a href="#dates" class="mt-2 inline-block text-xs text-white/55 underline decoration-line underline-offset-4 hover:text-brand-red">
                            <?= esc(lang('Catalog.panel.all_dates', [count($sessions)])) ?>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mt-5 text-sm text-white/70"><?= esc(lang('Catalog.panel.no_dates')) ?></p>
                <?php endif; ?>

                <?php // ── The call to action ─────────────────────────────── ?>
                <?php if ($m === 'PRIVATE'): ?>
                    <a href="<?= esc(locale_url('corporate/request-quote') . '?course=' . rawurlencode($course['slug'])) ?>" class="btn-brand mt-6 w-full">
                        <?= esc(lang('Catalog.panel.request_quote')) ?>
                    </a>
                <?php elseif ($next !== null): ?>
                    <form method="post" action="<?= esc(locale_url('cart/add')) ?>" class="mt-6">
                        <?= csrf_field() ?>
                        <input type="hidden" name="item_type" value="session">
                        <input type="hidden" name="item_id" value="<?= (int) $next['id'] ?>">
                        <label class="sr-only" for="qty-<?= esc($m, 'attr') ?>"><?= esc(lang('Catalog.panel.seats')) ?></label>
                        <div class="flex gap-2">
                            <input id="qty-<?= esc($m, 'attr') ?>" name="qty" type="number" min="1" max="20" value="1"
                                   class="field w-20 text-center" inputmode="numeric">
                            <button type="submit" class="btn-brand flex-1"><?= esc(lang('Catalog.panel.book')) ?></button>
                        </div>
                        <p class="mt-2 text-center text-xs text-white/50">
                            <?= esc(lang('Catalog.panel.booking_for', [session_dates($next)])) ?>
                        </p>
                    </form>
                <?php else: ?>
                    <?php // No date suits, or none is published. A visitor who
                          // leaves here is gone; a visitor who asks is a lead
                          // and often the reason the date gets scheduled. ?>
                    <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="mt-6 space-y-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                        <input type="hidden" name="mode" value="<?= esc($m, 'attr') ?>">
                        <label class="sr-only" for="wl-<?= esc($m, 'attr') ?>"><?= esc(lang('Catalog.panel.email')) ?></label>
                        <input id="wl-<?= esc($m, 'attr') ?>" name="email" type="email" required autocomplete="email"
                               class="field" placeholder="<?= esc(lang('Catalog.panel.email'), 'attr') ?>">
                        <button type="submit" class="btn-brand w-full"><?= esc(lang('Catalog.panel.tell_me')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php // Reassurance, under every tab. These are the three objections a
              // buyer raises last, and answering them at the button is worth
              // more than answering them on a policies page nobody opens. ?>
        <ul class="space-y-1.5 border-t border-line px-6 py-4 text-xs text-white/55">
            <li><?= esc(lang('Catalog.panel.assure_transfer')) ?></li>
            <li><?= esc(lang('Catalog.panel.assure_retake')) ?></li>
            <li><?= esc(lang('Catalog.panel.assure_recording')) ?></li>
        </ul>
    </div>
</aside>
