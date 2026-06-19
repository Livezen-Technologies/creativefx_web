import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const prefersReducedMotion = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Wire scroll-driven storytelling. Markup hooks:
 *   [data-gsap="reveal"]   — fade/slide up on enter
 *   [data-gsap="parallax"] — gentle parallax on the element's [data-speed]
 *   [data-counter]         — count up to data-counter when scrolled into view
 *   [data-gsap="hero-out"] — dim/scale the hero as it scrolls away
 */
export function initScrollStory() {
  if (prefersReducedMotion()) {
    // Make sure nothing is left hidden when animations are disabled.
    document.querySelectorAll('[data-gsap="reveal"]').forEach((el) => {
      el.style.opacity = 1;
    });
    return;
  }

  gsap.utils.toArray('[data-gsap="reveal"]').forEach((el) => {
    gsap.from(el, {
      opacity: 0,
      y: 48,
      duration: 1,
      ease: 'power3.out',
      scrollTrigger: { trigger: el, start: 'top 82%', once: true },
    });
  });

  gsap.utils.toArray('[data-gsap="parallax"]').forEach((el) => {
    const speed = parseFloat(el.dataset.speed || '0.2');
    gsap.to(el, {
      yPercent: -speed * 100,
      ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true },
    });
  });

  gsap.utils.toArray('[data-counter]').forEach((el) => {
    const target = parseFloat(el.dataset.counter || '0');
    const decimals = parseInt(el.dataset.decimals || '0', 10);
    const obj = { v: 0 };
    gsap.to(obj, {
      v: target,
      duration: 2,
      ease: 'power1.out',
      scrollTrigger: { trigger: el, start: 'top 85%', once: true },
      onUpdate: () => { el.textContent = obj.v.toFixed(decimals); },
    });
  });

  const hero = document.querySelector('[data-gsap="hero-out"]');
  if (hero) {
    gsap.to(hero, {
      opacity: 0.25,
      scale: 1.08,
      ease: 'none',
      scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
    });
  }
}
