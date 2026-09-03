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

  /**
   * True once an IntersectionObserver owns the sticky state, so the scroll
   * handler stops writing it and only keeps the progress bar in step.
   */
  observed: false,

  init() {
    this.watchStickiness();
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
   * Decide the sticky state by watching a 1px marker at the top of the
   * document, not by listening for scroll.
   *
   * A scroll listener has to be told the page moved, and on a phone mid-fling
   * that message is not guaranteed to arrive in time — which is how the bar
   * came to be photographed in its transparent state half way down a page,
   * with the page's own text reading through it. An observer is told by the
   * compositor that the marker left the viewport; there is no event cadence to
   * miss, and it costs nothing per frame.
   *
   * The scroll handler stays for the progress bar, and remains the fallback
   * wherever IntersectionObserver is missing.
   */
  watchStickiness() {
    const sentinel = document.getElementById('header-sentinel');
    if (! sentinel || typeof IntersectionObserver !== 'function') return;

    // The marker is 30px tall and sits at the top of the document, so it is
    // the threshold — no rootMargin. A negative top margin would shrink the
    // root past a 1px marker entirely, leaving it never intersecting and the
    // header sticky from the first frame, which is how the first version of
    // this went out: transparent normal state, and nobody ever saw it.
    new IntersectionObserver(
      ([entry]) => { this.scrolled = ! entry.isIntersecting; },
      { threshold: 0 }
    ).observe(sentinel);

    this.observed = true;
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
    // Only when nothing better is watching: two writers that disagree on a
    // frame make the bar flicker between its two states.
    if (! this.observed) this.scrolled = y > 30;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    this.progress = max > 0 ? Math.min(100, (y / max) * 100) : 0;
  },
});
