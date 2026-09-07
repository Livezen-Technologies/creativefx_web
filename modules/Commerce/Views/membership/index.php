<?php
helper(['norlanka', 'catalog', 'commerce', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

/**
 * The membership page.
 *
 * Four terms side by side, and the only honest way to compare four different
 * spans of time is a per-month figure under each — otherwise "17,500" and
 * "30,000" are two numbers with no relationship a reader can see. The saving is
 * shown against what the same period costs bought monthly, which is a real
 * comparison rather than an invented "RRP".
 *
 * What is *not* included is on the page, not in a footnote. A learner who buys
 * a pass expecting it to cover a classroom seat in Colombo has been misled by
 * the page, and finding out at checkout is worse than reading it here.
 *
 * @var list<array>  $plans
 * @var string       $currency
 * @var array|null   $current
 * @var int          $courses
 * @var int          $hours
 */
?>
<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Commerce.membership.eyebrow'),
    'heading' => lang('Commerce.membership.heading'),
    'intro'   => lang('Commerce.membership.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<section class="py-14 sm:py-20">
    <div class="container-x">

        <?php if ($current !== null): ?>
            <?php // Said before the prices, not after them. Somebody who already
                  // holds a pass needs to know that buying again extends it
                  // rather than replacing it — otherwise the rational move is to
                  // wait until the last day, which is worse for everybody. ?>
            <div class="mb-10 rounded-2xl border border-brand-red/30 bg-brand-red/5 p-5 text-sm">
                <p class="font-semibold">
                    <?= esc(lang('Commerce.membership.current', [
                        (new DateTimeImmutable((string) $current['expires_at']))->format('j F Y'),
                    ])) ?>
                </p>
                <p class="mt-1 text-white/70"><?= esc(lang('Commerce.membership.current_add')) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($courses > 0): ?>
            <p class="mb-8 text-sm text-white/60">
                <?= esc($hours > 0
                    ? lang('Commerce.membership.library', [$courses, $hours])
                    : lang('Commerce.membership.library_bare', [$courses])) ?>
            </p>
        <?php endif; ?>

        <?php if ($plans === []): ?>
            <?php // Not an empty grid. A plan is left out when it has no price in
                  // this currency, and four missing plans means the currency is
                  // unpriced — which is a thing to say plainly rather than an
                  // empty page to stare at. ?>
            <p class="rounded-2xl border border-line bg-surface p-6 text-sm text-white/70">
                <?= esc(lang('Commerce.membership.intro')) ?>
            </p>
        <?php else: ?>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($plans as $plan):
                    $saving = $plan['compare_at_cents'] !== null
                        ? max(0, $plan['compare_at_cents'] - $plan['price_cents'])
                        : 0;
                    // The best-value plan is the longest, which is also the last.
                    $featured = $plan === end($plans);
                    ?>
                    <article class="flex flex-col rounded-2xl border p-6 <?= $featured
                        ? 'border-brand-red/50 bg-brand-red/5'
                        : 'border-line bg-surface' ?>">
                        <h2 class="text-lg font-semibold"><?= esc(t_field($plan['name'])) ?></h2>

                        <p class="mt-4 text-3xl font-bold tracking-tight">
                            <?= esc(money((int) $plan['price_cents'], $currency)) ?>
                        </p>

                        <?php // The figure that makes four terms comparable. ?>
                        <p class="mt-1 text-sm text-white/60">
                            <?= esc(lang('Commerce.membership.per_month', [money((int) $plan['per_month_cents'], $currency)])) ?>
                        </p>

                        <?php if ($saving > 0): ?>
                            <p class="mt-3 inline-flex w-fit rounded-full bg-gold/15 px-2.5 py-1 text-xs font-semibold text-gold">
                                <?= esc(lang('Commerce.membership.save', [money($saving, $currency)])) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($summary = t_field($plan['summary'] ?? '')): ?>
                            <p class="mt-4 text-sm leading-relaxed text-white/70"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto pt-6">
                            <form method="post" action="<?= esc(locale_url('cart/add')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_type" value="membership">
                                <input type="hidden" name="item_id" value="<?= (int) $plan['id'] ?>">
                                <button class="<?= $featured ? 'btn-brand' : 'btn-ghost' ?> w-full">
                                    <?= esc(lang('Commerce.membership.choose', [t_field($plan['name'])])) ?>
                                </button>
                            </form>

                            <p class="mt-3 text-xs leading-relaxed text-white/50">
                                <?= esc((int) $plan['months'] === 1
                                    ? lang('Commerce.membership.billed_one')
                                    : lang('Commerce.membership.billed_once', [(int) $plan['months']])) ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php // Both lists, side by side and the same size. Putting the
              // exclusions in smaller type below the inclusions is how a page
              // technically discloses something and practically does not. ?>
        <div class="mt-14 grid gap-8 lg:grid-cols-2">
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 class="text-base font-semibold"><?= esc(lang('Commerce.membership.includes')) ?></h2>
                <ul class="mt-4 space-y-3 text-sm leading-relaxed text-white/70">
                    <li><?= esc(lang('Commerce.membership.inc_library')) ?></li>
                    <li><?= esc(lang('Commerce.membership.inc_pace')) ?></li>
                    <li><?= esc(lang('Commerce.membership.inc_assess')) ?></li>
                    <li><?= esc(lang('Commerce.membership.inc_nothing')) ?></li>
                </ul>
            </div>
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 class="text-base font-semibold"><?= esc(lang('Commerce.membership.excludes')) ?></h2>
                <ul class="mt-4 space-y-3 text-sm leading-relaxed text-white/70">
                    <li><?= esc(lang('Commerce.membership.exc_taught')) ?></li>
                </ul>
                <a href="<?= esc(locale_url('schedule')) ?>" class="mt-4 inline-block text-sm font-medium text-brand-red hover:underline">
                    <?= esc(lang('Catalog.schedule.title')) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
