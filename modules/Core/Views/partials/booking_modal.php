<?php helper(['norlanka', 'url']);

/**
 * The booking request form, in a dialog opened from the hero.
 *
 * The hotel takes no payment here and publishes no live availability, so this
 * asks for what staff need in order to ring back — who, when, how many, which
 * room — and says so plainly rather than implying a confirmed reservation.
 *
 * Opened by dispatching `booking-open` on window, so any button anywhere can
 * open it without knowing anything about this markup.
 *
 * It is one <form> with a real action and method. With JavaScript the submit is
 * intercepted and answered in place; without it the same fields post to the
 * same endpoint. The dialog is `open` by default and hidden by CSS only once
 * Alpine has taken charge (`x-cloak`), so a reader without JS still reaches the
 * form instead of a button that does nothing.
 */
$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<div x-data="bookingModal()"
     x-on:booking-open.window="open()"
     x-on:keydown.escape.window="close()"
     class="booking-modal">

    <div x-show="isOpen" x-cloak x-transition.opacity
         class="fixed inset-0 z-[90] bg-black/60 backdrop-blur-sm"
         @click="close()" aria-hidden="true"></div>

    <div x-show="isOpen" x-cloak
         class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto p-4 sm:items-center sm:p-6"
         role="dialog" aria-modal="true" aria-labelledby="booking-title">

        <div x-transition
             @click.outside="close()"
             class="panel-forest relative my-8 w-full max-w-2xl overflow-hidden p-7 shadow-2xl sm:p-10">

            <button type="button" @click="close()"
                    class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full
                           border border-white/30 transition hover:bg-white/10
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    aria-label="<?= esc(lang('Site.booking.close'), 'attr') ?>">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>

            <!-- Sent -->
            <div x-show="sent" x-cloak class="py-10 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/15">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg>
                </span>
                <h2 class="mt-6 text-2xl font-bold"><?= esc(lang('Site.booking.ok_title')) ?></h2>
                <p class="mx-auto mt-3 max-w-sm text-white/85"><?= esc(lang('Site.booking.ok_text')) ?></p>
            </div>

            <form x-show="! sent" method="post" action="<?= esc(site_url('api/booking'), 'attr') ?>"
                  @submit.prevent="submit($event.target)" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="locale" value="<?= esc(current_locale(), 'attr') ?>">
                <!-- Honeypot: never shown, never filled by a person. -->
                <input type="text" name="website" tabindex="-1" autocomplete="off"
                       class="hidden" aria-hidden="true">

                <h2 id="booking-title" class="pr-10 text-2xl font-bold sm:text-3xl">
                    <?= esc(lang('Site.booking.title')) ?>
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-white/80"><?= esc(lang('Site.booking.intro')) ?></p>

                <div class="mt-7 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.name')) ?></span>
                        <input type="text" name="name" required maxlength="128" autocomplete="name" class="bk-input">
                        <span class="bk-err" x-text="errors.name"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.email')) ?></span>
                        <input type="email" name="email" required maxlength="191" autocomplete="email" class="bk-input">
                        <span class="bk-err" x-text="errors.email"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.phone')) ?></span>
                        <input type="tel" name="phone" maxlength="32" autocomplete="tel" class="bk-input">
                        <span class="bk-err" x-text="errors.phone"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.room')) ?></span>
                        <select name="room_type" class="bk-input">
                            <option value="either"><?= esc(lang('Site.booking.room_any')) ?></option>
                            <option value="deluxe"><?= esc(lang('Site.booking.room_dlx')) ?></option>
                            <option value="standard"><?= esc(lang('Site.booking.room_std')) ?></option>
                        </select>
                        <span class="bk-err" x-text="errors.room_type"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.check_in')) ?></span>
                        <input type="date" name="check_in" required
                               min="<?= esc($today, 'attr') ?>" value="<?= esc($today, 'attr') ?>"
                               x-ref="checkIn" @change="syncDates()" class="bk-input">
                        <span class="bk-err" x-text="errors.check_in"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.check_out')) ?></span>
                        <input type="date" name="check_out" required
                               min="<?= esc($tomorrow, 'attr') ?>" value="<?= esc($tomorrow, 'attr') ?>"
                               x-ref="checkOut" class="bk-input">
                        <span class="bk-err" x-text="errors.check_out"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.adults')) ?></span>
                        <input type="number" name="adults" required min="1" max="20" value="2" class="bk-input">
                        <span class="bk-err" x-text="errors.adults"></span>
                    </label>
                    <label class="block">
                        <span class="bk-label"><?= esc(lang('Site.booking.children')) ?></span>
                        <input type="number" name="children" min="0" max="20" value="0" class="bk-input">
                        <span class="bk-err" x-text="errors.children"></span>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="bk-label"><?= esc(lang('Site.booking.message')) ?></span>
                        <textarea name="message" rows="3" maxlength="2000" class="bk-input"></textarea>
                        <span class="bk-err" x-text="errors.message"></span>
                    </label>
                </div>

                <p x-show="failed" x-cloak class="mt-5 text-sm font-semibold" style="color:#FFD9D4">
                    <?= esc(lang('Site.booking.err')) ?>
                </p>

                <?php // The same request, by whichever route the guest prefers. The
                      // form reaches the inbox staff already watch; WhatsApp reaches
                      // a phone, which for a small hotel is often answered sooner.
                      // Both carry the same details, so neither is a lesser path.
                      // Rendered only when a WhatsApp number is configured. ?>
                <?php $waNumber = preg_replace('/\D+/', '', (string) setting('whatsapp', '', 'contact')); ?>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button type="submit" class="btn-brand w-full sm:w-auto" :disabled="busy">
                        <span x-text="busy ? '<?= esc(lang('Site.booking.sending'), 'attr') ?>' : '<?= esc(lang('Site.booking.send'), 'attr') ?>'"></span>
                    </button>
                    <?php if ($waNumber !== ''): ?>
                        <span class="hidden text-xs uppercase tracking-widest text-white/55 sm:inline"><?= esc(lang('Site.booking.or')) ?></span>
                        <button type="button" @click="toWhatsApp($el.closest('form'))"
                                class="btn-ghost w-full sm:w-auto"
                                data-wa="<?= esc($waNumber, 'attr') ?>">
                            <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.33 4.97L2 22l5.25-1.38a9.87 9.87 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/>
                            </svg>
                            <?= esc(lang('Site.booking.whatsapp')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
