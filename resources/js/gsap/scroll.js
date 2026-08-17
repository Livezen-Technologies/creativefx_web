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

    // Counters render "0" in the markup and are counted up by the tween below,
    // so skipping the tween used to leave every statistic reading zero for
    // anyone who asks for reduced motion. Reduced motion means don't animate
    // the number, not don't show it.
    document.querySelectorAll('[data-counter]').forEach((el) => {
      const target = parseFloat(el.dataset.counter || '0');
      const decimals = parseInt(el.dataset.decimals || '0', 10);
      el.textContent = target.toFixed(decimals);
    });

    return;
  }

  gsap.utils.toArray('[data-gsap="reveal"]').forEach((el) => {
    const tween = {
      opacity: 0,
      y: 48,
      duration: 1,
      ease: 'power3.out',
    };

    // Anything already on screen at load (hero copy, short pages, tall
    // viewports) must animate straight away — a ScrollTrigger whose start
    // line sits above it would never fire, leaving the content invisible
    // until the reader scrolls.
    if (el.getBoundingClientRect().top < window.innerHeight) {
      gsap.from(el, { ...tween, delay: 0.1 });
      return;
    }

    gsap.from(el, {
      ...tween,
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

  // Milestone timeline: the accent spine fills as the reader moves through it.
  // CSS leaves the fill at full height, so with reduced motion (which returns
  // above) the spine simply reads as a solid accent rail.
  gsap.utils.toArray('[data-timeline]').forEach((list) => {
    const fill = list.querySelector('[data-timeline-fill]');
    if (! fill) return;

    gsap.fromTo(
      fill,
      { scaleY: 0 },
      {
        scaleY: 1,
        ease: 'none',
        scrollTrigger: { trigger: list, start: 'top 70%', end: 'bottom 75%', scrub: 0.4 },
      },
    );
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
