<?php
helper(['url', 'norlanka', 'catalog', 'commerce']);

/**
 * The settings for one date.
 *
 * Rendered twice from one file: on its own for a new date, and embedded in the
 * session screen for an existing one — there is no separate edit URL, because
 * the roster, the register and the settings are the same job and splitting them
 * across two pages means walking back and forth while a learner is on the
 * telephone. `extend()` must not run in the embedded case or the admin chrome
 * would be drawn a second time inside the page; the section markers are
 * bracketed for the same reason.
 *
 * `seats_sold` and `seats_reserved` appear on this page as text and never as
 * inputs. They are inventory, written only by InventoryService inside a
 * transaction, and a field over them — even a disabled one, which is a name in
 * the DOM somebody can re-enable — is how a classroom gets oversold.
 */

$embed = $embed ?? false;
if (! $embed) {
    $this->extend('Modules\Admin\Views\layout');
}

$row      = $row ?? null;
$isNew    = $row === null;
$action   = $isNew ? site_url('admin/course-sessions') : site_url('admin/course-sessions/' . $row['id']);
$input    = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
$labelCls = 'mb-1 block text-xs font-semibold uppercase tracking-widest text-white/50';
$card     = 'rounded-2xl border border-white/10 bg-white/[0.02] p-6';

$val = static fn (string $name, $fallback = '') => old($name) ?? ($row[$name] ?? $fallback);

// A cancelled date is read-only ground: its learners were cancelled with it.
$cancelled = ($row['status'] ?? '') === 'cancelled';

// The dropdown only offers the statuses a person chooses. `confirmed` and
// `full` are derived from the seat count by InventoryService on every save, so
// a row sitting in one of them is shown as `open` here and put straight back
// where it belongs the moment this form is submitted.
$statusValue = (string) ($val('status', 'draft'));
if (! in_array($statusValue, $statuses, true)) {
    $statusValue = 'open';
}

// The day editor is seeded from the posted values after a failed save, so a
// validation bounce does not lose an hour of typing.
$seedDays = old('days');
if (! is_array($seedDays)) {
    $seedDays = [];
    foreach ($dayRows as $d) {
        $seedDays[] = [
            'date'  => (string) $d['day_date'],
            'start' => substr((string) $d['start_time'], 0, 5),
            'end'   => substr((string) $d['end_time'], 0, 5),
            'url'   => (string) ($d['meeting_url'] ?? ''),
        ];
    }
}

$oldPrices   = old('prices');
$seedAmounts = [];
foreach ($currencies as $currency) {
    $code               = strtoupper($currency['code']);
    $seedAmounts[$code] = (string) (is_array($oldPrices) ? ($oldPrices[$code]['price'] ?? '') : ($prices[$code]['price_cents'] ?? ''));
}
?>
<?php if (! $embed): ?><?= $this->section('content') ?><?php endif; ?>

<form method="post" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- What is taught, by whom, where -->
        <div class="<?= $card ?>">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-white/60">Course &amp; delivery</h3>

            <label class="<?= $labelCls ?>">Course</label>
            <select name="course_id" class="<?= $input ?>" required>
                <option value="">— Choose a course —</option>
                <?php $chosen = (int) ($val('course_id', $presetCourse ?? 0)); ?>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $chosen === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= esc(t_field($c['title'])) ?><?= $c['status'] === 'published' ? '' : ' (draft)' ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="<?= $labelCls ?>">Delivery mode</label>
                    <select name="mode" class="<?= $input ?>">
                        <?php foreach ($modes as $m): ?>
                            <option value="<?= esc($m, 'attr') ?>" <?= (string) $val('mode', 'LIVE_ONLINE') === $m ? 'selected' : '' ?>><?= esc(mode_label($m)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="<?= $labelCls ?>">Language</label>
                    <select name="language" class="<?= $input ?>">
                        <?php foreach ($locales as $l): ?>
                            <option value="<?= esc($l, 'attr') ?>" <?= (string) $val('language', 'en') === $l ? 'selected' : '' ?>><?= esc(strtoupper($l)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="<?= $labelCls ?>">Venue</label>
                    <select name="venue_id" class="<?= $input ?>">
                        <option value="">— Online, no venue —</option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= (int) $v['id'] ?>" <?= (int) $val('venue_id', 0) === (int) $v['id'] ? 'selected' : '' ?>>
                                <?= esc($v['name']) ?><?= empty($v['city']) ? '' : ' — ' . esc($v['city']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="<?= $labelCls ?>">Instructor</label>
                    <select name="instructor_id" class="<?= $input ?>">
                        <option value="">— Not yet assigned —</option>
                        <?php foreach ($instructors as $i): ?>
                            <option value="<?= (int) $i['id'] ?>" <?= (int) $val('instructor_id', 0) === (int) $i['id'] ? 'selected' : '' ?>>
                                <?= esc($i['name']) ?><?= (int) $i['is_placeholder'] === 1 ? ' (placeholder)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="<?= $labelCls ?>">Status</label>
                <?php if ($cancelled): ?>
                    <p class="rounded-lg border border-brand-red/40 bg-brand-red/10 px-3 py-2 text-sm text-brand-red">
                        Cancelled. A cancelled date is not reopened — everybody who was booked on it has been cancelled
                        and refunded, so create a new date instead.
                    </p>
                    <?php /* `status` is a required rule, so a valid value still has to be posted; the
                             controller pins a cancelled date to `cancelled` whatever arrives here. */ ?>
                    <input type="hidden" name="status" value="draft">
                <?php else: ?>
                    <select name="status" class="<?= $input ?>">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= esc($s, 'attr') ?>" <?= $statusValue === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-white/40">
                        <em>Confirmed</em> and <em>Full</em> are not on this list: they are set from the seat count every
                        time this date is saved, so "confirmed to run" is always a fact rather than a dropdown somebody
                        forgot. Cancelling is its own action, further down.
                    </p>
                <?php endif; ?>
            </div>

            <label class="mt-4 inline-flex items-center gap-2">
                <input type="checkbox" name="is_private" value="1" <?= $val('is_private', 0) ? 'checked' : '' ?>>
                <span class="text-sm text-white/70">Private — a closed company class, kept off the public schedule</span>
            </label>
        </div>

        <!-- When -->
        <div class="<?= $card ?>">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-white/60">Dates &amp; times</h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="<?= $labelCls ?>">First day</label>
                    <input type="date" id="f_start" name="start_date" value="<?= esc($val('start_date')) ?>" class="<?= $input ?>">
                </div>
                <div>
                    <label class="<?= $labelCls ?>">Last day</label>
                    <input type="date" id="f_end" name="end_date" value="<?= esc($val('end_date')) ?>" class="<?= $input ?>">
                </div>
            </div>
            <p class="mt-1 text-xs text-white/40">Leave both empty for self-paced study, which starts whenever the learner does.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="<?= $labelCls ?>">Starts each day</label>
                    <input type="time" id="f_dstart" name="daily_start" value="<?= esc(substr((string) $val('daily_start'), 0, 5)) ?>" class="<?= $input ?>">
                </div>
                <div>
                    <label class="<?= $labelCls ?>">Ends each day</label>
                    <input type="time" id="f_dend" name="daily_end" value="<?= esc(substr((string) $val('daily_end'), 0, 5)) ?>" class="<?= $input ?>">
                </div>
            </div>

            <div class="mt-4">
                <label class="<?= $labelCls ?>">Timezone</label>
                <input type="text" name="timezone" list="tz_options" value="<?= esc($val('timezone', 'Asia/Colombo')) ?>" class="<?= $input ?>">
                <datalist id="tz_options">
                    <?php foreach (['Asia/Colombo', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Europe/London', 'America/New_York', 'Australia/Sydney', 'UTC'] as $tz): ?>
                        <option value="<?= esc($tz, 'attr') ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <p class="mt-1 text-xs text-white/40">
                    The class's own clock. Every time on the site is shown in this zone and named, because a learner
                    three hours away who reads "09:00" and nothing else turns up at the wrong hour.
                </p>
            </div>
        </div>
    </div>

    <!-- Individual days: the attendance register and the calendar file hang off these -->
    <div class="<?= $card ?>" x-data="{
            days: <?= esc(json_encode($seedDays, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'attr') ?>,
            field(id) { const el = document.getElementById(id); return el ? el.value : ''; },
            add() { this.days.push({ date: '', start: this.field('f_dstart'), end: this.field('f_dend'), url: '' }); },
            fill() {
                const first = this.field('f_start');
                if (first === '') { return; }
                const last = new Date((this.field('f_end') || first) + 'T00:00:00');
                const cursor = new Date(first + 'T00:00:00');
                const st = this.field('f_dstart'), en = this.field('f_dend');
                const stamp = (x) => x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0');
                const out = [];
                for (let i = 0; i !== 60; i++) {
                    if (cursor.getTime() > last.getTime()) { break; }
                    out.push({ date: stamp(cursor), start: st, end: en, url: '' });
                    cursor.setDate(cursor.getDate() + 1);
                }
                this.days = out;
            }
        }">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">The days themselves</h3>
            <div class="flex gap-2">
                <button type="button" @click="fill()" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/70 hover:border-white/40">Fill from the date range</button>
                <button type="button" @click="add()" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/70 hover:border-white/40">+ Add a day</button>
            </div>
        </div>

        <p class="mb-4 text-xs text-white/40">
            One row per teaching day — a weekend course running over two weekends is four rows, not eight. The register is
            marked per day and the calendar file is built from these, so a class with no days can be taught but not
            registered. Leaving this empty on a dated class fills in the range automatically. A day that already carries
            attendance is kept even if you remove it here: deleting it would take the register with it.
        </p>

        <template x-for="(d, i) in days" :key="i">
            <div class="mb-2 grid grid-cols-12 gap-2">
                <input type="date" x-model="d.date" :name="'days[' + i + '][date]'" class="<?= $input ?> col-span-6 sm:col-span-3">
                <input type="time" x-model="d.start" :name="'days[' + i + '][start]'" class="<?= $input ?> col-span-3 sm:col-span-2">
                <input type="time" x-model="d.end" :name="'days[' + i + '][end]'" class="<?= $input ?> col-span-3 sm:col-span-2">
                <input type="url" x-model="d.url" :name="'days[' + i + '][url]'" placeholder="Joining link for this day (optional)" class="<?= $input ?> col-span-11 sm:col-span-4">
                <button type="button" @click="days.splice(i, 1)" class="col-span-1 rounded-lg border border-white/15 text-white/40 hover:border-brand-red hover:text-brand-red" aria-label="Remove this day">&times;</button>
            </div>
        </template>
        <p x-show="days.length === 0" class="text-sm text-white/40">No days listed. Save and the date range will be filled in for you.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Seats: two numbers editable, two read-only, and the reason said out loud -->
        <div class="<?= $card ?>">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-white/60">Seats</h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="<?= $labelCls ?>">Seats in the room</label>
                    <input type="number" min="0" name="seats_total" value="<?= esc($val('seats_total', 12)) ?>" class="<?= $input ?>">
                    <p class="mt-1 text-xs text-white/40">0 means unlimited — self-paced study, and the occasional webinar.</p>
                </div>
                <div>
                    <label class="<?= $labelCls ?>">Minimum to run</label>
                    <input type="number" min="0" name="min_to_run" value="<?= esc($val('min_to_run', 3)) ?>" class="<?= $input ?>">
                    <p class="mt-1 text-xs text-white/40">Below this the date is honest about not being confirmed yet.</p>
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-white/10 bg-black/20 p-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-white/40">Sold</p>
                        <p class="mt-1 text-2xl font-bold"><?= $isNew ? '0' : (int) $row['seats_sold'] ?></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-widest text-white/40">Held in carts</p>
                        <p class="mt-1 text-2xl font-bold"><?= $isNew ? '0' : (int) $row['seats_reserved'] ?></p>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-white/45">
                    These two are not editable, on purpose. They are inventory: they are written only when a payment
                    clears or a cart takes a hold, inside a database transaction that locks the row first. A text box
                    over them would let two people be sold the same chair, which is the one mistake a training company
                    cannot apologise its way out of. If they ever look wrong, run <code class="text-white/70">spark seats:reconcile</code>,
                    which recomputes them from the orders and holds that actually exist.
                </p>
            </div>
        </div>

        <!-- Price, per currency, published rather than converted -->
        <div class="<?= $card ?>" x-data="{
                amounts: <?= esc(json_encode($seedAmounts, JSON_UNESCAPED_UNICODE), 'attr') ?>,
                show(code) {
                    const digits = String(this.amounts[code] || '').replace(/\D/g, '');
                    if (digits === '') { return 'not sold in this currency'; }
                    const whole = (digits.slice(0, -2) || '0').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    return whole + '.' + digits.slice(-2).padStart(2, '0');
                }
            }">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-white/60">Price</h3>

            <?php foreach ($currencies as $currency): $code = strtoupper($currency['code']); ?>
                <div class="mb-4">
                    <label class="<?= $labelCls ?>"><?= esc($code) ?> — <?= esc($currency['name'] ?? $code) ?></label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input type="text" inputmode="numeric" x-model="amounts.<?= esc($code) ?>"
                               name="prices[<?= esc($code, 'attr') ?>][price]"
                               placeholder="Price" class="<?= $input ?>">
                        <input type="text" inputmode="numeric"
                               name="prices[<?= esc($code, 'attr') ?>][compare]"
                               value="<?= esc(is_array($oldPrices) ? ($oldPrices[$code]['compare'] ?? '') : ($prices[$code]['compare_at_cents'] ?? '')) ?>"
                               placeholder="Was (optional)" class="<?= $input ?>">
                    </div>
                    <p class="mt-1 text-xs text-white/40">
                        <span class="text-white/60"><?= esc($currency['symbol'] ?? '') ?></span>
                        <span x-text="show('<?= esc($code) ?>')"></span>
                        <?php if (isset($prices[$code])): ?>
                            · currently <?= esc(money((int) $prices[$code]['price_cents'], $code)) ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>

            <p class="text-xs leading-relaxed text-white/45">
                Amounts are in the currency's smallest unit, so <code class="text-white/70">42500</code> is $425.00 and
                <code class="text-white/70">2800000</code> is Rs 28,000 — the line under each box shows what you have
                typed. Whole numbers only, because a price that passes through a decimal on its way to the database is a
                price that eventually arrives as 42499. Each currency is published separately and never converted from
                the other at checkout: a live exchange rate produces Rs 47,382 on a page and loses margin control the
                day the rate moves. Clear a box to stop selling this date in that currency.
            </p>
        </div>
    </div>

    <!-- The joining link -->
    <div class="<?= $card ?>">
        <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-white/60">Joining instructions</h3>
        <div class="grid gap-4 sm:grid-cols-[12rem_1fr]">
            <div>
                <label class="<?= $labelCls ?>">Provider</label>
                <select name="meeting_provider" class="<?= $input ?>">
                    <?php foreach (['' => '— None —', 'zoom' => 'Zoom', 'teams' => 'Microsoft Teams', 'meet' => 'Google Meet', 'other' => 'Other'] as $k => $label): ?>
                        <option value="<?= esc($k, 'attr') ?>" <?= (string) $val('meeting_provider') === (string) $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="<?= $labelCls ?>">Link for the whole date</label>
                <input type="url" name="meeting_url" value="<?= esc(old('meeting_url') ?? ($joiningUrl ?? '')) ?>" placeholder="https://…" class="<?= $input ?>">
                <p class="mt-1 text-xs text-white/40">
                    Stored encrypted: a joining link is a key to a class somebody paid for, and in a plain database dump
                    it lets anybody walk in. Clearing this box removes the link rather than keeping the old one. A day
                    with its own link, above, overrides this one.
                </p>
            </div>
        </div>
    </div>

    <div class="<?= $card ?>">
        <label class="<?= $labelCls ?>">Internal notes</label>
        <textarea name="notes" rows="3" class="<?= $input ?>" placeholder="Anything the team needs to know — parking, catering, a corporate contact…"><?= esc($val('notes')) ?></textarea>
    </div>

    <div class="flex gap-3">
        <button class="btn-brand"><?= $isNew ? 'Create date' : 'Save date' ?></button>
        <a href="<?= site_url('admin/course-sessions') ?>" class="btn-ghost">Back to all dates</a>
    </div>
</form>

<?php if (! $embed): ?><?= $this->endSection() ?><?php endif; ?>
