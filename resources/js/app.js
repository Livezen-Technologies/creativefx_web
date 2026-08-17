// Self-hosted brand fonts (Brand Guide) — K2D (primary/display) + Montserrat
// (Avenir fallback; Avenir is licensed and can't be bundled). Bundled by Vite,
// so no external font CDN request.
import '@fontsource/k2d/latin-400.css';
import '@fontsource/k2d/latin-500.css';
import '@fontsource/k2d/latin-600.css';
import '@fontsource/k2d/latin-700.css';
import '@fontsource/montserrat/latin-400.css';
import '@fontsource/montserrat/latin-500.css';
import '@fontsource/montserrat/latin-600.css';
import '@fontsource/montserrat/latin-700.css';
// K2D and Montserrat carry no Sinhala or Tamil glyphs, so the si/ta locales
// would fall back to whatever the OS happens to have. Noto covers both and
// only downloads when the browser actually needs those scripts.
import '@fontsource/noto-sans-sinhala/sinhala-400.css';
import '@fontsource/noto-sans-sinhala/sinhala-600.css';
import '@fontsource/noto-sans-sinhala/sinhala-700.css';
import '@fontsource/noto-sans-tamil/tamil-400.css';
import '@fontsource/noto-sans-tamil/tamil-600.css';
import '@fontsource/noto-sans-tamil/tamil-700.css';

import '../css/app.css';

import Alpine from 'alpinejs';
import videoExperience from './alpine/videoExperience.js';
import langSwitcher from './alpine/langSwitcher.js';
import siteHeader from './alpine/siteHeader.js';
import showroomScene from './alpine/showroomScene.js';
import worldMap from './alpine/worldMap.js';
import themeToggle from './alpine/themeToggle.js';
import { initScrollStory } from './gsap/scroll.js';
import { initKineticHero } from './gsap/kinetic.js';
import { initCarousels } from './carousels.js';
import { initHeroVideo } from './heroVideo.js';
import { initPreloader } from './preloader.js';
import { initSmoothScroll } from './smooth.js';
import { initFullpage } from './fullpage.js';

// --- Alpine components ---
Alpine.data('videoExperience', videoExperience);
Alpine.data('langSwitcher', langSwitcher);
Alpine.data('siteHeader', siteHeader);
Alpine.data('showroomScene', showroomScene);
Alpine.data('worldMap', worldMap);
Alpine.data('themeToggle', themeToggle);

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

// --- Scroll experience ---
// The home page opts into the full-screen "fullpage" section engine (a single
// #fp wrapper). Every other page keeps Lenis smooth scrolling + GSAP scroll
// storytelling. The two models are mutually exclusive, so we branch on #fp.
document.addEventListener('DOMContentLoaded', () => {
  initPreloader();
  initKineticHero();

  if (document.getElementById('fp')) {
    initFullpage();
  } else {
    initSmoothScroll();
    initScrollStory();
  }

  initCarousels();
  initHeroVideo();
});

// Three.js hero accent is heavy + optional — load it lazily only if requested
// and the device looks capable.
const heroAccent = document.querySelector('[data-three-hero]');
if (heroAccent && window.matchMedia('(min-width: 1024px)').matches) {
  import('./three/hero.js')
    .then(({ initHeroAccent }) => initHeroAccent(heroAccent))
    .catch(() => {/* non-critical: silently skip */});
}

// GLB/GLTF product viewer (lazy — only on product pages with a 3D model).
const viewerEl = document.querySelector('[data-product-viewer]');
if (viewerEl) {
  import('./three/productViewer.js')
    .then(({ initProductViewer }) => initProductViewer(viewerEl))
    .catch(() => {/* WebGL unavailable: the poster image still shows */});
}

// Virtual showroom 3D scene (lazy — only on the scene page).
const showroomEl = document.querySelector('[data-showroom]');
if (showroomEl) {
  import('./three/showroom.js')
    .then(({ initShowroom }) => initShowroom(showroomEl))
    .catch(() => {/* WebGL unavailable: the HTML product list still works */});
}
