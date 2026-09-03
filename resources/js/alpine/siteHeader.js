/**
 * Site header behaviour: sticky shrink, scroll-progress bar and mobile-menu
 * state. Kept tiny and dependency-free.
 */
export default () => ({
  scrolled: false,
  progress: 0,
  mobile: false,
  /**
   * Whether the bar is wide enough for the full navigation.
   *
   * A breakpoint cannot answer this on a trilingual site whose menu is edited
   * in a console. The same eight items are "About Us · Services · Media
   * Centre…" in English and half again as wide in Tamil, and an editor can
   * rename any of them tomorrow — so the width at which the bar stops fitting
   * is not a number anybody can write into a stylesheet. Measuring it is:
   * lay the nav out, ask whether it overflowed, and fall back to the drawer
   * when it did.
   *
   * Starts true so that the desktop nav is the pre-JavaScript state: a reader
   * with no JavaScript gets the full menu rather than a hamburger that cannot
   * open.
   */
  fits: true,

  init() {
    this.onScroll();
    this.measure();
    // Re-measure on resize and on the web fonts arriving, both of which change
    // the answer. Throttled to a frame — this reads layout, and doing it on
    // every resize event is how a resize handler becomes a stutter.
    let queued = false;
    const remeasure = () => {
      if (queued) return;
      queued = true;
      window.requestAnimationFrame(() => { queued = false; this.measure(); });
    };
    window.addEventListener('resize', remeasure, { passive: true });
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(remeasure).catch(() => {});
    }
    // Lock body scroll while the mobile menu is open.
    this.$watch('mobile', (open) => {
      document.documentElement.style.overflow = open ? 'hidden' : '';
    });
    // On the full-screen home experience the window never scrolls, so drive the
    // header's condensed/solid state and progress bar from the active panel.
    window.addEventListener('fp:change', (e) => {
      const { index = 0, count = 1 } = e.detail || {};
      this.scrolled = index > 0;
      this.progress = count > 1 ? (index / (count - 1)) * 100 : 0;
    });
  },

  /**
   * Does the navigation fit beside the logo and the controls?
   *
   * Measured with the nav laid out but invisible (`is-measuring` sets
   * visibility, not display, so it still has a width) to avoid a flash of the
   * full menu before it is hidden again.
   */
  measure() {
    const bar = this.$refs.bar;
    const nav = this.$refs.nav;
    const logo = this.$refs.logo;
    const controls = this.$refs.controls;
    if (! bar || ! nav || ! logo || ! controls) return;

    // Below the CSS breakpoint the drawer is the design, not a fallback.
    if (window.innerWidth < 1024) { this.fits = true; return; }

    // The nav is laid out but invisible for the length of this measurement —
    // see `.header-bar.is-measuring` in app.css. Reading scrollWidth off a
    // display:none element returns 0, which would read as "fits", which would
    // show it again: the header would flicker on every resize.
    bar.classList.add('is-measuring');
    const available = bar.clientWidth - logo.offsetWidth - controls.offsetWidth;
    // Breathing room, not just clearance: a nav whose last item ends exactly
    // where the language switch begins reads as broken even though it
    // technically fits, and the centring grid needs slack on both flanks or it
    // squeezes them until their contents wrap.
    this.fits = nav.scrollWidth + 64 <= available;
    bar.classList.remove('is-measuring');
  },

  onScroll() {
    const y = window.scrollY || window.pageYOffset || 0;
    this.scrolled = y > 30;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    this.progress = max > 0 ? Math.min(100, (y / max) * 100) : 0;
  },
});
