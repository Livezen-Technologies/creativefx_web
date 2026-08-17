<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$hasHero = ! empty($heroImage) && is_file(FCPATH . ltrim((string) $heroImage, '/'));

// Shared field idiom (same as the Careers application form).
$in    = 'w-full rounded-lg border border-white/15 bg-white/[0.04] px-3.5 py-2.5 text-sm placeholder:text-white/35 focus:border-brand-red focus:outline-none';
$label = 'mb-2 block text-xs font-semibold uppercase tracking-widest text-white/50';
$opt   = 'bg-brand-black text-white';

// A choice card: the whole tile is the label, and has-[:checked] lights it up,
// so the selected answer reads at a glance on a form this long.
$card = 'flex cursor-pointer items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.02] p-5 text-sm '
    . 'leading-snug transition hover:border-brand-red/60 has-[:checked]:border-brand-red has-[:checked]:bg-brand-red/[0.06]';
$radio = 'mt-0.5 h-4 w-4 flex-none accent-brand-red';

// Re-populated after a failed submit. old() escapes for HTML by default, which
// would double-escape everything below, so the raw value is asked for and
// escaped once at the point of output.
$was = static function (string $field, string $fallback = ''): string {
    $value = old($field, $fallback, false);

    return is_string($value) ? $value : $fallback;
};

// Step one may arrive pre-answered from a service page or a gear card; a failed
// submit always wins over it, so nothing the visitor typed is lost.
$checkedService = $was('service', $selectedService);
$checkedBudget  = $was('budget');
$oldMessage     = $was('message', $messagePrefill);

// The wizard's six steps. Rendered as headings inside the form and as the
// progress rail above it, so the sequence is the same whether Alpine is
// driving the page or the visitor is scrolling one long form.
$steps = [
    1 => 'Select service',
    2 => 'Project details',
    3 => 'Date and location',
    4 => 'Budget',
    5 => 'Contact details',
    6 => 'Submit',
];

$errors = (array) (session('errors') ?? []);
?>
<?= $this->section('content') ?>

<!-- Quote hero -->
<section class="relative overflow-hidden">
    <?php if ($hasHero): ?>
        <!-- Decorative: the headline below carries the meaning. -->
        <img src="<?= esc($heroImage, 'attr') ?>" alt="" class="absolute inset-0 -z-30 h-full w-full object-cover">
        <div class="absolute inset-0 -z-20 bg-gradient-to-r from-brand-black/95 via-brand-black/60 to-brand-black/25"></div>
        <div class="absolute inset-0 -z-20 bg-gradient-to-t from-brand-black via-brand-black/30 to-transparent"></div>
        <div class="hero-red-glow absolute inset-0 -z-10"></div>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-20"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-black/40 via-brand-black/10 to-brand-black"></div>
    <?php endif; ?>

    <div class="container-x flex min-h-[42vh] flex-col justify-end pb-12 pt-40">
        <p class="eyebrow" data-gsap="reveal">Request a quote</p>
        <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal">Tell us about the project</h1>
        <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal">
            Six short steps — what you need, when and where it happens, and how to reach you.
            We read every request ourselves and reply with a costed proposal in LKR within one
            working day.
        </p>
    </div>
</section>

<section class="bg-brand-black py-16">
    <div class="container-x grid gap-10 lg:grid-cols-12">

        <!-- What to expect. Sits beside the form on desktop, above it on mobile. -->
        <aside class="order-2 lg:order-1 lg:col-span-4" data-gsap="reveal">
            <h2 class="text-xl font-bold">What happens next</h2>
            <ol class="mt-6 space-y-6">
                <?php foreach ([
                    ['Within one working day', 'A producer reads the brief and comes back with questions or a first estimate.'],
                    ['A costed proposal', 'Scope, crew, kit and delivery dates, priced in LKR with nothing hidden underneath.'],
                    ['A date in the diary', 'Approve the proposal and we lock the shoot, the studio or the stream.'],
                ] as $i => [$stepTitle, $stepBody]): ?>
                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full border border-brand-red/50 text-xs font-bold text-brand-red"><?= $i + 1 ?></span>
                        <span class="block">
                            <span class="block text-sm font-semibold"><?= esc($stepTitle) ?></span>
                            <span class="mt-1 block text-sm leading-relaxed text-white/60"><?= esc($stepBody) ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>

            <?php
            $email = trim((string) setting('email', '', 'contact'));
            $phone = trim((string) setting('phone', '', 'contact'));
            ?>
            <?php if ($email !== '' || $phone !== ''): ?>
                <div class="mt-10 rounded-2xl border border-white/10 bg-white/[0.02] p-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/50">Rather just talk</p>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        <?php if ($email !== ''): ?>
                            <li><a class="text-white hover:text-brand-red" href="mailto:<?= esc($email, 'attr') ?>"><?= esc($email) ?></a></li>
                        <?php endif; ?>
                        <?php if ($phone !== ''): ?>
                            <li><a class="text-white hover:text-brand-red" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>"><?= esc($phone) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>

        <!--
            The wizard. Every field is in the DOM on first paint and the form is
            one ordinary POST, so with JavaScript off this is a single long form
            that submits in full. Alpine only shows and hides the steps (x-show,
            never x-if) and drives the progress rail; the "Continue" guard is a
            courtesy, and the server is the validator that counts.
        -->
        <div class="order-1 lg:order-2 lg:col-span-8" x-data="{
            step: 1,
            total: <?= count($steps) ?>,
            error: '',
            go(n) {
                if (n >= this.step) { return; }
                this.step = n;
                this.error = '';
            },
            next() {
                if (! this.complete()) { return; }
                this.error = '';
                if (this.step < this.total) { this.step++; this.toTop(); }
            },
            back() {
                this.error = '';
                if (this.step > 1) { this.step--; this.toTop(); }
            },
            send(event) {
                for (let n = 1; n <= this.total; n++) {
                    this.step = n;
                    if (! this.complete()) { event.preventDefault(); this.toTop(); return; }
                }
                this.error = '';
            },
            complete() {
                const panel = this.$refs['s' + this.step];
                if (! panel) { return true; }
                const radio = panel.querySelector('input[type=radio][required]');
                if (radio && ! panel.querySelector('input[type=radio]:checked')) {
                    this.error = 'Pick one of the options to continue.';
                    radio.focus();
                    return false;
                }
                const fields = panel.querySelectorAll('input[required]:not([type=radio]), select[required], textarea[required]');
                for (const field of fields) {
                    if (field.value.trim() !== '') { continue; }
                    this.error = 'Fill this in before moving on.';
                    field.focus();
                    return false;
                }
                return true;
            },
            toTop() {
                this.$refs.top.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }">
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 sm:p-8" x-ref="top">

                <?php if ($errors !== []): ?>
                    <div class="mb-8 rounded-xl border border-brand-red/40 bg-brand-red/10 px-5 py-4 text-sm text-brand-red" role="alert">
                        <p class="font-semibold">We could not send that yet.</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Progress. Wizard chrome only: x-cloak keeps it out of the
                     no-JavaScript page, where there are no steps to track. -->
                <div x-cloak class="mb-9">
                    <div class="flex items-baseline justify-between gap-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-white/50">
                            Step <span x-text="step">1</span> of <?= count($steps) ?>
                        </p>
                        <p class="text-sm font-semibold text-brand-red" x-text="Math.round(step / total * 100) + '%'"></p>
                    </div>
                    <div class="mt-3 h-1 w-full overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-brand-red transition-all duration-300 motion-reduce:transition-none"
                             :style="'width: ' + Math.round(step / total * 100) + '%'"></div>
                    </div>
                    <ol class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-[11px] font-semibold uppercase tracking-widest">
                        <?php foreach ($steps as $n => $stepLabel): ?>
                            <li>
                                <button type="button" @click="go(<?= $n ?>)" :disabled="step <= <?= $n ?>"
                                        :aria-current="step === <?= $n ?> ? 'step' : false"
                                        :class="step === <?= $n ?> ? 'text-brand-red' : (step > <?= $n ?> ? 'text-white/70 hover:text-white' : 'text-white/30')"
                                        class="transition"><?= $n ?>. <?= esc($stepLabel) ?></button>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <form method="post" action="<?= esc(locale_url('quote')) ?>" enctype="multipart/form-data"
                      :novalidate="true" @submit="send($event)"
                      @keydown.enter="if (step < total) { if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); next(); } }"
                      class="space-y-10">
                    <?= csrf_field() ?>
                    <!-- Honeypot: bots fill every field; humans never see this one. -->
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <!-- Step 1 — Select service -->
                    <fieldset x-ref="s1" x-show="step === 1">
                        <legend class="sr-only">Select service</legend>
                        <p class="eyebrow">Step 1</p>
                        <h2 class="mt-4 text-xl font-bold">What do you need us for?</h2>
                        <p class="mt-2 text-sm text-white/60">Pick the closest fit — we will scope the rest with you.</p>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <?php foreach ($services as $slug => $name): ?>
                                <label class="<?= $card ?>">
                                    <input type="radio" name="service" value="<?= esc($slug, 'attr') ?>" required
                                           <?= $slug === $checkedService ? 'checked' : '' ?> class="<?= $radio ?>">
                                    <span class="font-semibold"><?= esc($name) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <!-- Step 2 — Project details -->
                    <div x-ref="s2" x-show="step === 2">
                        <p class="eyebrow">Step 2</p>
                        <h2 class="mt-4 text-xl font-bold">Tell us about the project</h2>
                        <div class="mt-6 space-y-5">
                            <div>
                                <label class="<?= $label ?>" for="q-project-type">Project type</label>
                                <select id="q-project-type" name="project_type" class="<?= $in ?>">
                                    <option value="" class="<?= $opt ?>">Choose a project type</option>
                                    <?php foreach ($projectTypes as $type): ?>
                                        <option value="<?= esc($type, 'attr') ?>" class="<?= $opt ?>"
                                                <?= $was('project_type') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="<?= $label ?>" for="q-message">The brief *</label>
                                <textarea id="q-message" name="message" rows="6" required
                                          placeholder="What are we making, who is it for, and where will it be published?"
                                          class="<?= $in ?>"><?= esc($oldMessage) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 — Date and location -->
                    <div x-ref="s3" x-show="step === 3">
                        <p class="eyebrow">Step 3</p>
                        <h2 class="mt-4 text-xl font-bold">When and where</h2>
                        <p class="mt-2 text-sm text-white/60">Leave these blank if the dates are still moving.</p>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="<?= $label ?>" for="q-date">Preferred date</label>
                                <input id="q-date" type="date" name="preferred_date" value="<?= esc($was('preferred_date'), 'attr') ?>" class="<?= $in ?>">
                            </div>
                            <div>
                                <label class="<?= $label ?>" for="q-location">Location</label>
                                <input id="q-location" type="text" name="location" value="<?= esc($was('location'), 'attr') ?>"
                                       placeholder="Colombo, our studio, on location…" class="<?= $in ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 — Budget -->
                    <fieldset x-ref="s4" x-show="step === 4">
                        <legend class="sr-only">Budget</legend>
                        <p class="eyebrow">Step 4</p>
                        <h2 class="mt-4 text-xl font-bold">What is the budget?</h2>
                        <p class="mt-2 text-sm text-white/60">A band is enough. It tells us what to build, not what to charge.</p>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <?php foreach ($budgets as $band): ?>
                                <label class="<?= $card ?>">
                                    <input type="radio" name="budget" value="<?= esc($band, 'attr') ?>"
                                           <?= $band === $checkedBudget ? 'checked' : '' ?> class="<?= $radio ?>">
                                    <span class="font-semibold"><?= esc($band) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <!-- Step 5 — Contact details -->
                    <div x-ref="s5" x-show="step === 5">
                        <p class="eyebrow">Step 5</p>
                        <h2 class="mt-4 text-xl font-bold">How do we reach you?</h2>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="<?= $label ?>" for="q-name">Name *</label>
                                <input id="q-name" type="text" name="name" required autocomplete="name"
                                       value="<?= esc($was('name'), 'attr') ?>" class="<?= $in ?>">
                            </div>
                            <div>
                                <label class="<?= $label ?>" for="q-email">Email *</label>
                                <input id="q-email" type="email" name="email" required autocomplete="email"
                                       value="<?= esc($was('email'), 'attr') ?>" class="<?= $in ?>">
                            </div>
                            <div>
                                <label class="<?= $label ?>" for="q-phone">Phone</label>
                                <input id="q-phone" type="tel" name="phone" autocomplete="tel"
                                       value="<?= esc($was('phone'), 'attr') ?>" class="<?= $in ?>">
                            </div>
                            <div>
                                <label class="<?= $label ?>" for="q-company">Company</label>
                                <input id="q-company" type="text" name="company" autocomplete="organization"
                                       value="<?= esc($was('company'), 'attr') ?>" class="<?= $in ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Step 6 — Submit -->
                    <div x-ref="s6" x-show="step === 6">
                        <p class="eyebrow">Step 6</p>
                        <h2 class="mt-4 text-xl font-bold">Anything to send with it?</h2>
                        <p class="mt-2 text-sm text-white/60">
                            A deck, a moodboard, a shot list or a reference cut — optional, and it saves a round of questions.
                        </p>
                        <label class="mt-6 block">
                            <span class="<?= $label ?>">Attachment — PDF, JPG, PNG or ZIP, up to 8MB</span>
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.zip"
                                   class="block w-full text-sm text-white/70 file:mr-3 file:rounded-full file:border-0 file:bg-brand-red file:px-5 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-brand-red-dark">
                        </label>
                        <button type="submit" class="btn-brand mt-8 w-full sm:w-auto">Send the request</button>
                        <p class="mt-4 text-xs leading-relaxed text-white/45">
                            We use these details to prepare your quote and nothing else.
                        </p>
                    </div>

                    <!-- Wizard navigation. x-cloak: with JavaScript off there are
                         no steps to move between, and the submit button above is
                         already on the page. -->
                    <div x-cloak class="!mt-8 border-t border-white/10 pt-6">
                        <p x-show="error" x-text="error" class="mb-4 text-sm text-brand-red" role="alert"></p>
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <button type="button" x-show="step > 1" @click="back()" class="btn-ghost !px-5 !py-2.5 text-xs">Back</button>
                            </div>
                            <div>
                                <button type="button" x-show="step < total" @click="next()" class="btn-brand">Continue</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
