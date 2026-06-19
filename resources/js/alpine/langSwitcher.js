/**
 * langSwitcher — header locale switch. Navigates to the same path under a
 * different locale segment (/en/... -> /ja/...). The CMS content + UI strings
 * are then served in that locale by the server (LocaleFilter + i18n).
 */
export default function langSwitcher(config = {}) {
  return {
    open: false,
    current: config.current || 'en',
    locales: config.locales || [],

    label(code) {
      const m = this.locales.find((l) => l.code === code);
      return m ? m.label : code.toUpperCase();
    },

    go(code) {
      if (code === this.current) { this.open = false; return; }
      const parts = window.location.pathname.split('/').filter(Boolean);
      const codes = this.locales.map((l) => l.code);
      if (parts.length && codes.includes(parts[0])) {
        parts[0] = code;
      } else {
        parts.unshift(code);
      }
      window.location.pathname = '/' + parts.join('/');
    },
  };
}
