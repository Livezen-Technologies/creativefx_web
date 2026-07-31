import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { createGLTFLoader } from './gltf.js';

/**
 * Interactive GLB/GLTF product viewer (blueprint §8): studio lighting,
 * drag-to-rotate, scroll-to-zoom, auto-rotate (paused on interaction),
 * fullscreen toggle, poster image while loading and reduced-motion support.
 * Reads {model, poster, hint} from the container's [data-viewer-config] JSON.
 */
export function initProductViewer(container) {
  let cfg = {};
  try {
    cfg = JSON.parse(container.querySelector('[data-viewer-config]').textContent);
  } catch (e) { /* no config: nothing to show */ }
  if (! cfg.model) return;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Poster keeps the space designed while the model streams in.
  let poster = null;
  if (cfg.poster) {
    poster = document.createElement('img');
    poster.src = cfg.poster;
    poster.alt = '';
    poster.className = 'absolute inset-0 h-full w-full object-cover opacity-40';
    container.appendChild(poster);
  }

  const width = container.clientWidth;
  const height = container.clientHeight || 480;

  const scene = new THREE.Scene();
  scene.background = new THREE.Color('#08080a');

  const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
  camera.position.set(0, 0.6, 3.2);

  const renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(width, height);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.domElement.classList.add('absolute', 'inset-0');
  container.appendChild(renderer.domElement);

  // Studio lighting: soft ambient dome + key + rim.
  scene.add(new THREE.HemisphereLight(0xffffff, 0x35353d, 1.1));
  const key = new THREE.DirectionalLight(0xffffff, 1.6);
  key.position.set(3, 4, 4);
  scene.add(key);
  const rim = new THREE.DirectionalLight(0xdfe7ff, 0.7);
  rim.position.set(-4, 2, -3);
  scene.add(rim);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.enablePan = false;
  controls.minDistance = 1.2;
  controls.maxDistance = 8;
  controls.autoRotate = ! reduce;
  controls.autoRotateSpeed = 1.2;
  controls.addEventListener('start', () => { controls.autoRotate = false; });

  createGLTFLoader(renderer).load(cfg.model, (gltf) => {
    const obj = gltf.scene;
    const box = new THREE.Box3().setFromObject(obj);
    const size = box.getSize(new THREE.Vector3());
    obj.scale.setScalar(2 / Math.max(size.x, size.y, size.z, 0.001));
    box.setFromObject(obj);
    obj.position.sub(box.getCenter(new THREE.Vector3()));
    scene.add(obj);
    if (poster) poster.remove();
  }, undefined, () => {/* load failed: poster stays */});

  // Fullscreen toggle.
  const fsBtn = document.createElement('button');
  fsBtn.type = 'button';
  fsBtn.setAttribute('aria-label', 'Fullscreen');
  fsBtn.className = 'absolute right-3 top-3 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur transition hover:bg-black/80';
  fsBtn.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>';
  fsBtn.addEventListener('click', () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else container.requestFullscreen?.();
  });
  container.appendChild(fsBtn);

  const onResize = () => {
    const w = container.clientWidth;
    const h = container.clientHeight || height;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
  };
  new ResizeObserver(onResize).observe(container);
  document.addEventListener('fullscreenchange', onResize);

  const animate = () => {
    controls.update();
    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  };
  animate();
}
