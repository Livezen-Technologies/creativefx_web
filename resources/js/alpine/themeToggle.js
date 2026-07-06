/**
 * Dark-mode toggle. The chosen theme is applied to <html> as the `dark` class
 * and persisted to localStorage; the head script re-applies it before first
 * paint so there is no flash. All theming flows from CSS custom properties, so
 * flipping the class re-themes the whole site.
 */
export default function themeToggle() {
  return {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
      this.dark = !this.dark;
      document.documentElement.classList.toggle('dark', this.dark);
      try {
        localStorage.setItem('nl_theme', this.dark ? 'dark' : 'light');
      } catch (e) { /* storage unavailable — session-only toggle */ }
    },
  };
}
