import Swiper from 'swiper';
import { Autoplay, EffectFade, A11y } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';

const AUTOPLAY_MS = 6000;

/**
 * The home page hero slideshow.
 *
 * Deliberately not part of initCarousels(): that one is built for strips of
 * cards — peeking neighbours, grab cursor, per-view breakpoints — and a hero is
 * one full-bleed photograph cross-fading into the next. Sharing the function
 * would mean a growing pile of `if (isHero)` inside it.
 *
 * What this adds over a plain Swiper:
 *
 *   - It stops. A carousel that moves on its own and cannot be paused is a
 *     WCAG 2.2.2 failure, so there is a real pause button, and autoplay also
 *     halts on hover and whenever focus enters the panel — otherwise the slide
 *     under someone's cursor changes as they reach for it.
 *   - It never starts when the reader has asked for reduced motion, and it
 *     listens for that preference changing rather than only reading it once.
 *   - It stops while the tab is hidden, so a backgrounded tab is not decoding
 *     images nobody is looking at.
 *
 * One slide means no slideshow at all: the markup omits the controls, and this
 * leaves the single photograph as a still.
 */
export function initHeroSlider() {
  document.querySelectorAll('[data-hero-slider]').forEach((el) => {
    const slides = el.querySelectorAll('.swiper-slide');
    if (slides.length < 2) return;

    const box = el.closest('.hero-box') || el.parentElement;
    const controls = box ? box.querySelector('[data-hero-controls]') : null;
    const dotsHost = controls ? controls.querySelector('[data-hero-dots]') : null;
    const toggle = controls ? controls.querySelector('[data-hero-toggle]') : null;

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    const swiper = new Swiper(el, {
      modules: [Autoplay, EffectFade, A11y],
      effect: 'fade',
      fadeEffect: { crossFade: true },
      speed: 900,
      loop: true,
      allowTouchMove: false, // the panel's copy is selectable text, not a swipe surface
      autoplay: motionQuery.matches
        ? false
        : { delay: AUTOPLAY_MS, disableOnInteraction: false, pauseOnMouseEnter: true },
      a11y: false, // the whole slider is aria-hidden; the page's own H1 is the content
    });

    // --- dots -------------------------------------------------------------
    // Built here rather than in the template because their number is the number
    // of slides, which the template would have to loop a second time to know.
    const dots = [];
    if (dotsHost) {
      slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'hero-dot';
        dot.setAttribute('aria-label', `${dotsHost.dataset.labelSlide || 'Slide'} ${i + 1}`);
        dot.addEventListener('click', () => swiper.slideToLoop(i));
        dotsHost.appendChild(dot);
        dots.push(dot);
      });

      const paint = () => {
        dots.forEach((dot, i) => {
          const active = i === swiper.realIndex;
          dot.classList.toggle('is-active', active);
          dot.setAttribute('aria-current', active ? 'true' : 'false');
        });
      };
      swiper.on('slideChange', paint);
      paint();
    }

    // --- arrows -----------------------------------------------------------
    const prev = controls ? controls.querySelector('[data-hero-prev]') : null;
    const next = controls ? controls.querySelector('[data-hero-next]') : null;
    if (prev) prev.addEventListener('click', () => swiper.slidePrev());
    if (next) next.addEventListener('click', () => swiper.slideNext());

    // --- play / pause -----------------------------------------------------
    // `paused` is our own state, not Swiper's: Swiper also stops autoplay for
    // hover and for a hidden tab, and those must not flip the button's label.
    // Reduced motion starts paused, because that is what it asked for.
    let paused = motionQuery.matches;

    const paintToggle = () => {
      if (!toggle) return;
      toggle.classList.toggle('is-paused', paused);
      const label = paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause;
      if (label) toggle.setAttribute('aria-label', label);
    };

    const setPaused = (value) => {
      paused = value;
      if (paused) swiper.autoplay?.stop();
      else swiper.autoplay?.start();
      paintToggle();
    };

    if (toggle) {
      toggle.addEventListener('click', () => setPaused(!paused));
      paintToggle();
    }

    // Focus entering the panel stops it: a reader tabbing to the search box
    // should not have the picture change underneath them mid-keystroke.
    if (box) {
      box.addEventListener('focusin', () => {
        if (!paused) swiper.autoplay?.stop();
      });
      box.addEventListener('focusout', (e) => {
        if (paused) return;
        if (box.contains(e.relatedTarget)) return;
        swiper.autoplay?.start();
      });
    }

    document.addEventListener('visibilitychange', () => {
      if (paused) return;
      if (document.hidden) swiper.autoplay?.stop();
      else swiper.autoplay?.start();
    });

    // The preference can change while the page is open — a system setting, or
    // the reader turning it on precisely because of this slideshow.
    const onMotionChange = (e) => setPaused(e.matches);
    if (typeof motionQuery.addEventListener === 'function') {
      motionQuery.addEventListener('change', onMotionChange);
    } else if (typeof motionQuery.addListener === 'function') {
      motionQuery.addListener(onMotionChange); // Safari < 14
    }

    if (paused) swiper.autoplay?.stop();
  });
}
