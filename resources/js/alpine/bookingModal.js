/**
 * The hero's booking request dialog.
 *
 * Opened by a `booking-open` event on window, so any button on any page can
 * open it without knowing this markup exists.
 *
 * Focus is handled here rather than with @alpinejs/focus, which this project
 * does not install. A dialog that does not hold focus is not a dialog: a
 * keyboard reader tabs straight out of it into the page behind, which is still
 * there and still scrollable, and has no way back. So: remember what was
 * focused, move into the form, keep Tab inside it, and give focus back on close.
 */
const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default () => ({
  isOpen: false,
  busy: false,
  sent: false,
  failed: false,
  errors: {},
  returnFocusTo: null,

  open() {
    this.returnFocusTo = document.activeElement;
    this.isOpen = true;
    this.sent = false;
    this.failed = false;
    this.errors = {};
    // The page behind must not scroll under the dialog.
    document.documentElement.style.overflow = 'hidden';
    this.$nextTick(() => {
      this.$el.querySelector('input[name="name"]')?.focus();
      document.addEventListener('keydown', this.trap);
    });
  },

  close() {
    if (! this.isOpen) return;
    this.isOpen = false;
    document.documentElement.style.overflow = '';
    document.removeEventListener('keydown', this.trap);
    this.returnFocusTo?.focus?.();
  },

  init() {
    // Bound once so add/removeEventListener see the same function object.
    this.trap = (e) => {
      if (e.key !== 'Tab' || ! this.isOpen) return;
      const items = [...this.$el.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
      if (! items.length) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (! e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    };
  },

  destroy() {
    document.removeEventListener('keydown', this.trap);
    document.documentElement.style.overflow = '';
  },

  /** Keep the two dates honest in the picker itself, before anything is sent. */
  syncDates() {
    const inEl = this.$refs.checkIn;
    const outEl = this.$refs.checkOut;
    if (! inEl?.value) return;
    const next = new Date(inEl.value);
    next.setDate(next.getDate() + 1);
    const min = next.toISOString().slice(0, 10);
    outEl.min = min;
    if (! outEl.value || outEl.value <= inEl.value) outEl.value = min;
  },

  async submit(form) {
    this.busy = true;
    this.failed = false;
    this.errors = {};
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
      });
      if (res.ok) {
        this.sent = true;
      } else {
        // The endpoint answers a 400 with a field -> message map. Showing them
        // against their own inputs beats one generic line at the bottom.
        const body = await res.json().catch(() => ({}));
        this.errors = body.messages ?? body ?? {};
        this.failed = true;
      }
    } catch {
      this.failed = true;
    } finally {
      this.busy = false;
    }
  },
});
