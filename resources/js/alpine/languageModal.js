const STORE_KEY = 'nl_locale';

/**
 * The first-visit language chooser.
 *
 * It reads the same key the header switcher writes and the welcome page reads,
 * so the three of them agree: choose once, anywhere, and nothing asks again.
 *
 * Both answers are answers. Picking a language stores it; dismissing stores the
 * language already being shown, because a reader who closed the dialog has
 * decided to carry on in what they can see, and re-asking on the next page
 * would be nagging rather than asking.
 */
export default function languageModal(config = {}) {
  return {
    open: false,
    current: config.current || 'en',
    codes: config.codes || ['en'],
    returnFocus: null,

    init() {
      // A preference already exists, or storage is unreadable and asking on
      // every page would be worse than not asking: stay shut either way.
      let chosen = null;
      try {
        chosen = localStorage.getItem(STORE_KEY);
      } catch (e) {
        return;
      }
      if (chosen && this.codes.includes(chosen)) return;

      this.whenVisible(() => this.show());
    },

    /**
     * Run once the reader can actually see the page.
     *
     * The brand preloader covers the whole viewport for its first second or so.
     * A dialog opened underneath it is invisible — and when the preloader is
     * removed from the document, focus goes back to <body>, taking the focus we
     * had just put inside the dialog with it. Both symptoms, one cause: do not
     * race the loading screen, wait for it to go.
     *
     * Then a beat longer. A dialog that lands on the first painted frame reads
     * as an error; one that arrives a moment later reads as an offer, and by
     * then the reader has seen what they came for.
     */
    whenVisible(fn) {
      const afterPreloader = () => {
        let waited = 0;
        const tick = () => {
          // The cap matters: the preloader has its own safety timeout, and a
          // bug there must not mean the language is never offered at all.
          if (!document.getElementById('preloader') || waited >= 5000) {
            window.setTimeout(fn, 450);
            return;
          }
          waited += 100;
          window.setTimeout(tick, 100);
        };
        tick();
      };

      if (document.readyState === 'complete') afterPreloader();
      else window.addEventListener('load', afterPreloader, { once: true });
    },

    /** Run once the browser has actually painted the change. */
    afterPaint(fn) {
      window.requestAnimationFrame(() => window.requestAnimationFrame(fn));
    },

    show() {
      this.returnFocus = document.activeElement;
      this.open = true;
      // The page behind must not scroll under the dialog, and everything else
      // on the page needs to know a modal owns the screen — otherwise two
      // components both listening for Escape on the window each handle the same
      // keypress, and closing this one also closes the help panel behind it.
      document.documentElement.style.overflow = 'hidden';
      document.documentElement.dataset.modalOpen = 'language';
      // Two frames, not $nextTick. `open` is set from a plain setTimeout, so it
      // lands outside Alpine's flush: $nextTick resolves before x-show has
      // written `display` back, and focus() on an element whose ancestor is
      // still display:none silently does nothing. Waiting for paint means the
      // dialog is really on the screen before we put the cursor in it.
      this.afterPaint(() => {
        const first = this.$refs.card?.querySelector('.lang-option[aria-current="true"], .lang-option');
        first?.focus();
      });
    },

    remember(code) {
      try {
        localStorage.setItem(STORE_KEY, code);
      } catch (e) { /* a private window; the choice still applies to this page */ }
    },

    close() {
      this.open = false;
      document.documentElement.style.overflow = '';
      delete document.documentElement.dataset.modalOpen;
      this.afterPaint(() => {
        if (this.returnFocus && document.contains(this.returnFocus)) this.returnFocus.focus();
      });
    },

    /** Dismissing is choosing what is already on the screen. */
    dismiss() {
      this.remember(this.current);
      this.close();
    },

    choose(code) {
      this.remember(code);
      if (code === this.current) {
        this.close();
        return;
      }

      // Swap the locale segment in place and keep the query and the fragment,
      // so the reader stays on the page they arrived at rather than being sent
      // to the front of the site in another language.
      const path = window.location.pathname;
      const next = this.codes.some((c) => path === `/${c}` || path.startsWith(`/${c}/`))
        ? path.replace(/^\/[a-z]{2}(?=\/|$)/, `/${code}`)
        : `/${code}${path === '/' ? '' : path}`;

      window.location.href = next + window.location.search + window.location.hash;
    },

    /** Arrow keys move between the options, the way a radio group does. */
    move(step) {
      const options = [...this.$refs.card.querySelectorAll('.lang-option')];
      const at = options.indexOf(document.activeElement);
      const to = (at + step + options.length) % options.length;
      options[to]?.focus();
    },

    /**
     * Keep Tab inside the dialog while it is open. aria-modal tells assistive
     * technology the rest of the page is inert; this makes it true for the
     * keyboard as well, which aria-modal alone does not do.
     */
    trap(event) {
      const focusable = [...this.$refs.card.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')]
        .filter((el) => !el.disabled && el.offsetParent !== null);
      if (focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    },
  };
}
