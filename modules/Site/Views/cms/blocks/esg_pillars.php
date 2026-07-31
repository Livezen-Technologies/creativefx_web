<?php helper('norlanka'); if (empty($content['pillars']) || ! is_array($content['pillars'])) { return; } ?>
<!-- Better Tomorrow pillars: flip tiles (name → description on hover/focus) with
     live baseline / current / target counters per KPI underneath. -->
<section class="bg-brand-black py-20">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            <?php foreach ($content['pillars'] as $pillar):
                $hasBack = ! empty($pillar['text']);
                // Photography behind the pillar name; falls back to the plain
                // card if the file is absent, so a bad path never leaves a gap.
                $image = ! empty($pillar['image']) && is_file(FCPATH . ltrim((string) $pillar['image'], '/')) ? $pillar['image'] : null; ?>
                <div class="flex flex-col gap-4" data-gsap="reveal">
                    <article class="flip-card <?= $image ? 'flip-card--photo' : 'flip-card--tall' ?> <?= $hasBack ? '' : 'flip-card--static' ?>" tabindex="0">
                        <div class="flip-card__inner">
                            <?php if ($image): ?>
                                <div class="flip-card__face flip-card__front value-tile">
                                    <img src="<?= esc($image, 'attr') ?>" alt="" loading="lazy" class="value-tile__img">
                                    <span class="value-tile__scrim" aria-hidden="true"></span>
                                    <span class="value-tile__body">
                                        <h3 class="value-tile__title value-tile__title--lg"><?= esc(t_field($pillar['title'] ?? [])) ?></h3>
                                    </span>
                                    <?php if ($hasBack): ?>
                                        <span class="value-tile__hint" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9a8 8 0 0 1 14.2-3.4M20 15a8 8 0 0 1-14.2 3.4M18 2v4h-4M6 22v-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                            <div class="flip-card__face flip-card__front rounded-2xl border border-white/10 bg-white/[0.02] p-8">
                                <h3 class="mt-auto text-2xl font-bold leading-tight text-brand-red"><?= esc(t_field($pillar['title'] ?? [])) ?></h3>
                                <?php if ($hasBack): ?>
                                    <span class="mt-3 inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-white/35">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9a8 8 0 0 1 14.2-3.4M20 15a8 8 0 0 1-14.2 3.4M18 2v4h-4M6 22v-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($hasBack): ?>
                                <div class="flip-card__face flip-card__back rounded-2xl border border-brand-red/40 bg-brand-red/[0.08] p-7">
                                    <h3 class="text-xs font-semibold uppercase tracking-widest text-brand-red"><?= esc(t_field($pillar['title'] ?? [])) ?></h3>
                                    <p class="mt-3 text-sm leading-relaxed text-white/80"><?= esc(t_field($pillar['text'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>

                    <?php if (! empty($pillar['targets']) && is_array($pillar['targets'])): ?>
                        <!-- Targets vs actual (live counters) -->
                        <div class="divide-y divide-white/[0.07] rounded-2xl border border-white/10 bg-white/[0.02]">
                            <?php foreach ($pillar['targets'] as $t):
                                $current  = (string) ($t['current'] ?? '');
                                $decimals = str_contains($current, '.') ? strlen(explode('.', $current)[1]) : 0; ?>
                                <div class="px-6 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-widest text-white/50"><?= esc(t_field($t['label'] ?? [])) ?></p>
                                    <p class="mt-1.5 flex items-baseline gap-1.5">
                                        <span class="text-2xl font-bold text-brand-red" data-counter="<?= esc($current, 'attr') ?>" data-decimals="<?= $decimals ?>">0</span>
                                        <span class="text-sm font-semibold text-brand-red"><?= esc($t['unit'] ?? '') ?></span>
                                    </p>
                                    <p class="mt-1 text-xs text-white/45">
                                        <?= esc(lang('Site.impact.baseline')) ?> <?= esc($t['baseline'] ?? '—') ?><?= esc($t['unit'] ?? '') ?>
                                        · <?= esc(lang('Site.impact.target')) ?> <?= esc($t['target'] ?? '—') ?><?= esc($t['unit'] ?? '') ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (! empty($pillar['items']) && is_array($pillar['items'])): ?>
                        <ul class="space-y-3 rounded-2xl border border-white/10 bg-white/[0.02] p-6">
                            <?php foreach ($pillar['items'] as $item): ?>
                                <li class="flex gap-3 text-sm text-white/75">
                                    <span class="mt-1.5 h-1.5 w-1.5 flex-none rounded-full bg-brand-red"></span>
                                    <span><?= esc(t_field($item)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
