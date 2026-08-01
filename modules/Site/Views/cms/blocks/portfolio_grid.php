<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<!-- Product portfolio: each category opens its full look-book, which is hosted
     off-site, so every link leaves the site in a new tab. -->
<section class="nl-portfolio">
    <div class="container-x">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="eyebrow" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['title'])): ?>
            <h2 class="mt-5 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <ul class="nl-portfolio__grid" data-gsap="reveal">
            <?php foreach ($content['items'] as $item):
                $url   = trim((string) ($item['url'] ?? ''));
                $name  = t_field($item['name'] ?? []);
                $image = ! empty($item['image']) && is_file(FCPATH . ltrim((string) $item['image'], '/')) ? $item['image'] : null;
                if ($url === '' || $name === '') { continue; } ?>
                <li>
                    <a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="nl-portfolio__item">
                        <span class="nl-portfolio__disc">
                            <?php if ($image !== null): ?>
                                <img src="<?= esc($image, 'attr') ?>" alt="" loading="lazy">
                            <?php endif; ?>
                        </span>
                        <span class="nl-portfolio__name"><?= esc($name) ?></span>
                        <span class="nl-portfolio__cue">
                            <?= esc(lang('Site.portfolio.view')) ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/></svg>
                            <!-- Named for screen readers: the link leaves the site. -->
                            <span class="sr-only"><?= esc(lang('Site.portfolio.new_tab')) ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
