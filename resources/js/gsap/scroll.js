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

  /*
   * Reveal-on-scroll, arranged so that it can never hide public information.
   *
   * The obvious implementation is gsap.from({opacity: 0}), which writes the
   * hidden state the moment the tween is created and unwrites it when the
   * trigger fires. That is fine until the trigger does not fire — a
   * ScrollTrigger whose start line is above the element, a layout that shifts
   * after measurement, a browser that never reaches the section — and then a
   * paragraph of an Authority notice is simply invisible, with the page
   * reporting no error at all. On a marketing site that is a missed animation.
   * Here it is a citizen who cannot read a fertilizer issue notice.
   *
   * So the element's resting state is visible, and it is hidden only for the
   * instant between the observer being armed and the tween running. fromTo with
   * immediateRender:false means GSAP writes nothing until the trigger fires;
   * the fallback timer unhides anything the observer has not reached within a
   * few seconds, whatever the reason.
   */
  const reveals = gsap.utils.toArray('[data-gsap="reveal"]');

  reveals.forEach((el) => {
    const onScreen = el.getBoundingClientRect().top < window.innerHeight;

    gsap.fromTo(
      el,
      { opacity: 0, y: 48 },
      {
        opacity: 1,
        y: 0,
        duration: 1,
        ease: 'power3.out',
        immediateRender: false,
        // Anything already on screen at load animates straight away rather
        // than waiting for a start line the reader has already passed.
        ...(onScreen
          ? { delay: 0.1 }
          : { scrollTrigger: { trigger: el, start: 'top 90%', once: true } }),
      },
    );
  });

  // Whatever happened above, nothing stays transparent. Cheap, and it is the
  // difference between a decorative failure and a content failure.
  window.setTimeout(() => {
    reveals.forEach((el) => {
      if (parseFloat(window.getComputedStyle(el).opacity) < 0.99) {
        gsap.set(el, { opacity: 1, y: 0 });
      }
    });
  }, 4000);

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
