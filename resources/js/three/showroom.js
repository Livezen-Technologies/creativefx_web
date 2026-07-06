import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

/**
 * Procedural virtual-showroom scene. No external 3D assets — each product is a
 * pedestal + a slowly-rotating faceted "garment" + a glowing hotspot ring,
 * positioned from its seeded hotspot coords. Clicking a product dispatches
 * `showroom-select` (the Alpine panel listens). `showroom-filter` toggles which
 * products are visible. The scene reads JSON from #showroom-data.
 */
export function initShowroom(container) {
  let config = { accent: '#cf2030', theme: 'minimal', products: [] };
  try {
    config = JSON.parse(document.getElementById('showroom-data').textContent);
  } catch (e) { /* keep defaults */ }

  const accent = new THREE.Color(config.accent || '#cf2030');
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const width = container.clientWidth;
  const height = container.clientHeight || 480;

  const scene = new THREE.Scene();
  scene.background = new THREE.Color('#08080a');
  scene.fog = new THREE.FogExp2('#08080a', config.theme === 'clouds' ? 0.04 : 0.06);

  const camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 100);
  camera.position.set(0, 2.4, 8.5);

  const renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(width, height);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  container.appendChild(renderer.domElement);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.enablePan = false;
  controls.minDistance = 4.5;
  controls.maxDistance = 14;
  controls.minPolarAngle = Math.PI / 4;
  controls.maxPolarAngle = Math.PI / 2.05;
  controls.target.set(0, 1, 0);
  controls.autoRotate = ! reduce;
  controls.autoRotateSpeed = 0.5;
  controls.addEventListener('start', () => { controls.autoRotate = false; });

  scene.add(new THREE.AmbientLight(0xffffff, 0.35));
  const dir = new THREE.DirectionalLight(0xffffff, 0.8);
  dir.position.set(5, 8, 5);
  scene.add(dir);
  const key = new THREE.PointLight(accent.getHex(), 1.3, 40);
  key.position.set(0, 4.5, 2.5);
  scene.add(key);
  const rim = new THREE.PointLight(accent.getHex(), 0.6, 40);
  rim.position.set(-6, 3, -4);
  scene.add(rim);

  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(50, 50),
    new THREE.MeshStandardMaterial({ color: 0x101014, roughness: 0.55, metalness: 0.25 }),
  );
  floor.rotation.x = -Math.PI / 2;
  scene.add(floor);

  const wall = new THREE.Mesh(
    new THREE.PlaneGeometry(50, 18),
    new THREE.MeshStandardMaterial({ color: 0x0b0b10, roughness: 1 }),
  );
  wall.position.set(0, 9, -9);
  scene.add(wall);

  // Accent particle field.
  const pCount = 350;
  const pPos = new Float32Array(pCount * 3);
  for (let i = 0; i < pCount * 3; i++) pPos[i] = (Math.random() - 0.5) * 26;
  const pGeo = new THREE.BufferGeometry();
  pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
  const particles = new THREE.Points(pGeo, new THREE.PointsMaterial({
    color: accent.getHex(), size: 0.05, transparent: true, opacity: 0.5,
  }));
  scene.add(particles);

  // Shared low-poly "garment" — a soft kids top/dress silhouette (extruded 2D
  // shape) reused for every product, tinted per product. Reads clearly as
  // clothing rather than an abstract solid. A dummy stand-in for real garments.
  const garmentGeo = (() => {
    const s = new THREE.Shape();
    s.moveTo(-0.24, 0.60);              // neck (left)
    s.lineTo(-0.46, 0.66);              // left shoulder
    s.lineTo(-0.74, 0.34);              // left sleeve top
    s.lineTo(-0.58, 0.12);              // left sleeve bottom
    s.lineTo(-0.36, 0.30);              // left underarm
    s.lineTo(-0.46, -0.86);            // left hem (slight flare)
    s.lineTo(0.46, -0.86);             // right hem
    s.lineTo(0.36, 0.30);              // right underarm
    s.lineTo(0.58, 0.12);              // right sleeve bottom
    s.lineTo(0.74, 0.34);              // right sleeve top
    s.lineTo(0.46, 0.66);              // right shoulder
    s.lineTo(0.24, 0.60);              // neck (right)
    s.quadraticCurveTo(0, 0.44, -0.24, 0.60); // neckline
    const geo = new THREE.ExtrudeGeometry(s, {
      depth: 0.14, bevelEnabled: true, bevelThickness: 0.05, bevelSize: 0.05, bevelSegments: 2, steps: 1,
    });
    geo.center();
    return geo;
  })();

  const texLoader = new THREE.TextureLoader();
  const raycaster = new THREE.Raycaster();
  const pointer = new THREE.Vector2();
  const groups = {};
  const interactive = [];

  config.products.forEach((prod, i) => {
    const h = prod.hotspot || { x: (i - 1) * 3, y: 1.2, z: 0 };
    const g = new THREE.Group();
    g.position.set(Number(h.x) || 0, 0, Number(h.z) || 0);

    const pedestal = new THREE.Mesh(
      new THREE.CylinderGeometry(0.6, 0.72, 0.4, 36),
      new THREE.MeshStandardMaterial({ color: 0x16161c, roughness: 0.5, metalness: 0.45 }),
    );
    pedestal.position.y = 0.2;
    g.add(pedestal);

    // Product display: when the product has a photo, show it as a billboard that
    // always faces the viewer (never turns edge-on); otherwise a tinted garment
    // silhouette. Real photos swap in at the same path — no code change.
    let garment;
    if (prod.image) {
      const tex = texLoader.load(prod.image);
      tex.colorSpace = THREE.SRGBColorSpace;
      tex.anisotropy = 4;
      garment = new THREE.Mesh(
        new THREE.PlaneGeometry(1.5, 1.9),
        new THREE.MeshBasicMaterial({ map: tex, transparent: true, side: THREE.DoubleSide }),
      );
      garment.userData.billboard = true;
      garment.userData.baseY = 1.5;
    } else {
      const swatch = (prod.gallery && prod.gallery[0]) || config.accent;
      garment = new THREE.Mesh(
        garmentGeo,
        new THREE.MeshStandardMaterial({
          color: new THREE.Color(swatch).getHex(), roughness: 0.78, metalness: 0.04,
        }),
      );
      garment.scale.setScalar(1.05);
      garment.userData.baseY = Number(h.y) || 1.2;
      garment.rotation.y = (Math.random() - 0.5) * 0.5;
    }
    garment.userData.bob = Math.random() * Math.PI * 2;
    garment.position.y = garment.userData.baseY;
    g.add(garment);

    const ring = new THREE.Mesh(
      new THREE.RingGeometry(0.92, 1.02, 36),
      new THREE.MeshBasicMaterial({ color: accent.getHex(), side: THREE.DoubleSide, transparent: true, opacity: 0.6 }),
    );
    ring.rotation.x = -Math.PI / 2;
    ring.position.y = 0.42;
    g.add(ring);

    pedestal.userData.productId = prod.id;
    garment.userData.productId = prod.id;
    interactive.push(pedestal, garment);
    groups[String(prod.id)] = g;
    scene.add(g);
  });

  const onClick = (ev) => {
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((ev.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((ev.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    const hit = raycaster.intersectObjects(interactive, false)[0];
    if (hit) {
      window.dispatchEvent(new CustomEvent('showroom-select', { detail: { id: hit.object.userData.productId } }));
    }
  };
  renderer.domElement.addEventListener('click', onClick);
  renderer.domElement.style.cursor = 'grab';

  window.addEventListener('showroom-filter', (e) => {
    const ids = (e.detail && e.detail.ids) || null;
    Object.entries(groups).forEach(([id, g]) => {
      g.visible = ! ids || ids.map(String).includes(id);
    });
  });

  const onResize = () => {
    const w = container.clientWidth;
    const hgt = container.clientHeight || height;
    camera.aspect = w / hgt;
    camera.updateProjectionMatrix();
    renderer.setSize(w, hgt);
  };
  window.addEventListener('resize', onResize);

  const clock = new THREE.Clock();
  const animate = () => {
    const t = clock.getElapsedTime();
    Object.values(groups).forEach((g) => {
      const garment = g.children[1];
      if (! garment || ! garment.userData) return;
      const ud = garment.userData;
      const bob = ud.bob || 0;
      if (ud.billboard) {
        // Photo card: rotate on Y only to always face the camera (stays upright,
        // never edge-on) + a gentle float.
        garment.rotation.y = Math.atan2(camera.position.x - g.position.x, camera.position.z - g.position.z);
        if (! reduce) garment.position.y = (ud.baseY || 1.5) + Math.sin(t + bob) * 0.05;
      } else if (! reduce) {
        // Silhouette: gentle float + sway (no full spin so it never turns edge-on).
        garment.position.y = (ud.baseY || 1.2) + Math.sin(t + bob) * 0.06;
        garment.rotation.y = Math.sin(t * 0.5 + bob) * 0.35;
      }
    });
    if (! reduce) particles.rotation.y += 0.0004;
    controls.update();
    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  };
  animate();
}
