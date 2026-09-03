<?php helper(['norlanka', 'url']); if (empty($section['blocks'])) { return; } $c = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: []; ?>
<?php $mist = (string) ($c['image'] ?? ''); $hasMist = $mist !== '' && is_file(FCPATH . ltrim($mist, '/')); ?>
<section class="relative overflow-hidden py-28 <?= $hasMist ? 'mist-scene' : '' ?>">
    <?php if ($hasMist): ?>
        <?= view('Modules\\Core\\Views\\partials\\mist_scene', ['image' => $mist, 'alt' => '']) ?>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-10 opacity-70"></div>
    <?php endif; ?>
    <div class="container-x text-center" data-gsap="reveal">
        <h2 class="text-3xl font-bold sm:text-5xl"><?= esc(t_field($c['title'] ?? [])) ?></h2>
        <p class="mx-auto mt-4 max-w-xl text-white/70"><?= esc(t_field($c['text'] ?? [])) ?></p>
        <?php // A booking button opens the dialog rather than navigating, when the
              // block asks for it. It stays a real link to the same destination, so
              // without JavaScript it still goes somewhere useful. ?>
        <a href="<?= esc(locale_url($c['url'] ?? 'contact')) ?>"
           <?= ($c['modal'] ?? '') === 'booking' ? 'x-data @click.prevent="$dispatch(\'booking-open\')"' : '' ?>
           class="btn-brand mt-8"><?= esc(t_field($c['button'] ?? [])) ?></a>
    </div>
</section>
