/**
 * The header's language switch.
 *
 * Clause 3.15 asks for more than a link to the other language: "switching
 * preserves the reader's current page and scroll position rather than
 * returning them to the home page", and the language preference is remembered.
 * All three are this component's job.
 *
 *   The page — the locale segment is replaced in place, and the query string
 *   and fragment come with it, so a filtered directory or an anchored FAQ
 *   answer survives the switch instead of resetting to the top of the section.
 *
 *   The scroll position — stashed against the destination path and restored
 *   once, on arrival. Keyed to the path so that a stale offset from an earlier
 *   switch cannot be applied to a different page.
 *
 *   The preference — written to the same key the welcome page reads, so a
 *   reader who has chosen once is not asked again at the front door.
 */
const STORE_KEY = 'nl_locale';
const SCROLL_KEY = 'nl_lang_scroll';

/**
 * Restore the offset a language switch stashed, if it was for this page.
 *
 * @param {object|null} scroller The smooth-scroll engine, when one is running.
 *   It has to be asked rather than bypassed: Lenis holds its own target
 *   position and animates towards it every frame, so a raw window.scrollTo is
 *   applied and then pulled back to wherever Lenis thinks the page is — which
 *   is the top, on a page that has just loaded. The reader ends up at zero and
 *   the clause's promise is quietly broken with nothing in the console.
 */
export function restoreLangScroll(scroller = null) {
  let stashed;
  try {
    stashed = JSON.parse(sessionStorage.getItem(SCROLL_KEY) || 'null');
    sessionStorage.removeItem(SCROLL_KEY);
  } catch (e) {
    return;
  }
  if (! stashed || stashed.path !== window.location.pathname) return;

  const y = stashed.y || 0;

  // After paint: the layout is not final until fonts and images have settled,
  // and scrolling to an offset measured against a shorter page lands short.
  window.requestAnimationFrame(() => {
    window.requestAnimationFrame(() => {
      if (scroller && typeof scroller.scrollTo === 'function') {
        scroller.scrollTo(y, { immediate: true });
      } else {
        window.scrollTo(0, y);
      }
    });
  });
}

export default function langSwitcher(config = {}) {
  return {
    open: false,
    current: config.current || 'en',
    locales: config.locales || [],

    label(code) {
      const m = this.locales.find((l) => l.code === code);
      return m ? m.label : code.toUpperCase();
    },

    /** The same address under another locale — query and fragment included. */
    href(code) {
      const parts = window.location.pathname.split('/').filter(Boolean);
      const codes = this.locales.map((l) => l.code);
      if (parts.length && codes.includes(parts[0])) {
        parts[0] = code;
      } else {
        parts.unshift(code);
      }
      return '/' + parts.join('/') + window.location.search + window.location.hash;
    },

    go(code) {
      if (code === this.current) { this.open = false; return; }

      const target = this.href(code);
      try {
        localStorage.setItem(STORE_KEY, code);
        sessionStorage.setItem(SCROLL_KEY, JSON.stringify({
          path: target.split('?')[0].split('#')[0],
          y: window.scrollY || 0,
        }));
      } catch (e) {
        // Private browsing, or storage disabled. The switch still works; it
        // just lands at the top and asks again at the welcome page.
      }

      window.location.href = target;
    },
  };
}
