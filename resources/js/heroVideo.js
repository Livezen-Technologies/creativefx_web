/**
 * Bulletproof background-video autoplay.
 *
 * Browsers block autoplay in several situations (data-saver, low power, tab
 * restored from bfcache, preload deferral). A muted/inline <video autoplay loop>
 * is allowed to play, but the play() call can still be deferred or rejected — so
 * we actively (re)start it: on load, on canplay, when the tab becomes visible,
 * and on the first user gesture as a last resort. We also restart on `ended`
 * so it loops continuously even if the native loop hiccups.
 *
 * All of that is switched off for prefers-reduced-motion. Everything else on
 * this site honours that setting — the scroll reveals, the panel engine, the
 * counters — and a full-screen film running behind the headline is the single
 * largest piece of motion on the page, so overriding the browser's own autoplay
 * deferral there is precisely the wrong thing to do. Those readers get the
 * poster frame, and the file is not downloaded at all. The corner control still
 * works, so anyone who does want the film can start it.
 */
const prefersReducedMotion = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function initHeroVideo() {
  // The home hero plus any CMS page hero that has a background film.
  document.querySelectorAll('#hero video, [data-hero-video]').forEach(setupHeroVideo);
}

function setupHeroVideo(v) {
  if (!v || v.dataset.heroReady) return;
  v.dataset.heroReady = '1';

  if (prefersReducedMotion()) {
    v.autoplay = false;
    v.removeAttribute('autoplay');
    // Nothing is going to play, so nothing needs downloading: several megabytes
    // saved for the reader least likely to want them.
    v.preload = 'none';
    v.pause();
    return;
  }

  // Autoplay requires muted + inline.
  v.muted = true;
  v.defaultMuted = true;
  v.playsInline = true;
  v.setAttribute('muted', '');
  v.setAttribute('playsinline', '');
  v.loop = true;

  let settled = false;
  const tryPlay = () => {
    const p = v.play();
    if (p && typeof p.then === 'function') {
      p.then(() => { settled = true; }).catch(() => { /* will retry on gesture */ });
    }
  };

  // Kick it off as soon as there is data, and immediately.
  tryPlay();
  ['loadeddata', 'canplay', 'canplaythrough'].forEach((e) =>
    v.addEventListener(e, tryPlay)
  );

  // Loop guard.
  v.addEventListener('ended', () => {
    v.currentTime = 0;
    tryPlay();
  });

  // Resume when returning to the tab (bfcache / background tabs pause video).
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && v.paused && !v.dataset.userPaused) tryPlay();
  });
  window.addEventListener('pageshow', () => {
    if (v.paused && !v.dataset.userPaused) tryPlay();
  });

  // Last resort: unlock on the first user interaction.
  const onGesture = () => {
    if (!settled && !v.dataset.userPaused) tryPlay();
  };
  ['pointerdown', 'touchstart', 'keydown', 'scroll'].forEach((e) =>
    window.addEventListener(e, onGesture, { once: true, passive: true })
  );
}
