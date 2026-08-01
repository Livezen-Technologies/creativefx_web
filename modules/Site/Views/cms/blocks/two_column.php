<?php helper('norlanka');

// Optional film beneath the heading, filling the column the copy leaves empty.
$video   = ! empty($content['video'])  && is_file(FCPATH . ltrim((string) $content['video'], '/'))  ? $content['video']  : null;
$poster  = ! empty($content['poster']) && is_file(FCPATH . ltrim((string) $content['poster'], '/')) ? $content['poster'] : null;

$title   = t_field($content['title'] ?? []);
$eyebrow = ! empty($content['eyebrow']) ? t_field($content['eyebrow']) : '';
// Several pages seed the eyebrow and title from the same string, which just
// prints the same words twice. Show the eyebrow only when it adds something.
if (mb_strtolower(trim($eyebrow)) === mb_strtolower(trim($title))) {
    $eyebrow = '';
}
$items = (! empty($content['items']) && is_array($content['items'])) ? $content['items'] : [];
?>
<section class="nl-profile">
    <div class="container-x grid gap-12 lg:grid-cols-2 lg:gap-16 lg:items-start">
        <div data-gsap="reveal">
            <?php if ($eyebrow !== ''): ?>
                <p class="eyebrow mb-4"><?= esc($eyebrow) ?></p>
            <?php endif; ?>
            <h2 class="text-3xl font-bold leading-tight sm:text-4xl"><?= esc($title) ?></h2>

            <?php if ($video !== null): ?>
                <!-- A film the reader chooses to start, so it keeps its controls
                     and poster rather than autoplaying beside the body copy. -->
                <figure class="nl-profile__media mt-9">
                    <video controls preload="metadata" playsinline class="aspect-video w-full bg-black object-cover"
                           <?= $poster !== null ? 'poster="' . esc($poster, 'attr') . '"' : '' ?>>
                        <source src="<?= esc($video, 'attr') ?>" type="video/mp4">
                    </video>
                    <?php if (! empty($content['video_caption'])): ?>
                        <figcaption><?= esc(t_field($content['video_caption'])) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
        </div>

        <div data-gsap="reveal">
            <?php if (! empty($content['body'])): ?>
                <div class="nl-profile__body"><?= rich_text($content['body']) ?></div>
            <?php endif; ?>

            <?php if ($items !== []): ?>
                <!-- The block's hardest numbers: a panel rather than bullets, so
                     they carry weight against the film opposite them. -->
                <ul class="nl-profile__facts">
                    <?php foreach ($items as $item): ?>
                        <li><?= esc(t_field($item)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
