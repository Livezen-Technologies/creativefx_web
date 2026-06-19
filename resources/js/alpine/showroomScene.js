/**
 * showroomScene — drives the product panel, material filter, wishlist and
 * inquiry form for a virtual-showroom category page. Product data is read from
 * the #showroom-data JSON the Three.js scene also uses.
 */
export default function showroomScene() {
  return {
    products: [],
    selected: null,
    filter: 'all',
    panelOpen: false,
    form: { name: '', email: '', company: '', message: '', website: '' },
    sending: false,
    sent: false,
    error: '',

    init() {
      try {
        this.products = JSON.parse(document.getElementById('showroom-data').textContent).products || [];
      } catch (e) { this.products = []; }
      window.addEventListener('showroom-select', (e) => this.select(e.detail.id));
    },

    get current() {
      return this.products.find((p) => p.id === this.selected) || null;
    },

    inList(p) {
      return this.filter === 'all' || (p.materials || []).includes(this.filter);
    },

    setFilter(material) {
      this.filter = material;
      const ids = this.products.filter((p) => this.inList(p)).map((p) => p.id);
      window.dispatchEvent(new CustomEvent('showroom-filter', { detail: { ids } }));
    },

    select(id) {
      this.selected = id;
      this.panelOpen = true;
      this.sent = false;
      this.error = '';
    },

    close() {
      this.panelOpen = false;
    },

    async submitInquiry() {
      this.sending = true;
      this.error = '';
      try {
        const res = await fetch('/api/inquiry', {
          method: 'POST',
          headers: { Accept: 'application/json' },
          body: new URLSearchParams({
            ...this.form,
            interest: this.current ? this.current.name : '',
            locale: document.documentElement.lang,
          }),
        });
        const data = await res.json();
        this.sending = false;
        if (res.ok && data.status === 'success') {
          this.sent = true;
        } else {
          this.error = (data.messages && Object.values(data.messages)[0]) || 'Something went wrong.';
        }
      } catch (e) {
        this.sending = false;
        this.error = 'Network error.';
      }
    },
  };
}
