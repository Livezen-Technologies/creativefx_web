<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<!-- Milestone timeline. A single rail on small screens; on large screens the
     cards alternate either side of a centre spine whose accent fill tracks
     scroll progress. -->
<section class="nl-journey">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-14 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>

        <ol class="nl-timeline" data-timeline>
            <span class="nl-timeline__spine" aria-hidden="true">
                <span class="nl-timeline__fill" data-timeline-fill></span>
            </span>

            <?php foreach ($content['items'] as $item): ?>
                <li class="nl-timeline__item" data-gsap="reveal">
                    <span class="nl-timeline__node" aria-hidden="true"></span>
                    <article class="nl-timeline__card">
                        <span class="nl-timeline__year"><?= esc(t_field($item['year'] ?? [])) ?></span>
                        <h3 class="nl-timeline__title"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                        <?php if (! empty($item['text'])): ?>
                            <p class="nl-timeline__text"><?= esc(t_field($item['text'])) ?></p>
                        <?php endif; ?>
                    </article>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
