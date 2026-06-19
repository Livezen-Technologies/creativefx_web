import gsap from 'gsap';

/**
 * Kinetic-typography hero. GSAP owns ALL transforms here (no CSS transform on
 * the animated elements — a stylesheet transform gets parsed into GSAP's cache
 * as a fixed offset and breaks yPercent tweens). The container is hidden via
 * opacity until set up, to avoid a flash of un-positioned text.
 *
 *  - [data-kinetic] words: mask-reveal upward, staggered, on load.
 *  - [data-rotator]: a vertical word list that cycles continuously (the last
 *    item duplicates the first for a seamless loop).
 * Respects prefers-reduced-motion.
 */
export function initKineticHero() {
  const hero = document.querySelector('.kinetic-hero');
  if (!hero) return;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const words = gsap.utils.toArray('[data-kinetic] .kw-i');

  try {
    if (words.length && !reduce) {
      gsap.set(words, { yPercent: 110 }); // hidden below their masks
    }
    gsap.set(hero, { autoAlpha: 1 }); // reveal the container (opacity + visibility)

    if (words.length && !reduce) {
      gsap.to(words, { yPercent: 0, duration: 0.9, ease: 'power3.out', stagger: 0.05, delay: 0.15 });
    }

    const wrap = document.querySelector('[data-rotator]');
    if (!wrap || reduce) return;
    const list = wrap.querySelector('.rotator-list');
    const items = list ? list.children.length : 0;
    if (!list || items < 3) return; // need >=2 real words (+1 duplicate)

    const stepPct = 100 / items;
    const tl = gsap.timeline({ repeat: -1, delay: 1.6 });
    for (let k = 1; k < items; k++) {
      tl.to(list, { yPercent: -stepPct * k, duration: 0.5, ease: 'power3.inOut' }, '+=1.4');
    }
    tl.set(list, { yPercent: 0 }); // duplicate == first word → seamless reset
  } catch (e) {
    // Never leave the headline hidden if something goes wrong.
    gsap.set(hero, { autoAlpha: 1 });
    if (words.length) gsap.set(words, { yPercent: 0 });
  }
}
