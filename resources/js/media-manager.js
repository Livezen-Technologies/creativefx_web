/**
 * Centralized Media Manager UI.
 *
 * One MediaBrowser component, two habitats:
 *   - mounted inline on the Media Library page (mode 'page': full management)
 *   - inside a modal via openMediaPicker() (mode 'picker': select & insert)
 *
 * Talks to the JSON endpoints on /admin/media (list, upload-ajax, rename,
 * move, replace, meta, delete). Capabilities come from the server per user.
 */

const BASE = () => window.ADMIN_BASE + '/media';

function csrfPair() {
  const meta = document.querySelector('meta[name="csrf"]');
  return meta ? { name: meta.dataset.name, value: meta.content } : null;
}

function adoptCsrf(data) {
  const meta = document.querySelector('meta[name="csrf"]');
  if (data && data.csrf && meta) {
    meta.content = data.csrf;
    document.querySelectorAll(`input[name="${meta.dataset.name}"]`).forEach((el) => { el.value = data.csrf; });
  }
  return data;
}

async function api(path, { method = 'GET', form = null } = {}) {
  const opts = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
  if (form) {
    const csrf = csrfPair();
    if (csrf) form.append(csrf.name, csrf.value);
    opts.body = form;
  }
  const res = await fetch(BASE() + path, opts);
  const data = adoptCsrf(await res.json().catch(() => ({})));
  if (!res.ok) throw new Error(data.error || `Request failed (${res.status})`);
  return data;
}

export async function uploadToLibrary(file, folder = 'uploads', tags = '') {
  const fd = new FormData();
  fd.append('file', file);
  fd.append('folder', folder);
  if (tags) fd.append('tags', tags);
  return api('/upload-ajax', { method: 'POST', form: fd });
}

const fmtSize = (b) => (b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const ICONS = {
  doc: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 3v5h5M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/></svg>',
  cube: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8ZM3.3 7l8.7 5 8.7-5M12 22V12"/></svg>',
};

export class MediaBrowser {
  /**
   * @param {HTMLElement} mount
   * @param {{mode?:'page'|'picker', accept?:'image'|'video'|'all', multiple?:boolean, onPick?:Function}} opts
   */
  constructor(mount, opts = {}) {
    this.mount = mount;
    this.mode = opts.mode || 'page';
    this.accept = opts.accept || 'all';
    this.multiple = !!opts.multiple;
    this.onPick = opts.onPick || null;
    this.state = { q: '', type: this.accept === 'all' ? '' : this.accept, folder: '', page: 1 };
    this.items = [];
    this.selected = new Map();
    this.caps = { view: false, upload: false, edit: false, delete: false };
    this.renderShell();
    this.load();
  }

  renderShell() {
    const typeChips = ['', 'image', 'video', 'document', '3d']
      .filter((t) => this.accept === 'all' || t === '' || t === this.accept)
      .map((t) => `<button type="button" data-type="${t}" class="mb-chip">${t === '' ? 'All' : t === '3d' ? '3D' : t[0].toUpperCase() + t.slice(1)}</button>`)
      .join('');

    this.mount.classList.add('mb');
    this.mount.innerHTML = `
      <div class="mb-toolbar">
        <div class="mb-toolbar__row">
          <input type="text" class="mb-search" placeholder="Search files, tags, folders…">
          <select class="mb-folder" data-native><option value="">All folders</option></select>
          <div class="mb-chips">${typeChips}</div>
          <button type="button" class="mb-upload-btn" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
            Upload
          </button>
          <input type="file" class="mb-file-input" multiple hidden>
        </div>
        <div class="mb-upload-meta" hidden>
          <input type="text" class="mb-up-folder" placeholder="Folder (default: uploads)">
          <input type="text" class="mb-up-tags" placeholder="Tags, comma separated">
        </div>
      </div>
      <div class="mb-body">
        <div class="mb-gridwrap">
          <div class="mb-drop-hint">Drop files anywhere to upload</div>
          <div class="mb-grid" role="listbox" aria-label="Media files"></div>
          <div class="mb-status"></div>
          <button type="button" class="mb-more" hidden>Load more</button>
        </div>
        <aside class="mb-detail" hidden></aside>
      </div>
      ${this.mode === 'picker' ? '<div class="mb-pickbar" hidden><span class="mb-pickcount"></span><button type="button" class="mb-pick-confirm">Use selected</button></div>' : ''}
    `;

    this.$ = (sel) => this.mount.querySelector(sel);
    this.grid = this.$('.mb-grid');

    let t = null;
    this.$('.mb-search').addEventListener('input', (e) => {
      clearTimeout(t);
      t = setTimeout(() => { this.state.q = e.target.value; this.state.page = 1; this.load(); }, 250);
    });
    this.$('.mb-folder').addEventListener('change', (e) => { this.state.folder = e.target.value; this.state.page = 1; this.load(); });
    this.mount.querySelectorAll('.mb-chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        this.state.type = chip.dataset.type;
        this.state.page = 1;
        this.load();
      });
    });
    this.$('.mb-upload-btn').addEventListener('click', () => this.$('.mb-file-input').click());
    this.$('.mb-file-input').addEventListener('change', (e) => this.uploadFiles([...e.target.files]));
    this.$('.mb-more').addEventListener('click', () => { this.state.page++; this.load(true); });
    if (this.mode === 'picker') {
      this.$('.mb-pick-confirm').addEventListener('click', () => this.confirmPick());
    }

    // Drag & drop uploads over the whole browser.
    this.mount.addEventListener('dragover', (e) => { e.preventDefault(); this.mount.classList.add('mb-dragging'); });
    this.mount.addEventListener('dragleave', (e) => { if (!this.mount.contains(e.relatedTarget)) this.mount.classList.remove('mb-dragging'); });
    this.mount.addEventListener('drop', (e) => {
      e.preventDefault();
      this.mount.classList.remove('mb-dragging');
      this.uploadFiles([...(e.dataTransfer?.files || [])]);
    });
  }

  matchesAccept(file) {
    if (this.accept === 'image') return file.type.startsWith('image/');
    if (this.accept === 'video') return file.type.startsWith('video/');
    return true;
  }

  async uploadFiles(files) {
    if (!this.caps.upload || !files.length) return;
    const folder = this.$('.mb-up-folder')?.value.trim() || this.state.folder || 'uploads';
    const tags = this.$('.mb-up-tags')?.value.trim() || '';
    const status = this.$('.mb-status');
    let done = 0, dupes = 0, failed = 0;
    for (const file of files) {
      if (!this.matchesAccept(file)) { failed++; continue; }
      status.textContent = `Uploading ${file.name} (${done + dupes + failed + 1}/${files.length})…`;
      try {
        const row = await uploadToLibrary(file, folder, tags);
        row.duplicate ? dupes++ : done++;
      } catch (err) {
        console.error(err);
        failed++;
      }
    }
    this.state.page = 1;
    await this.load();
    status.textContent = `${done} uploaded${dupes ? `, ${dupes} duplicate(s) reused instead of re-storing` : ''}${failed ? `, ${failed} failed` : ''}.`;
  }

  async load(append = false) {
    const p = new URLSearchParams({ q: this.state.q, type: this.state.type, folder: this.state.folder, page: this.state.page });
    let data;
    try {
      data = await api('/list?' + p.toString());
    } catch (err) {
      this.$('.mb-status').textContent = err.message;
      return;
    }
    this.caps = data.caps;
    this.$('.mb-upload-btn').hidden = !this.caps.upload;
    this.$('.mb-upload-meta').hidden = !this.caps.upload || this.mode === 'picker';

    const fsel = this.$('.mb-folder');
    const cur = this.state.folder;
    fsel.innerHTML = '<option value="">All folders</option>' + data.folders.map((f) => `<option value="${esc(f)}" ${f === cur ? 'selected' : ''}>${esc(f)}</option>`).join('');

    this.mount.querySelectorAll('.mb-chip').forEach((c) => c.classList.toggle('is-on', c.dataset.type === this.state.type));

    this.items = append ? this.items.concat(data.items) : data.items;
    this.$('.mb-more').hidden = data.page >= data.pages;
    if (!append) this.grid.scrollTop = 0;
    this.renderGrid();
    this.$('.mb-status').textContent = data.total === 0 ? 'No files found.' : `${data.total} file(s)`;
  }

  tileMedia(item) {
    if (item.mime?.startsWith('image/')) {
      return `<img src="${esc(item.thumb || item.webp || item.url)}" alt="${esc(item.alt || '')}" loading="lazy">`;
    }
    if (item.mime?.startsWith('video/')) {
      return `<video src="${esc(item.url)}" preload="metadata" muted playsinline></video><span class="mb-badge">▶ video</span>`;
    }
    const is3d = /\.(glb|gltf)$/i.test(item.name);
    return `<span class="mb-fileicon">${is3d ? ICONS.cube : ICONS.doc}</span><span class="mb-badge">${esc(item.name.split('.').pop())}</span>`;
  }

  renderGrid() {
    this.grid.innerHTML = this.items.map((item, i) => `
      <figure class="mb-tile ${this.selected.has(item.id) ? 'is-selected' : ''}" data-i="${i}" role="option" tabindex="0" aria-selected="${this.selected.has(item.id)}">
        <div class="mb-tile__media">${this.tileMedia(item)}</div>
        <figcaption class="mb-tile__name">${esc(item.original || item.name)}</figcaption>
        <span class="mb-check">✓</span>
      </figure>
    `).join('');

    this.grid.querySelectorAll('.mb-tile').forEach((tile) => {
      const item = this.items[+tile.dataset.i];
      tile.addEventListener('click', () => this.onTileClick(item));
      tile.addEventListener('dblclick', () => { if (this.mode === 'picker') { this.select(item, true); this.confirmPick(); } });
      tile.addEventListener('keydown', (e) => { if (e.key === 'Enter') this.onTileClick(item); });
    });
  }

  onTileClick(item) {
    if (this.mode === 'picker') {
      this.select(item, !this.selected.has(item.id));
    } else {
      this.showDetail(item);
    }
  }

  select(item, on) {
    if (!this.multiple) this.selected.clear();
    if (on) this.selected.set(item.id, item); else this.selected.delete(item.id);
    this.renderGrid();
    const bar = this.$('.mb-pickbar');
    if (bar) {
      bar.hidden = this.selected.size === 0;
      this.$('.mb-pickcount').textContent = `${this.selected.size} selected`;
    }
    if (this.mode === 'picker') this.showDetail(on ? item : null);
  }

  confirmPick() {
    if (this.onPick && this.selected.size) this.onPick([...this.selected.values()]);
  }

  showDetail(item) {
    const pane = this.$('.mb-detail');
    if (!item) { pane.hidden = true; pane.innerHTML = ''; return; }
    const isImage = item.mime?.startsWith('image/');
    const dims = item.width ? `${item.width} × ${item.height}px · ` : '';
    pane.hidden = false;
    pane.innerHTML = `
      <div class="mb-detail__preview">${isImage
        ? `<img src="${esc(item.webp || item.url)}" alt="">`
        : item.mime?.startsWith('video/') ? `<video src="${esc(item.url)}" controls preload="metadata"></video>` : `<span class="mb-fileicon">${ICONS.doc}</span>`}</div>
      <p class="mb-detail__name">${esc(item.original || item.name)}</p>
      <p class="mb-detail__meta">${dims}${fmtSize(item.size)} · ${esc(item.mime || '')}<br>media/${esc(item.folder || '')} · ${esc((item.date || '').slice(0, 10))}</p>
      <div class="mb-detail__row"><input type="text" class="mb-in mb-url" readonly value="${esc(item.url)}"><button type="button" class="mb-btn mb-copy">Copy</button></div>
      ${this.caps.edit ? `
        <label class="mb-lbl">Alt text</label>
        <input type="text" class="mb-in mb-alt" value="${esc(item.alt || '')}" placeholder="Describe the image">
        <label class="mb-lbl">Tags</label>
        <input type="text" class="mb-in mb-tags" value="${esc(item.tags || '')}" placeholder="comma,separated">
        <button type="button" class="mb-btn mb-savemeta">Save details</button>
        <hr class="mb-hr">
        <div class="mb-detail__row"><input type="text" class="mb-in mb-newname" placeholder="new-file-name"><button type="button" class="mb-btn mb-rename">Rename</button></div>
        <div class="mb-detail__row"><input type="text" class="mb-in mb-newfolder" placeholder="target-folder"><button type="button" class="mb-btn mb-move">Move</button></div>
        <p class="mb-note">Renaming or moving changes the URL — existing references keep pointing at the old address.</p>
        <label class="mb-btn mb-replace">Replace file… <input type="file" hidden></label>
        ${isImage ? '<button type="button" class="mb-btn mb-editimg">Crop / resize…</button>' : ''}
      ` : ''}
      ${this.caps.delete ? '<button type="button" class="mb-btn mb-btn--danger mb-delete">Delete file</button>' : ''}
    `;

    pane.querySelector('.mb-copy')?.addEventListener('click', () => {
      navigator.clipboard?.writeText(item.url);
      pane.querySelector('.mb-copy').textContent = 'Copied!';
      setTimeout(() => { const b = pane.querySelector('.mb-copy'); if (b) b.textContent = 'Copy'; }, 1200);
    });

    const refresh = (data) => {
      const idx = this.items.findIndex((x) => x.id === item.id);
      if (idx >= 0 && data.item) this.items[idx] = data.item;
      this.renderGrid();
      this.showDetail(data.item || null);
    };
    const act = async (path, form) => {
      try { refresh(await api(path, { method: 'POST', form })); }
      catch (err) { alert(err.message); }
    };

    pane.querySelector('.mb-savemeta')?.addEventListener('click', () => {
      const fd = new FormData();
      fd.append('alt', pane.querySelector('.mb-alt').value);
      fd.append('tags', pane.querySelector('.mb-tags').value);
      act(`/${item.id}/meta`, fd);
    });
    pane.querySelector('.mb-rename')?.addEventListener('click', () => {
      const name = pane.querySelector('.mb-newname').value.trim();
      if (!name) return;
      const fd = new FormData();
      fd.append('name', name);
      act(`/${item.id}/rename`, fd);
    });
    pane.querySelector('.mb-move')?.addEventListener('click', () => {
      const folder = pane.querySelector('.mb-newfolder').value.trim();
      if (!folder) return;
      const fd = new FormData();
      fd.append('folder', folder);
      act(`/${item.id}/move`, fd);
    });
    pane.querySelector('.mb-replace input')?.addEventListener('change', (e) => {
      const f = e.target.files?.[0];
      if (!f) return;
      const fd = new FormData();
      fd.append('file', f);
      act(`/${item.id}/replace`, fd);
    });
    pane.querySelector('.mb-editimg')?.addEventListener('click', async () => {
      const edited = await openImageEditor(item);
      if (edited) { this.state.page = 1; await this.load(); this.showDetail(edited); }
    });
    pane.querySelector('.mb-delete')?.addEventListener('click', async () => {
      if (!confirm('Delete this file? References to it will break.')) return;
      try {
        await api(`/${item.id}/delete`, { method: 'POST', form: new FormData() });
        this.selected.delete(item.id);
        this.showDetail(null);
        this.state.page = 1;
        this.load();
      } catch (err) { alert(err.message); }
    });
  }
}

/* -------------------------------------------------------------------------- */
/* Picker modal                                                               */
/* -------------------------------------------------------------------------- */
let pickerEl = null;

/**
 * Open the media picker. Resolves with an array of chosen items, or null.
 * @param {{accept?:'image'|'video'|'all', multiple?:boolean}} opts
 */
export function openMediaPicker(opts = {}) {
  return new Promise((resolve) => {
    pickerEl?.remove();
    pickerEl = document.createElement('div');
    pickerEl.className = 'mb-modal';
    pickerEl.innerHTML = `
      <div class="mb-modal__backdrop"></div>
      <div class="mb-modal__panel" role="dialog" aria-modal="true" aria-label="Media library">
        <div class="mb-modal__head">
          <h2>Media library</h2>
          <button type="button" class="mb-modal__close" aria-label="Close">✕</button>
        </div>
        <div class="mb-modal__mount"></div>
      </div>`;
    document.body.appendChild(pickerEl);

    const close = (result) => { pickerEl?.remove(); pickerEl = null; resolve(result); };
    pickerEl.querySelector('.mb-modal__backdrop').addEventListener('click', () => close(null));
    pickerEl.querySelector('.mb-modal__close').addEventListener('click', () => close(null));
    document.addEventListener('keydown', function esc(e) {
      if (e.key === 'Escape') { document.removeEventListener('keydown', esc); close(null); }
    });

    new MediaBrowser(pickerEl.querySelector('.mb-modal__mount'), {
      mode: 'picker',
      accept: opts.accept || 'all',
      multiple: !!opts.multiple,
      onPick: (items) => close(items),
    });
  });
}

/* -------------------------------------------------------------------------- */
/* Image editor: crop + resize on a canvas, saved as a new library file        */
/* -------------------------------------------------------------------------- */
function openImageEditor(item) {
  return new Promise((resolve) => {
    const el = document.createElement('div');
    el.className = 'mb-modal mb-editor';
    el.innerHTML = `
      <div class="mb-modal__backdrop"></div>
      <div class="mb-modal__panel mb-editor__panel" role="dialog" aria-modal="true" aria-label="Edit image">
        <div class="mb-modal__head">
          <h2>Crop &amp; resize</h2>
          <button type="button" class="mb-modal__close" aria-label="Close">✕</button>
        </div>
        <div class="mb-editor__stage"><img class="mb-editor__img" crossorigin="anonymous"><div class="mb-editor__crop" hidden></div></div>
        <div class="mb-editor__bar">
          <div class="mb-editor__aspects">
            <button type="button" data-aspect="">Free</button>
            <button type="button" data-aspect="1">1:1</button>
            <button type="button" data-aspect="1.7778">16:9</button>
            <button type="button" data-aspect="1.3333">4:3</button>
            <button type="button" class="mb-editor__reset">Reset</button>
          </div>
          <label>Max width <input type="range" class="mb-editor__w" min="200" max="1920" step="10" value="1920"><span class="mb-editor__wv">1920px</span></label>
          <button type="button" class="mb-btn mb-editor__apply">Save as new file</button>
        </div>
        <p class="mb-editor__hint">Drag on the image to crop. Drag inside the box to move it; drag its corner to resize.</p>
      </div>`;
    document.body.appendChild(el);

    const img = el.querySelector('.mb-editor__img');
    const cropBox = el.querySelector('.mb-editor__crop');
    const stage = el.querySelector('.mb-editor__stage');
    let crop = null; // {x,y,w,h} in displayed px
    let aspect = null;

    img.src = item.url + (item.url.includes('?') ? '&' : '?') + 'v=edit';

    const close = (result) => { el.remove(); resolve(result); };
    el.querySelector('.mb-modal__backdrop').addEventListener('click', () => close(null));
    el.querySelector('.mb-modal__close').addEventListener('click', () => close(null));

    const drawCrop = () => {
      if (!crop) { cropBox.hidden = true; return; }
      cropBox.hidden = false;
      Object.assign(cropBox.style, { left: crop.x + 'px', top: crop.y + 'px', width: crop.w + 'px', height: crop.h + 'px' });
    };

    const clampCrop = () => {
      const r = { w: img.clientWidth, h: img.clientHeight };
      crop.w = Math.min(crop.w, r.w);
      crop.h = Math.min(crop.h, r.h);
      crop.x = Math.max(0, Math.min(crop.x, r.w - crop.w));
      crop.y = Math.max(0, Math.min(crop.y, r.h - crop.h));
    };

    // Draw / move / resize interactions.
    let dragMode = null, start = null;
    stage.addEventListener('pointerdown', (e) => {
      const rect = img.getBoundingClientRect();
      const x = e.clientX - rect.left, y = e.clientY - rect.top;
      if (x < 0 || y < 0 || x > rect.width || y > rect.height) return;
      e.preventDefault();
      stage.setPointerCapture(e.pointerId);
      const corner = crop && Math.abs(x - (crop.x + crop.w)) < 16 && Math.abs(y - (crop.y + crop.h)) < 16;
      const inside = crop && !corner && x > crop.x && x < crop.x + crop.w && y > crop.y && y < crop.y + crop.h;
      dragMode = corner ? 'resize' : inside ? 'move' : 'draw';
      start = { x, y, crop: crop ? { ...crop } : null };
      if (dragMode === 'draw') { crop = { x, y, w: 0, h: 0 }; }
    });
    stage.addEventListener('pointermove', (e) => {
      if (!dragMode) return;
      const rect = img.getBoundingClientRect();
      const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
      const y = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
      if (dragMode === 'draw') {
        crop = { x: Math.min(start.x, x), y: Math.min(start.y, y), w: Math.abs(x - start.x), h: Math.abs(y - start.y) };
        if (aspect) crop.h = crop.w / aspect;
      } else if (dragMode === 'move') {
        crop = { ...start.crop, x: start.crop.x + (x - start.x), y: start.crop.y + (y - start.y) };
      } else {
        crop = { ...start.crop, w: Math.max(24, start.crop.w + (x - start.x)), h: Math.max(24, start.crop.h + (y - start.y)) };
        if (aspect) crop.h = crop.w / aspect;
      }
      clampCrop();
      drawCrop();
    });
    stage.addEventListener('pointerup', () => {
      dragMode = null;
      if (crop && (crop.w < 12 || crop.h < 12)) { crop = null; drawCrop(); }
    });

    el.querySelectorAll('[data-aspect]').forEach((b) => b.addEventListener('click', () => {
      aspect = b.dataset.aspect ? parseFloat(b.dataset.aspect) : null;
      el.querySelectorAll('[data-aspect]').forEach((x) => x.classList.toggle('is-on', x === b));
      if (crop && aspect) { crop.h = crop.w / aspect; clampCrop(); drawCrop(); }
    }));
    el.querySelector('.mb-editor__reset').addEventListener('click', () => { crop = null; drawCrop(); });

    const wSlider = el.querySelector('.mb-editor__w');
    const wLabel = el.querySelector('.mb-editor__wv');
    wSlider.addEventListener('input', () => { wLabel.textContent = wSlider.value + 'px'; });

    el.querySelector('.mb-editor__apply').addEventListener('click', async () => {
      const scale = img.naturalWidth / img.clientWidth;
      const sx = crop ? crop.x * scale : 0;
      const sy = crop ? crop.y * scale : 0;
      const sw = crop ? crop.w * scale : img.naturalWidth;
      const sh = crop ? crop.h * scale : img.naturalHeight;
      const maxW = parseInt(wSlider.value, 10);
      const outW = Math.round(Math.min(sw, maxW));
      const outH = Math.round(sh * (outW / sw));

      const canvas = document.createElement('canvas');
      canvas.width = outW;
      canvas.height = outH;
      canvas.getContext('2d').drawImage(img, sx, sy, sw, sh, 0, 0, outW, outH);

      canvas.toBlob(async (blob) => {
        if (!blob) { alert('Could not process the image.'); return; }
        const base = (item.original || item.name).replace(/\.[a-z0-9]+$/i, '');
        const file = new File([blob], `${base}-edited.jpg`, { type: 'image/jpeg' });
        try {
          const row = await uploadToLibrary(file, item.folder || 'edited', 'edited');
          close(row);
        } catch (err) { alert(err.message); }
      }, 'image/jpeg', 0.88);
    });
  });
}
