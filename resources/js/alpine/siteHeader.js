/**
 * Site header behaviour: sticky shrink, scroll-progress bar, mega-menu and
 * mobile-menu state. Kept tiny and dependency-free.
 */
export default () => ({
  scrolled: false,
  progress: 0,
  mega: false,
  mobile: false,

  init() {
    this.onScroll();
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

  onScroll() {
    const y = window.scrollY || window.pageYOffset || 0;
    this.scrolled = y > 30;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    this.progress = max > 0 ? Math.min(100, (y / max) * 100) : 0;
  },
});
