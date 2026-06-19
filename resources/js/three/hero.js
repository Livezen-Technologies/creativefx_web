import * as THREE from 'three';

/**
 * initHeroAccent — a subtle, lightweight particle field rendered behind the
 * hero as a brand accent. Intentionally minimal: it is lazy-loaded and skipped
 * on small screens / reduced-motion. This is a placeholder for the richer
 * Three.js work that the Virtual Showroom module will own later.
 */
export function initHeroAccent(container) {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const width = container.clientWidth || window.innerWidth;
  const height = container.clientHeight || window.innerHeight;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(70, width / height, 0.1, 100);
  camera.position.z = 6;

  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
  renderer.setSize(width, height);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  container.appendChild(renderer.domElement);

  const count = 600;
  const positions = new Float32Array(count * 3);
  for (let i = 0; i < count * 3; i++) positions[i] = (Math.random() - 0.5) * 14;

  const geometry = new THREE.BufferGeometry();
  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  const material = new THREE.PointsMaterial({
    color: 0xcf2030,
    size: 0.035,
    transparent: true,
    opacity: 0.55,
  });
  const points = new THREE.Points(geometry, material);
  scene.add(points);

  let raf;
  const animate = () => {
    points.rotation.y += 0.0006;
    points.rotation.x += 0.0002;
    renderer.render(scene, camera);
    raf = requestAnimationFrame(animate);
  };
  animate();

  const onResize = () => {
    const w = container.clientWidth;
    const h = container.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
  };
  window.addEventListener('resize', onResize);

  // Pause when the hero scrolls out of view to save the GPU.
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting && !raf) animate();
      else if (!e.isIntersecting && raf) { cancelAnimationFrame(raf); raf = null; }
    });
  });
  io.observe(container);
}
