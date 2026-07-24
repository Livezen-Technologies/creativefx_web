<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$pTitle   = t_field(json_decode($post['title'] ?? '[]', true) ?: []);
$pBody    = t_field(json_decode($post['body'] ?? '[]', true) ?: []);
$pDate    = ! empty($post['published_at']) ? date('j M Y', strtotime($post['published_at'])) : '';
$catName  = $category ? t_field(json_decode($category['name'] ?? '[]', true) ?: []) : '';
$tags     = array_values(array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? '')))));

/**
 * Article body renderer. Rich-text bodies (authored in the admin editor) are
 * stored as HTML and rendered directly — with scripts/handlers stripped as a
 * safety net. Legacy plain-text bodies keep the original minimal formatter:
 * blank lines split paragraphs, "## " starts a heading, "- " lines make lists.
 */
$renderBody = static function (string $text): string {
    if (preg_match('/^\s*<(?:p|h[1-6]|ul|ol|blockquote|div|figure)[\s>]/i', $text)) {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $text);
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2/i', '$1="#"', $html);
        return '<div class="article-body">' . $html . '</div>';
    }

    $html = '';
    foreach (preg_split('/\n{2,}/', str_replace("\r\n", "\n", trim($text))) ?: [] as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }
        if (str_starts_with($chunk, '## ')) {
            $html .= '<h2 class="mt-10 text-2xl font-bold">' . esc(substr($chunk, 3)) . '</h2>';
            continue;
        }
        $lines = explode("\n", $chunk);
        $isList = $lines !== [] && array_filter($lines, static fn ($l) => ! str_starts_with(trim($l), '- ')) === [];
        if ($isList) {
            $html .= '<ul class="mt-5 space-y-2.5">';
            foreach ($lines as $l) {
                $html .= '<li class="flex gap-3 leading-relaxed text-white/70"><span class="mt-2.5 h-1.5 w-1.5 flex-none rounded-full bg-brand-red"></span><span>' . esc(substr(trim($l), 2)) . '</span></li>';
            }
            $html .= '</ul>';
            continue;
        }
        $html .= '<p class="mt-5 leading-relaxed text-white/70">' . nl2br(esc($chunk)) . '</p>';
    }
    return $html;
};
?>
<?= $this->section('content') ?>

<!-- Article hero -->
<article>
    <section class="relative overflow-hidden">
        <div class="hero-aurora absolute inset-0 -z-20"></div>
        <div class="container-x flex min-h-[36vh] flex-col justify-end pb-10 pt-36">
            <a href="<?= esc(locale_url('news')) ?>" class="mb-4 text-xs uppercase tracking-widest text-brand-red hover:underline">← <?= esc(lang('Site.news.back')) ?></a>
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-white/50" data-gsap="reveal">
                <?php if ($catName !== ''): ?>
                    <a href="<?= esc(locale_url('news') . '?category=' . ($category['slug'] ?? '')) ?>" class="text-brand-red hover:underline"><?= esc($catName) ?></a>
                    <span aria-hidden="true">·</span>
                <?php endif; ?>
                <?php if ($pDate !== ''): ?><time datetime="<?= esc(date('Y-m-d', strtotime($post['published_at'])), 'attr') ?>"><?= esc($pDate) ?></time><?php endif; ?>
                <?php if (! empty($post['author'])): ?>
                    <span aria-hidden="true">·</span>
                    <span><?= esc($post['author']) ?></span>
                <?php endif; ?>
            </div>
            <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-[1.1] sm:text-5xl" data-gsap="reveal"><?= esc($pTitle) ?></h1>
        </div>
    </section>

    <section class="bg-brand-black pb-20">
        <div class="container-x">
            <?php if (! empty($post['image'])): ?>
                <figure class="isolate mt-2 overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
                    <img src="<?= esc($post['image'], 'attr') ?>" alt="<?= esc($pTitle, 'attr') ?>" class="aspect-[21/9] w-full object-cover">
                </figure>
            <?php endif; ?>

            <div class="mx-auto mt-10 max-w-3xl text-[1.05rem]">
                <?= $renderBody($pBody) ?>

                <?php if ($tags !== []): ?>
                    <div class="mt-12 flex flex-wrap items-center gap-2 border-t border-white/10 pt-6">
                        <span class="text-xs font-semibold uppercase tracking-widest text-white/45"><?= esc(lang('Site.news.tags')) ?></span>
                        <?php foreach ($tags as $tag): ?>
                            <span class="rounded-full bg-white/[0.05] px-3.5 py-1.5 text-xs text-white/70 ring-1 ring-white/10"><?= esc($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($related !== []): ?>
                <div class="mt-16 border-t border-white/10 pt-10">
                    <h2 class="text-xl font-bold"><?= esc(lang('Site.news.related')) ?></h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($related as $r): ?>
                            <?= view('Modules\News\Views\partials\card', ['post' => $r, 'label' => '']) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</article>

<?= $this->endSection() ?>
