<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$jTitle = t_field(json_decode($job['title'] ?? '[]', true) ?: []);
$jDesc  = t_field(json_decode($job['description'] ?? '[]', true) ?: []);
$list   = static function (?string $json): array {
    $v = json_decode((string) $json, true);
    if (is_array($v)) { return array_values(array_filter(array_map('trim', $v))); }
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $json))));
};
$quals  = $list($job['qualifications'] ?? null);
$skills = $list($job['skills'] ?? null);
$chips  = array_filter([
    $job['department'] ?? null,
    $job['location'] ?? ($job['country'] ?? null),
    $job['employment_type'] ?? null,
    $job['experience'] ?? null,
    $job['salary_range'] ?? null,
]);
$deadline = ! empty($job['closes_at']) ? date('j M Y', strtotime($job['closes_at'])) : null;
?>
<?= $this->section('content') ?>

<!-- Vacancy hero -->
<section class="relative overflow-hidden">
    <div class="hero-aurora absolute inset-0 -z-20"></div>
    <div class="container-x flex min-h-[38vh] flex-col justify-end pb-10 pt-36">
        <a href="<?= esc(locale_url('careers')) ?>" class="mb-4 text-xs uppercase tracking-widest text-brand-red hover:underline">← <?= esc(lang('Site.careers.all')) ?></a>
        <h1 class="max-w-3xl text-4xl font-bold leading-[1.05] sm:text-5xl" data-gsap="reveal"><?= esc($jTitle) ?></h1>
        <div class="mt-5 flex flex-wrap gap-2" data-gsap="reveal">
            <?php foreach ($chips as $chip): ?>
                <span class="rounded-full border border-white/15 bg-white/[0.04] px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/70"><?= esc($chip) ?></span>
            <?php endforeach; ?>
            <?php if ($deadline): ?>
                <span class="rounded-full border border-brand-red/40 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-brand-red"><?= esc(lang('Site.careers.deadline')) ?>: <?= esc($deadline) ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="bg-brand-black py-16">
    <div class="container-x grid gap-12 lg:grid-cols-12">
        <!-- Role details -->
        <div class="lg:col-span-7">
            <?php if ($jDesc !== ''): ?>
                <h2 class="text-2xl font-bold"><?= esc(lang('Site.careers.about_role')) ?></h2>
                <div class="mt-4 max-w-2xl leading-relaxed text-white/70"><?= rich_text(json_decode($job['description'] ?? '[]', true) ?: []) ?></div>
            <?php endif; ?>

            <?php if ($quals !== []): ?>
                <h3 class="mt-10 text-lg font-semibold"><?= esc(lang('Site.careers.qualifications')) ?></h3>
                <ul class="mt-3 space-y-2 text-white/70">
                    <?php foreach ($quals as $q): ?>
                        <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-brand-red"></span><?= esc($q) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($skills !== []): ?>
                <h3 class="mt-10 text-lg font-semibold"><?= esc(lang('Site.careers.skills')) ?></h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach ($skills as $s): ?>
                        <span class="rounded-full bg-white/[0.05] px-3.5 py-1.5 text-sm text-white/75 ring-1 ring-white/10"><?= esc($s) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Application form -->
        <div class="lg:col-span-5">
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-7">
                <?php if (session('applied')): ?>
                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm text-emerald-300">
                        <strong class="block text-base"><?= esc(lang('Site.careers.applied_title')) ?></strong>
                        <span class="mt-1 block"><?= esc(lang('Site.careers.applied_body')) ?></span>
                    </div>
                <?php else: ?>
                    <h2 class="text-xl font-bold"><?= esc(lang('Site.careers.apply_title')) ?></h2>
                    <?php if (session('errors')): ?>
                        <div class="mt-4 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm text-brand-red">
                            <ul class="list-inside list-disc"><?php foreach ((array) session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <?php $in = 'w-full rounded-lg border border-white/15 bg-white/[0.04] px-3.5 py-2.5 text-sm placeholder:text-white/35 focus:border-brand-red focus:outline-none'; ?>
                    <form method="post" action="<?= esc(locale_url('careers/' . $job['slug'] . '/apply')) ?>" enctype="multipart/form-data" class="mt-5 space-y-3">
                        <?= csrf_field() ?>
                        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="text" name="name" required value="<?= esc(old('name') ?? '') ?>" placeholder="<?= esc(lang('Site.contact.name'), 'attr') ?> *" class="<?= $in ?>">
                            <input type="email" name="email" required value="<?= esc(old('email') ?? '') ?>" placeholder="<?= esc(lang('Site.contact.email'), 'attr') ?> *" class="<?= $in ?>">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="text" name="phone" value="<?= esc(old('phone') ?? '') ?>" placeholder="<?= esc(lang('Site.careers.phone'), 'attr') ?>" class="<?= $in ?>">
                            <input type="text" name="country" value="<?= esc(old('country') ?? '') ?>" placeholder="<?= esc(lang('Site.careers.country'), 'attr') ?>" class="<?= $in ?>">
                        </div>
                        <input type="text" name="education" value="<?= esc(old('education') ?? '') ?>" placeholder="<?= esc(lang('Site.careers.education'), 'attr') ?>" class="<?= $in ?>">
                        <input type="text" name="experience" value="<?= esc(old('experience') ?? '') ?>" placeholder="<?= esc(lang('Site.careers.experience'), 'attr') ?>" class="<?= $in ?>">
                        <input type="text" name="skills" value="<?= esc(old('skills') ?? '') ?>" placeholder="<?= esc(lang('Site.careers.your_skills'), 'attr') ?>" class="<?= $in ?>">
                        <input type="url" name="linkedin" value="<?= esc(old('linkedin') ?? '') ?>" placeholder="LinkedIn / Portfolio URL" class="<?= $in ?>">
                        <textarea name="cover_letter" rows="4" placeholder="<?= esc(lang('Site.careers.cover_letter'), 'attr') ?>" class="<?= $in ?>"><?= esc(old('cover_letter') ?? '') ?></textarea>
                        <label class="block text-xs uppercase tracking-widest text-white/50">
                            <?= esc(lang('Site.careers.cv')) ?> *
                            <input type="file" name="cv" required accept=".pdf,.doc,.docx"
                                   class="mt-2 block w-full text-sm text-white/70 file:mr-3 file:rounded-full file:border-0 file:bg-brand-red file:px-5 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-brand-red-dark">
                        </label>
                        <button type="submit" class="btn-brand w-full"><?= esc(lang('Site.careers.submit')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
