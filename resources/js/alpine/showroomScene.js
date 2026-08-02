/**
 * showroomScene — drives the product panel, material filter, wishlist and
 * inquiry form for a virtual-showroom category page. Product data + contact
 * details are read from the #showroom-data JSON the Three.js scene also uses.
 */
export default function showroomScene() {
  return {
    products: [],
    contact: { whatsapp: '', email: '' },
    selected: null,
    filter: 'all',
    panelOpen: false,
    activeImage: 0,

    init() {
      try {
        const data = JSON.parse(document.getElementById('showroom-data').textContent);
        this.products = data.products || [];
        this.contact = data.contact || this.contact;
      } catch (e) { this.products = []; }
      window.addEventListener('showroom-select', (e) => this.select(e.detail.id));
    },

    get current() {
      return this.products.find((p) => p.id === this.selected) || null;
    },

    /**
     * Drawer photo set. Falls back to the single billboard shot so a product
     * with no gallery yet still shows its photo rather than an empty frame.
     */
    get images() {
      const p = this.current;
      if (! p) return [];
      const set = (p.images || []).filter(Boolean);
      if (set.length) return set;
      return p.image ? [p.image] : [];
    },

    get shownImage() {
      const set = this.images;
      if (! set.length) return '';
      return set[Math.min(this.activeImage, set.length - 1)];
    },

    inList(p) {
      return this.filter === 'all' || (p.materials || []).includes(this.filter);
    },

    setFilter(material) {
      this.filter = material;
      const ids = this.products.filter((p) => this.inList(p)).map((p) => p.id);
      window.dispatchEvent(new CustomEvent('showroom-filter', { detail: { ids } }));
    },

    /**
     * Hold the page still while the drawer is open.
     *
     * `overflow: hidden` on the body is not enough — iOS Safari scrolls it
     * anyway. Pinning the body with position:fixed at a negative offset does
     * hold, at the cost of having to restore the scroll position on release,
     * since fixing the body otherwise throws the reader back to the top.
     */
    lockScroll() {
      if (this._locked) return;
      this._scrollY = window.scrollY;
      const b = document.body;
      b.style.position = 'fixed';
      b.style.top = `-${this._scrollY}px`;
      b.style.left = '0';
      b.style.right = '0';
      b.style.width = '100%';
      this._locked = true;
    },

    unlockScroll() {
      if (! this._locked) return;
      const b = document.body;
      b.style.position = '';
      b.style.top = '';
      b.style.left = '';
      b.style.right = '';
      b.style.width = '';
      this._locked = false;
      // html has scroll-behavior:smooth, which would glide the page back into
      // place; jump instead so the release is invisible.
      window.scrollTo({ top: this._scrollY || 0, behavior: 'instant' });
    },

    select(id) {
      this.selected = id;
      this.lockScroll();
      this.panelOpen = true;
      this.activeImage = 0;
    },

    close() {
      this.panelOpen = false;
      this.unlockScroll();
    },

    // Leaving the page with the drawer open must not strand a pinned body.
    destroy() {
      this.unlockScroll();
    },

  };
}
