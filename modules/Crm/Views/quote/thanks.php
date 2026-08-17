<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$email = trim((string) setting('email', '', 'contact'));
$phone = trim((string) setting('phone', '', 'contact'));

// Where to go next. A confirmation page that only says "thank you" is a dead
// end; these are the three things people actually do while they wait.
$onward = [
    ['portfolio', 'See the work', 'Brand films, photography, podcasts and live broadcasts we have delivered.'],
    ['services', 'Browse the services', 'What each service covers, how it runs and what you get back.'],
    ['contact', 'Talk to us', 'Studio address, hours and everything else in one place.'],
];
?>
<?= $this->section('head') ?>
<!-- A one-visitor page reached by redirect: nothing here belongs in an index. -->
<meta name="robots" content="noindex, follow">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<section class="relative overflow-hidden">
    <div class="hero-aurora absolute inset-0 -z-20"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-black/40 via-brand-black/10 to-brand-black"></div>

    <div class="container-x flex min-h-[52vh] flex-col justify-end pb-14 pt-40">
        <p class="eyebrow" data-gsap="reveal">Request received</p>
        <h1 class="mt-5 max-w-3xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal">Thank you — we have your brief</h1>
        <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal">
            A producer is reading it now. You will hear back within one working day with either a
            costed proposal in LKR or the two or three questions we need answered to write one.
        </p>
        <?php if ($reference !== ''): ?>
            <p class="mt-8 inline-flex w-fit items-center gap-3 rounded-full border border-brand-red/40 bg-brand-red/10 px-5 py-2.5 text-sm" data-gsap="reveal">
                <span class="text-xs font-semibold uppercase tracking-widest text-white/50">Reference</span>
                <span class="font-semibold text-brand-red"><?= esc($reference) ?></span>
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="bg-brand-black py-16">
    <div class="container-x">
        <h2 class="mb-3 text-3xl font-bold sm:text-4xl" data-gsap="reveal">While you wait</h2>
        <p class="mb-10 max-w-2xl text-white/60" data-gsap="reveal">
            Nothing else is needed from you. If something changes — a date, a budget, an extra
            deliverable — reply to the confirmation and we will fold it into the quote.
        </p>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-gsap="reveal">
            <?php foreach ($onward as [$slug, $cardTitle, $cardBody]): ?>
                <a href="<?= esc(locale_url($slug)) ?>"
                   class="group rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60">
                    <span class="block text-lg font-bold"><?= esc($cardTitle) ?></span>
                    <span class="mt-2 block text-sm leading-relaxed text-white/60"><?= esc($cardBody) ?></span>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-brand-red">
                        Open
                        <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($email !== '' || $phone !== ''): ?>
            <div class="mt-12 rounded-2xl border border-white/10 bg-white/[0.02] p-7" data-gsap="reveal">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/50">Something urgent</p>
                <p class="mt-3 text-white/70">
                    Reach the studio directly and quote your reference.
                </p>
                <ul class="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                    <?php if ($email !== ''): ?>
                        <li><a class="text-white hover:text-brand-red" href="mailto:<?= esc($email, 'attr') ?>"><?= esc($email) ?></a></li>
                    <?php endif; ?>
                    <?php if ($phone !== ''): ?>
                        <li><a class="text-white hover:text-brand-red" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>"><?= esc($phone) ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
