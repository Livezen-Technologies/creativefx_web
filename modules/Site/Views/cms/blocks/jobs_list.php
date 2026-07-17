<?php
helper(['norlanka', 'url']);
// Dynamic block: renders the currently-open vacancies from the Careers module.
$jobs = model('Modules\Careers\Models\JobModel')->openJobs();
?>
<section class="bg-brand-black py-16" id="openings">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <?php if ($jobs === []): ?>
            <p class="mt-10 rounded-2xl border border-white/10 bg-white/[0.02] p-8 text-white/60" data-gsap="reveal"><?= esc(lang('Site.careers.none')) ?></p>
        <?php else: ?>
            <div class="mt-10 grid gap-5 md:grid-cols-2">
                <?php foreach ($jobs as $job):
                    $jTitle = t_field(json_decode($job['title'] ?? '[]', true) ?: []);
                    $chips  = array_filter([$job['department'] ?? null, $job['location'] ?? ($job['country'] ?? null), $job['employment_type'] ?? null]);
                ?>
                    <a href="<?= esc(locale_url('careers/' . $job['slug'])) ?>"
                       class="group rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60 hover:bg-white/[0.04]" data-gsap="reveal">
                        <h3 class="text-xl font-semibold transition group-hover:text-brand-red"><?= esc($jTitle) ?></h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <?php foreach ($chips as $chip): ?>
                                <span class="rounded-full bg-white/[0.05] px-3 py-1 text-xs font-semibold uppercase tracking-widest text-white/60 ring-1 ring-white/10"><?= esc($chip) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <span class="mt-5 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-white/60 transition group-hover:text-white">
                            <?= esc(lang('Site.careers.view_apply')) ?>
                            <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
