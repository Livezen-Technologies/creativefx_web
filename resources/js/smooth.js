import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

/**
 * Lenis smooth scrolling, driven by GSAP's ticker and synced to ScrollTrigger so
 * scroll-reveals stay in step. Disabled entirely for prefers-reduced-motion and
 * on touch devices (native momentum scroll feels better there).
 */
let instance = null;

/**
 * The live Lenis instance, or null when smooth scrolling is off (reduced
 * motion, or a touch device where native momentum feels better).
 *
 * Anything that needs to move the page has to ask Lenis to do it. Lenis keeps
 * its own target position and animates towards it every frame, so a raw
 * window.scrollTo lands and is then pulled straight back — which is exactly how
 * the language switch lost the reader's scroll position.
 */
export function getLenis() {
  return instance;
}

export function initSmoothScroll() {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const coarse = window.matchMedia('(pointer: coarse)').matches;
  if (reduce || coarse) return null;

  gsap.registerPlugin(ScrollTrigger);

  instance = new Lenis({
    duration: 1.05,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
  });

  instance.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => instance.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);

  return instance;
}
