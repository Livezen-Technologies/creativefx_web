import '../css/app.css';

import Alpine from 'alpinejs';
import videoExperience from './alpine/videoExperience.js';
import langSwitcher from './alpine/langSwitcher.js';
import showroomScene from './alpine/showroomScene.js';
import { initScrollStory } from './gsap/scroll.js';
import { initCarousels } from './carousels.js';

// --- Alpine components ---
Alpine.data('videoExperience', videoExperience);
Alpine.data('langSwitcher', langSwitcher);
Alpine.data('showroomScene', showroomScene);

// Shared wishlist (persisted to localStorage) — used across the showroom.
Alpine.store('wishlist', {
  items: JSON.parse(localStorage.getItem('norlanka_wishlist') || '[]'),
  has(id) { return this.items.includes(id); },
  toggle(id) {
    this.items = this.has(id) ? this.items.filter((x) => x !== id) : [...this.items, id];
    localStorage.setItem('norlanka_wishlist', JSON.stringify(this.items));
  },
  get count() { return this.items.length; },
});

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

// Virtual showroom 3D scene (lazy — only on the scene page).
const showroomEl = document.querySelector('[data-showroom]');
if (showroomEl) {
  import('./three/showroom.js')
    .then(({ initShowroom }) => initShowroom(showroomEl))
    .catch(() => {/* WebGL unavailable: the HTML product list still works */});
}
