// Self-hosted brand fonts — Playfair Display (display) + Poppins (body)
// Bundled by Vite, so no external font CDN request.
// Trilingual type. Noto Sans carries Latin; the Sinhala and Tamil companions
// carry their own scripts and are named after it in the font stack, so a page
// in Sinhala renders in Sinhala without the template knowing which language it
// is in. All three are self-hosted — Clause 3.15 asks for local web fonts, and
// a government site should not need fonts.googleapis.com to be reachable to be
// legible.
import '@fontsource/noto-sans/latin-400.css';
import '@fontsource/noto-sans/latin-500.css';
import '@fontsource/noto-sans/latin-600.css';
import '@fontsource/noto-sans/latin-700.css';
import '@fontsource/noto-sans-sinhala/sinhala-400.css';
import '@fontsource/noto-sans-sinhala/sinhala-500.css';
import '@fontsource/noto-sans-sinhala/sinhala-700.css';
import '@fontsource/noto-sans-tamil/tamil-400.css';
import '@fontsource/noto-sans-tamil/tamil-500.css';
import '@fontsource/noto-sans-tamil/tamil-700.css';

import '../css/app.css';

import Alpine from 'alpinejs';
import videoExperience from './alpine/videoExperience.js';
import langSwitcher, { restoreLangScroll } from './alpine/langSwitcher.js';
import siteHeader from './alpine/siteHeader.js';
import themeToggle from './alpine/themeToggle.js';
import assistant from './alpine/assistant.js';
import { initScrollStory } from './gsap/scroll.js';
import { initKineticHero } from './gsap/kinetic.js';
import { initCarousels } from './carousels.js';
import { initHeroSlider } from './heroSlider.js';
import { initHeroVideo } from './heroVideo.js';
import { initPreloader } from './preloader.js';
import { initTracking } from './track.js';
import { initRecaptcha } from './recaptcha.js';
import { initSmoothScroll } from './smooth.js';
import { initFullpage } from './fullpage.js';

// --- Alpine components ---
Alpine.data('videoExperience', videoExperience);
Alpine.data('langSwitcher', langSwitcher);
Alpine.data('siteHeader', siteHeader);
Alpine.data('themeToggle', themeToggle);
Alpine.data('assistant', assistant);

window.Alpine = Alpine;
Alpine.start();

// --- Scroll experience ---
// The home page opts into the full-screen "fullpage" section engine (a single
// #fp wrapper). Every other page keeps Lenis smooth scrolling + GSAP scroll
// storytelling. The two models are mutually exclusive, so we branch on #fp.
document.addEventListener('DOMContentLoaded', () => {
  initPreloader();
  // Before anything that scrolls: a language switch stashed the reader's
  // position, and it applies whichever scroll model this page uses.
  restoreLangScroll();
  initTracking();
  initRecaptcha();
  initKineticHero();

  if (document.getElementById('fp')) {
    initFullpage();
  } else {
    initSmoothScroll();
    initScrollStory();
  }

  initCarousels();
  initHeroSlider();
  initHeroVideo();
});

