<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// Social glyphs (Lucide-style 24px stroke icons) inlined like values_grid.php —
// three buttons do not justify shipping an icon font or a sprite. These are
// literal markup from this file, which is why they are echoed unescaped below.
$socialIcons = [
    'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6Z"/><rect x="2" y="9" width="4" height="12" rx="1"/><circle cx="4" cy="4" r="2"/>',
    'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37Z"/><path d="M17.5 6.5h.01"/>',
    'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
];

// Editors paste profile links in every shape. Full URLs and site-root paths are
// used as they are; anything else is given a scheme so a bare "linkedin.com/in/x"
// cannot resolve as a relative page — which also parks an exotic scheme
// (javascript:, data:) on a harmless dead host instead of firing it.
$socialHref = static function ($raw): ?string {
    $raw = trim((string) $raw);
    if ($raw === '') { return null; }

    return (str_starts_with($raw, 'http') || $raw[0] === '/') ? $raw : 'https://' . $raw;
};
?>
<!-- The people behind the work. Name and role ride in the scrim over each
     portrait; the bio joins them on hover/focus. Below is the payload contract:
     items[]{ name, role, bio, photo, linkedin, instagram, email }. -->
<section class="bg-brand-black py-16">
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

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($content['items'] as $item):
                $name = t_field($item['name'] ?? []);
                if ($name === '') { continue; } // an unnamed member has nothing to introduce
                $role  = t_field($item['role'] ?? []);
                $bio   = t_field($item['bio'] ?? []);
                $photo = ! empty($item['photo']) && is_file(FCPATH . ltrim((string) $item['photo'], '/')) ? $item['photo'] : null;

                // Only the channels actually filled in become buttons. There is no
                // Site.* wording for these, so the labels are written here, the same
                // way testimonials.php names its controls.
                $links = [];
                if ($url = $socialHref($item['linkedin'] ?? null)) {
                    $links[] = ['url' => $url, 'icon' => 'linkedin', 'label' => $name . ' on LinkedIn', 'external' => true];
                }
                if ($url = $socialHref($item['instagram'] ?? null)) {
                    $links[] = ['url' => $url, 'icon' => 'instagram', 'label' => $name . ' on Instagram', 'external' => true];
                }
                $email = trim((string) ($item['email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $links[] = ['url' => 'mailto:' . $email, 'icon' => 'mail', 'label' => 'Email ' . $name, 'external' => false];
                } ?>
                <article class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60 focus-within:border-brand-red/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red"
                         <?php // Without a focusable child a keyboard user could never open the bio, so the card itself becomes a stop — the tabindex idiom values_grid.php uses for its flip tiles. ?>
                         <?= $bio !== '' && $links === [] ? 'tabindex="0"' : '' ?> data-gsap="reveal">
                    <?php if ($photo !== null): ?>
                        <!-- object-top protects the face when a supplied portrait is a taller crop than 3:4. -->
                        <img src="<?= esc($photo, 'attr') ?>" alt="<?= esc($name, 'attr') ?>" loading="lazy"
                             class="aspect-[3/4] w-full object-cover object-top transition duration-500 group-hover:scale-[1.04] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                    <?php else: ?>
                        <!-- No portrait: a brand-tinted panel at the same ratio keeps the row
                             aligned and reads as deliberate, not as a failed image. -->
                        <div class="flex aspect-[3/4] items-center justify-center bg-brand-red/[0.08]" aria-hidden="true">
                            <span class="font-display text-5xl font-bold text-brand-red"><?= esc(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Scrim in the themeable ground colour, painted on the copy block itself
                         so it grows with the bio instead of leaving text on bare photo. -->
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-black via-brand-black/85 to-transparent px-6 pb-6 pt-16">
                        <h3 class="text-lg font-semibold leading-snug"><?= esc($name) ?></h3>
                        <?php if ($role !== ''): ?>
                            <p class="mt-1 text-xs uppercase tracking-widest text-brand-red"><?= esc($role) ?></p>
                        <?php endif; ?>

                        <?php if ($bio !== ''): ?>
                            <!-- 0fr→1fr grid row: animates open without guessing a max-height, so a
                                 long bio is never clipped mid-sentence. Collapsed only inside
                                 @media (hover:hover) — a touch screen has no hover to reveal it
                                 with, so there the bio simply stays part of the scrim. -->
                            <div class="grid grid-rows-[1fr] opacity-100 transition-[grid-template-rows,opacity] duration-300 ease-out motion-reduce:transition-none [@media(hover:hover)]:grid-rows-[0fr] [@media(hover:hover)]:opacity-0 group-hover:grid-rows-[1fr] group-hover:opacity-100 group-focus-within:grid-rows-[1fr] group-focus-within:opacity-100">
                                <p class="overflow-hidden text-sm leading-relaxed text-white/75">
                                    <?php // Spacing sits on an inner box: padding on the clipped element would leak as height while the row is collapsed. ?>
                                    <span class="block pt-3"><?= esc($bio) ?></span>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($links !== []): ?>
                            <ul class="mt-4 flex flex-wrap gap-2">
                                <?php foreach ($links as $link): ?>
                                    <li>
                                        <a href="<?= esc($link['url'], 'attr') ?>"<?= $link['external'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-brand-black/50 text-white/75 backdrop-blur transition hover:border-brand-red/60 hover:text-brand-red focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $socialIcons[$link['icon']] ?></svg>
                                            <!-- Icon-only button: the name makes each one distinct in a list of links. -->
                                            <span class="sr-only"><?= esc($link['label']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
