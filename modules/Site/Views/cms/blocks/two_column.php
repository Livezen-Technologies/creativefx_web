<?php helper('norlanka');

// Optional film beneath the heading, filling the column the copy leaves empty.
$video   = ! empty($content['video'])  && is_file(FCPATH . ltrim((string) $content['video'], '/'))  ? $content['video']  : null;
$poster  = ! empty($content['poster']) && is_file(FCPATH . ltrim((string) $content['poster'], '/')) ? $content['poster'] : null;

// A still, for the pages that have a photograph rather than a film. Checked on
// disk like the film is, so a path with no file behind it leaves the column
// empty instead of rendering a broken image.
$image = ! empty($content['image']) && is_file(FCPATH . ltrim((string) $content['image'], '/')) ? $content['image'] : null;

$title   = t_field($content['title'] ?? []);
$eyebrow = ! empty($content['eyebrow']) ? t_field($content['eyebrow']) : '';
// Several pages seed the eyebrow and title from the same string, which just
// prints the same words twice. Show the eyebrow only when it adds something.
if (mb_strtolower(trim($eyebrow)) === mb_strtolower(trim($title))) {
    $eyebrow = '';
}
$items = (! empty($content['items']) && is_array($content['items'])) ? $content['items'] : [];

/*
 * Two arrangements, and the grid always gets exactly two children.
 *
 * The block's own habit is heading-and-media left, body right, which suits a
 * profile page. A destination introduction reads better as a column of prose
 * with the picture beside it, so `layout: text-left` moves the copy up beside
 * the heading and gives the whole right-hand column to the photograph.
 *
 * Building the columns rather than reordering them is deliberate: a third grid
 * child wraps in a two-column grid, and CSS `order` on a figure that is not
 * itself a grid child does nothing at all. Both were tried; both put the
 * heading in the wrong column.
 */
$mediaRight = ($content['layout'] ?? '') === 'text-left' && $image !== null && $video === null;

$heading = static function () use ($eyebrow, $title): void { ?>
    <?php if ($eyebrow !== ''): ?>
        <p class="eyebrow mb-4"><?= esc($eyebrow) ?></p>
    <?php endif; ?>
    <h2 class="text-3xl font-bold leading-tight sm:text-4xl"><?= esc($title) ?></h2>
<?php };

$media = static function (string $extra = '') use ($video, $poster, $image, $content): void { ?>
    <?php if ($video !== null): ?>
        <!-- A film the reader chooses to start, so it keeps its controls and
             poster rather than autoplaying beside the body copy. -->
        <figure class="nl-profile__media <?= esc($extra, 'attr') ?>">
            <video controls preload="metadata" playsinline class="aspect-video w-full bg-black object-cover"
                   <?= $poster !== null ? 'poster="' . esc($poster, 'attr') . '"' : '' ?>>
                <source src="<?= esc(media_src($video), 'attr') ?>" type="video/mp4">
            </video>
            <?php if (! empty($content['video_caption'])): ?>
                <figcaption><?= esc(t_field($content['video_caption'])) ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php elseif ($image !== null): ?>
        <figure class="nl-profile__media <?= esc($extra, 'attr') ?>">
            <img src="<?= esc(media_src($image), 'attr') ?>"
                 alt="<?= esc(t_field($content['image_alt'] ?? []), 'attr') ?>"
                 loading="lazy" width="1200" height="800"
                 class="w-full rounded-sm object-cover">
        </figure>
    <?php endif; ?>
<?php };

$body = static function () use ($content, $items): void { ?>
    <?php if (! empty($content['body'])): ?>
        <div class="nl-profile__body"><?= rich_text($content['body']) ?></div>
    <?php endif; ?>

    <?php if ($items !== []): ?>
        <!-- The block's hardest numbers: a panel rather than bullets, so they
             carry weight against the media opposite them. -->
        <ul class="nl-profile__facts">
            <?php foreach ($items as $item): ?>
                <li><?= esc(t_field($item)) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (! empty($content['link'])): ?>
        <p class="mt-6 text-sm"><?= source_link($content['link']) ?></p>
    <?php endif; ?>
<?php };
?>
<section class="nl-profile">
    <div class="container-x grid gap-12 lg:grid-cols-2 lg:gap-16 lg:items-start">
        <?php if ($mediaRight): ?>
            <div data-gsap="reveal">
                <?php $heading(); ?>
                <div class="mt-8"><?php $body(); ?></div>
            </div>
            <div data-gsap="reveal"><?php $media(); ?></div>
        <?php else: ?>
            <div data-gsap="reveal">
                <?php $heading(); ?>
                <?php $media('mt-9'); ?>
            </div>
            <div data-gsap="reveal"><?php $body(); ?></div>
        <?php endif; ?>
    </div>
</section>
