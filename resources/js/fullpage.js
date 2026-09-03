import gsap from 'gsap';

/**
 * Cinematic full-screen section engine ("fullpage").
 *
 * Progressive enhancement: without JS the page is an ordinary vertical scroll.
 * When this runs it takes over and presents one 100vh panel at a time with a
 * slide + fade transition. One wheel notch / swipe / arrow key = one panel.
 *
 * Panels taller than the viewport (common on phones) scroll internally and only
 * advance to the next panel once their top / bottom edge is reached, so no
 * content is ever trapped. Works with wheel, trackpad, touch and keyboard, and
 * degrades to instant transitions for prefers-reduced-motion.
 *
 * Markup: a single `#fp` wrapper whose direct-child <section>s are the panels;
 * the site <footer> is pulled in as the final panel.
 */
export function initFullpage() {
  const root = document.getElementById('fp');
  if (!root) return null;

  const html = document.documentElement;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  try {
    // Pull the global footer in as the final panel so it participates in snapping.
    const footer = document.querySelector('body > footer');
    if (footer) {
      // The footer's brand may be a wordmark or a logo image, so fall back
      // through both before resorting to a generic label — a hard-coded brand
      // name here silently outlives a rebrand.
      footer.dataset.fpTitle = (
        footer.querySelector('.font-display')?.textContent
        || footer.querySelector('img[alt]')?.alt
        || 'Home'
      ).trim();
      root.appendChild(footer);
    }

    const panels = [...root.children].filter((el) => el.nodeType === 1);
    if (panels.length < 2) return null; // nothing to snap
    panels.forEach((p) => p.classList.add('fp-panel'));

    let H = window.innerHeight;
    let index = 0;
    let animating = false;

    const clamp = (i) => Math.max(0, Math.min(panels.length - 1, i));
    const atTop = (p) => p.scrollTop <= 1;
    const atBottom = (p) => p.scrollTop + p.clientHeight >= p.scrollHeight - 1;

    const measure = () => {
      H = window.innerHeight;
      root.style.setProperty('--fp-h', `${H}px`);
      gsap.set(root, { y: -index * H });
    };

    // --- Counters (were driven by ScrollTrigger; now fire on panel activation) --
    const fireCounters = (panel) => {
      panel.querySelectorAll('[data-counter]').forEach((el) => {
        if (el.dataset.counted) return;
        el.dataset.counted = '1';
        const target = parseFloat(el.dataset.counter || '0');
        const dec = parseInt(el.dataset.decimals || '0', 10);
        if (reduce) { el.textContent = target.toFixed(dec); return; }
        const o = { v: 0 };
        gsap.to(o, {
          v: target, duration: 1.6, ease: 'power1.out',
          onUpdate: () => { el.textContent = o.v.toFixed(dec); },
        });
      });
    };

    // --- Content entrance (slide + fade), direction-aware -----------------------
    /**
     * Parallax between panels.
     *
     * The whole #fp column translates as one block, so every layer inside it
     * moves at exactly the same rate and the change reads as a slide. Depth
     * comes from moving the imagery against that: the incoming panel's media
     * starts displaced in the direction of travel and settles to rest, while
     * the outgoing panel's drifts the other way. The copy is left alone, so it
     * stays readable while the picture behind it moves.
     *
     * Strength is per-element via data-parallax="<percent>", because a
     * full-bleed photograph can take far more movement than a framed one
     * before its edges show.
     */
    const parallax = (panel, dir, entering) => {
      if (reduce || ! panel) return;
      panel.querySelectorAll('[data-parallax]').forEach((el) => {
        // The two effects must not share an element. animateIn fades a reveal
        // target in from autoAlpha 0 and this kills tweens before starting its
        // own, so putting both on one node left it permanently invisible —
        // four sections shipped with an empty half and no error anywhere.
        // Skipping is the safe failure: a missing shift rather than missing
        // content. Put data-parallax on the picture inside the reveal.
        if (el.dataset.gsap === 'reveal') return;
        const depth = parseFloat(el.dataset.parallax) || 8;
        gsap.killTweensOf(el);
        if (entering) {
          gsap.fromTo(el, { yPercent: depth * dir },
            { yPercent: 0, duration: 1.1, ease: 'power3.out', overwrite: true });
        } else {
          gsap.to(el, { yPercent: -depth * dir * 0.6, duration: 0.9, ease: 'power3.inOut', overwrite: true });
        }
      });
    };

    const animateIn = (panel, dir) => {
      fireCounters(panel);
      if (reduce) return;
      const items = panel.querySelectorAll('[data-gsap="reveal"]');
      const targets = items.length ? items : [panel.querySelector('.container-x') || panel];
      gsap.killTweensOf(targets);
      gsap.fromTo(
        targets,
        { autoAlpha: 0, y: 42 * dir },
        {
          autoAlpha: 1, y: 0, duration: 0.7, ease: 'power3.out',
          stagger: 0.08, delay: 0.1, overwrite: true,
        }
      );
    };

    // --- Navigation -------------------------------------------------------------
    const nav = document.createElement('nav');
    nav.className = 'fp-nav';
    nav.setAttribute('aria-label', 'Section navigation');
    panels.forEach((p, i) => {
      const label =
        (p.dataset.fpTitle || p.querySelector('h1, h2, h4')?.textContent || `Section ${i + 1}`)
          .trim().replace(/\s+/g, ' ').slice(0, 34);
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'fp-dot';
      b.setAttribute('aria-label', label);
      b.innerHTML = '<span class="fp-dot__hit"></span><span class="fp-dot__label"></span>';
      b.querySelector('.fp-dot__label').textContent = label;
      b.addEventListener('click', () => goTo(i));
      nav.appendChild(b);
    });
    document.body.appendChild(nav);
    const dots = [...nav.children];
    const updateNav = () => {
      dots.forEach((d, i) => {
        const active = i === index;
        d.classList.toggle('is-active', active);
        d.setAttribute('aria-current', active ? 'true' : 'false');
      });
      // Let the header react (solid bg + progress) since the window never scrolls.
      window.dispatchEvent(new CustomEvent('fp:change', {
        detail: { index, count: panels.length },
      }));
    };

    // --- Transition -------------------------------------------------------------
    function goTo(i) {
      i = clamp(i);
      if (i === index || animating) return;
      const dir = i > index ? 1 : -1;
      const prev = index;
      index = i;
      animating = true;
      updateNav();
      html.dataset.fpIndex = String(i);
      gsap.to(root, {
        y: -i * H,
        duration: reduce ? 0.001 : 0.9,
        ease: 'power3.inOut',
        onComplete: () => {
          animating = false;
          panels[prev].scrollTop = 0; // reset the panel we left
        },
      });
      animateIn(panels[i], dir);
      parallax(panels[prev], dir, false);
      parallax(panels[i], dir, true);
    }

    // Advance, but let a tall panel finish its own inner scroll first.
    function step(dir) {
      const p = panels[index];
      if ((dir > 0 && !atBottom(p)) || (dir < 0 && !atTop(p))) {
        p.scrollBy({ top: dir * H * 0.85, behavior: reduce ? 'auto' : 'smooth' });
        return;
      }
      goTo(index + dir);
    }

    // --- Wheel / trackpad -------------------------------------------------------
    let wheelLock = false;
    const onWheel = (e) => {
      const p = panels[index];
      const down = e.deltaY > 0;
      if ((down && !atBottom(p)) || (!down && !atTop(p))) return; // native inner scroll
      e.preventDefault();
      if (animating || wheelLock || Math.abs(e.deltaY) < 6) return;
      wheelLock = true;
      setTimeout(() => { wheelLock = false; }, reduce ? 80 : 820);
      goTo(index + (down ? 1 : -1));
    };
    root.addEventListener('wheel', onWheel, { passive: false });

    // --- Touch ------------------------------------------------------------------
    let tY = 0;
    const onTouchStart = (e) => { tY = e.touches[0].clientY; };
    const onTouchMove = (e) => {
      const p = panels[index];
      const down = tY - e.touches[0].clientY > 0; // swipe up => go down
      if ((down && !atBottom(p)) || (!down && !atTop(p))) return; // native inner scroll
      e.preventDefault(); // at boundary: suppress rubber-band, act on touchend
    };
    const onTouchEnd = (e) => {
      const endY = (e.changedTouches[0] || {}).clientY ?? tY;
      const dy = tY - endY;
      if (Math.abs(dy) < 48 || animating) return;
      const p = panels[index];
      const down = dy > 0;
      if ((down && atBottom(p)) || (!down && atTop(p))) goTo(index + (down ? 1 : -1));
    };
    root.addEventListener('touchstart', onTouchStart, { passive: true });
    root.addEventListener('touchmove', onTouchMove, { passive: false });
    root.addEventListener('touchend', onTouchEnd, { passive: true });

    // --- Keyboard ---------------------------------------------------------------
    const onKey = (e) => {
      const t = e.target;
      if (t && t.closest && t.closest('input, textarea, select, [contenteditable="true"]')) return;
      let handled = true;
      switch (e.key) {
        case 'ArrowDown': case 'PageDown': step(1); break;
        case 'ArrowUp': case 'PageUp': step(-1); break;
        case ' ': step(e.shiftKey ? -1 : 1); break;
        case 'Home': goTo(0); break;
        case 'End': goTo(panels.length - 1); break;
        default: handled = false;
      }
      if (handled) e.preventDefault();
    };
    window.addEventListener('keydown', onKey);

    // --- Resize (debounced; re-measures to the live viewport height) ------------
    let rz;
    const onResize = () => {
      clearTimeout(rz);
      rz = setTimeout(measure, 150);
    };
    window.addEventListener('resize', onResize, { passive: true });
    window.addEventListener('orientationchange', () => setTimeout(measure, 250));

    // --- Boot -------------------------------------------------------------------
    html.classList.add('fp-active');
    html.dataset.fpIndex = '0';
    measure();
    updateNav();
    animateIn(panels[0], 1);
    root.querySelector('[data-fp-next]')?.addEventListener('click', () => goTo(index + 1));

    return { goTo };
  } catch (err) {
    // On any failure, fall back to a normal scrolling page.
    html.classList.remove('fp-active');
    return null;
  }
}
