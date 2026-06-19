import '../css/app.css';

import Alpine from 'alpinejs';
import videoExperience from './alpine/videoExperience.js';
import langSwitcher from './alpine/langSwitcher.js';
import { initScrollStory } from './gsap/scroll.js';
import { initCarousels } from './carousels.js';

// --- Alpine components ---
Alpine.data('videoExperience', videoExperience);
Alpine.data('langSwitcher', langSwitcher);

window.Alpine = Alpine;
Alpine.start();

// --- GSAP scroll storytelling (guards prefers-reduced-motion internally) ---
document.addEventListener('DOMContentLoaded', () => {
  initScrollStory();
  initCarousels();
});

// Three.js hero accent is heavy + optional — load it lazily only if requested
// and the device looks capable.
const heroAccent = document.querySelector('[data-three-hero]');
if (heroAccent && window.matchMedia('(min-width: 1024px)').matches) {
  import('./three/hero.js')
    .then(({ initHeroAccent }) => initHeroAccent(heroAccent))
    .catch(() => {/* non-critical: silently skip */});
}
