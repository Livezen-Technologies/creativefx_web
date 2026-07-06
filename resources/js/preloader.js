/**
 * Brand loading screen: animated NL logo + progress line, shown once per session
 * on first paint (the inline head script hides it up-front on later navigations
 * so there is no flash). Fades out on window load, with a min display + a safety
 * cap so it never gets stuck. Fully skipped for no-JS and reduced-motion users.
 */
export function initPreloader() {
  const el = document.getElementById('preloader');
  if (!el) return;

  // Already shown this session → remove immediately (head script also hid it).
  try {
    if (sessionStorage.getItem('nl_preloaded')) { el.remove(); return; }
  } catch (e) { /* sessionStorage unavailable — just proceed */ }

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fill = el.querySelector('.pre-bar-fill');
  if (fill && !reduce) requestAnimationFrame(() => { fill.style.width = '100%'; });

  const start = performance.now();
  const minShow = reduce ? 200 : 850;
  let finished = false;

  const dismiss = () => {
    if (finished) return;
    finished = true;
    try { sessionStorage.setItem('nl_preloaded', '1'); } catch (e) { /* ignore */ }
    el.classList.add('is-hidden');
    setTimeout(() => el.remove(), 650);
  };

  const finish = () => {
    const wait = Math.max(0, minShow - (performance.now() - start));
    setTimeout(dismiss, wait);
  };

  if (document.readyState === 'complete') finish();
  else window.addEventListener('load', finish, { once: true });

  // Safety: never let a slow asset trap the screen.
  setTimeout(dismiss, 3200);
}
