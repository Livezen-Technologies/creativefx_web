<?php
helper(['norlanka', 'catalog', 'commerce', 'url']);

/**
 * One course, as a card.
 *
 * Carries the four things somebody scanning a catalogue actually compares:
 * what it is, what level, what it costs, and when the next one runs. A card
 * without a date is a card that cannot be compared with the one beside it.
 *
 * @var array $course   may carry from_cents, ondemand_cents, next_date, currency
 * @var bool  $compact
 */
$compact  = $compact ?? false;
$currency = $course['currency'] ?? current_currency();
?>
<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition hover:border-brand-red/40">
    <?php if (! $compact && ! empty($course['hero_image'])): ?>
        <a href="<?= esc(course_url($course['slug'])) ?>" class="block aspect-[16/9] overflow-hidden" tabindex="-1" aria-hidden="true">
            <img src="<?= esc(media_src($course['hero_image']), 'attr') ?>" alt=""
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                 loading="lazy" width="640" height="360">
        </a>
    <?php endif; ?>

    <div class="flex flex-1 flex-col p-5">
        <p class="flex flex-wrap items-center gap-2 text-xs text-white/50">
            <span class="chip"><?= esc(level_label((int) $course['level'])) ?></span>
            <?php if ($d = duration_label($course)): ?><span><?= esc($d) ?></span><?php endif; ?>
        </p>

        <h3 class="mt-3 text-lg font-semibold leading-snug">
            <a href="<?= esc(course_url($course['slug'])) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                <?= esc(t_field($course['title'])) ?>
            </a>
        </h3>

        <?php if ($summary = t_field($course['summary'] ?? '')): ?>
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
        <?php endif; ?>

        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
            <div>
                <?php if (! empty($course['from_cents'])): ?>
                    <p class="text-xs text-white/45"><?= esc(lang('Catalog.panel.from')) ?></p>
                    <p class="font-semibold"><?= esc(money((int) $course['from_cents'], $currency)) ?></p>
                <?php endif; ?>
                <?php if (! empty($course['ondemand_cents'])): ?>
                    <?php // Named separately rather than folded into the "from" figure: a
                          // self-paced recording and a taught seat are different products,
                          // and one price standing for both misleads whichever way it leans. ?>
                    <p class="mt-1 text-xs text-white/45">
                        <?= esc(lang('Catalog.card.or_ondemand', [money((int) $course['ondemand_cents'], $currency)])) ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php if (! empty($course['next_date'])): ?>
                <p class="text-right text-xs text-white/55">
                    <?= esc(lang('Catalog.card.next')) ?><br>
                    <span class="font-medium text-white/80"><?= esc((new DateTimeImmutable($course['next_date']))->format('j M')) ?></span>
                </p>
            <?php endif; ?>
        </div>
    </div>
</article>
