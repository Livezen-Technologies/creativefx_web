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
  },

  onScroll() {
    const y = window.scrollY || window.pageYOffset || 0;
    this.scrolled = y > 30;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    this.progress = max > 0 ? Math.min(100, (y / max) * 100) : 0;
  },
});
