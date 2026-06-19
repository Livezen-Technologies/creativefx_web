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
    form: { name: '', email: '', company: '', country: '', phone: '', quantity: '', message: '', website: '' },
    sending: false,
    sent: false,
    error: '',

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

    // Quick-action chips pre-fill the inquiry so the lead is captured with intent.
    intent(type) {
      const name = this.current ? this.current.name : '';
      const lines = {
        sample: `I would like to request a sample of: ${name}.`,
        catalogue: `Please send me the catalogue including: ${name}.`,
      };
      this.form.message = lines[type] || this.form.message;
      const field = document.querySelector('textarea[x-model="form.message"]');
      if (field) field.focus();
    },

    mailLink() {
      const subject = encodeURIComponent(`Inquiry: ${this.current ? this.current.name : 'Norlanka Showroom'}`);
      const body = encodeURIComponent(this.form.message || '');
      return `mailto:${this.contact.email || ''}?subject=${subject}&body=${body}`;
    },

    waLink() {
      if (!this.contact.whatsapp) return '';
      const text = encodeURIComponent(
        `Hi Norlanka, I'm interested in ${this.current ? this.current.name : 'your collections'}.`,
      );
      return `https://wa.me/${this.contact.whatsapp}?text=${text}`;
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
