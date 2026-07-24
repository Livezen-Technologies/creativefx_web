/**
 * Admin bundle: rich-text editing (Quill) and drag-and-drop uploads.
 *
 * Behaviours (all opt-in via data attributes):
 *   [data-richtext]   — a hidden <textarea> + editor mount; Quill edits HTML,
 *                       syncs back on every change. Images can be dropped or
 *                       pasted straight into the editor: they upload to the
 *                       media library and embed by URL.
 *   [data-dropzone]   — a file field: click to browse or drop a file; uploads
 *                       via /admin/media/upload-ajax and writes the returned
 *                       URL into the associated <input>, with live preview.
 */
import Quill from 'quill';
import { MediaBrowser, openMediaPicker, uploadToLibrary } from './media-manager.js';
import '../css/admin.css';

// Media library uploads (CSRF rotation handled inside media-manager.js).
const uploadFile = uploadToLibrary;

// Expose for inline scripts / future integrations.
window.openMediaPicker = openMediaPicker;

/* ------------------------------------------------------------------------ */
/* Rich text editors                                                         */
/* ------------------------------------------------------------------------ */
function initRichtext(root) {
  root.querySelectorAll('[data-richtext]').forEach((wrap) => {
    const textarea = wrap.querySelector('textarea');
    const mount = wrap.querySelector('.rt-editor');
    if (!textarea || !mount || wrap.dataset.ready) return;
    wrap.dataset.ready = '1';

    mount.innerHTML = textarea.value;

    const quill = new Quill(mount, {
      theme: 'snow',
      placeholder: wrap.dataset.placeholder || 'Write here…',
      modules: {
        toolbar: {
          container: [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'link', 'image'],
            [{ align: [] }],
            ['clean'],
          ],
          handlers: {
            // Toolbar image button opens the centralized media picker —
            // choose existing images (multi-select) or upload right there.
            async image() {
              const items = await openMediaPicker({ accept: 'image', multiple: true });
              if (!items) return;
              for (const item of items) {
                const range = quill.getSelection(true) || { index: quill.getLength() };
                quill.insertEmbed(range.index, 'image', item.webp || item.url, 'user');
                quill.setSelection(range.index + 1);
              }
            },
          },
        },
      },
    });

    // root.innerHTML (not getSemanticHTML) — the latter hardens every space
    // into &nbsp;, which breaks word-wrapping on the public page.
    const sync = () => { textarea.value = quill.root.innerHTML; };
    quill.on('text-change', sync);
    sync();

    // Drag & drop / paste images straight into the editor.
    quill.root.addEventListener('drop', (e) => {
      const files = [...(e.dataTransfer?.files || [])].filter((f) => f.type.startsWith('image/'));
      if (!files.length) return;
      e.preventDefault();
      insertImages(quill, files);
    });
    quill.root.addEventListener('paste', (e) => {
      const files = [...(e.clipboardData?.files || [])].filter((f) => f.type.startsWith('image/'));
      if (!files.length) return;
      e.preventDefault();
      insertImages(quill, files);
    });
  });
}

async function insertImages(quill, files) {
  for (const file of files || []) {
    try {
      const { url } = await uploadFile(file, 'news');
      const range = quill.getSelection(true) || { index: quill.getLength() };
      quill.insertEmbed(range.index, 'image', url, 'user');
      quill.setSelection(range.index + 1);
    } catch (err) {
      console.error(err);
      alert('Image upload failed — please try again.');
    }
  }
}

/* ------------------------------------------------------------------------ */
/* Drag & drop file fields                                                   */
/* ------------------------------------------------------------------------ */
function initDropzones(root) {
  root.querySelectorAll('[data-dropzone]').forEach((zone) => {
    if (zone.dataset.ready) return;
    zone.dataset.ready = '1';

    const input = document.getElementById(zone.dataset.dropzone);
    const picker = zone.querySelector('input[type="file"]');
    const status = zone.querySelector('.dz-status');
    const preview = zone.querySelector('.dz-preview') || zone.parentElement?.querySelector('.dz-preview');
    const folder = zone.dataset.folder || 'uploads';

    const setPreview = (url) => {
      if (!preview) return;
      if (url && /\.(png|jpe?g|webp|gif|svg|avif)(\?.*)?$/i.test(url)) {
        preview.innerHTML = `<img src="${url}" alt="" class="h-24 w-auto rounded-lg border border-white/10 object-cover">`;
      } else if (url) {
        preview.innerHTML = `<span class="text-xs text-white/60">${url}</span>`;
      } else {
        preview.innerHTML = '';
      }
    };
    setPreview(input?.value);

    const handle = async (file) => {
      if (!file) return;
      zone.classList.add('dz-busy');
      if (status) status.textContent = 'Uploading ' + file.name + '…';
      try {
        const { url } = await uploadFile(file, folder);
        if (input) {
          input.value = url;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        setPreview(url);
        if (status) status.textContent = 'Uploaded — saved to the media library.';
      } catch (err) {
        console.error(err);
        if (status) status.textContent = 'Upload failed — try again.';
      } finally {
        zone.classList.remove('dz-busy');
      }
    };

    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dz-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dz-over'));
    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      zone.classList.remove('dz-over');
      handle(e.dataTransfer?.files?.[0]);
    });
    picker?.addEventListener('change', () => handle(picker.files?.[0]));
    input?.addEventListener('input', () => setPreview(input.value));

    // "Choose from library" — pick an existing file instead of re-uploading.
    const accept = (picker?.getAttribute('accept') || '').includes('video') ? 'video'
      : (picker?.getAttribute('accept') || '').includes('image') ? 'image' : 'all';
    const lib = document.createElement('button');
    lib.type = 'button';
    lib.className = 'dz-library';
    lib.textContent = 'Choose from library';
    lib.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const items = await openMediaPicker({ accept, multiple: false });
      if (items?.[0] && input) {
        input.value = items[0].url;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        setPreview(items[0].url);
        if (status) status.textContent = 'Selected from the media library.';
      }
    });
    zone.appendChild(lib);
  });
}

/* ------------------------------------------------------------------------ */
/* Gallery fields: JSON array of image paths with add / remove / reorder     */
/* ------------------------------------------------------------------------ */
function initGalleries(root) {
  root.querySelectorAll('[data-gallery]').forEach((wrap) => {
    if (wrap.dataset.ready) return;
    wrap.dataset.ready = '1';

    const textarea = wrap.querySelector('textarea');
    const grid = wrap.querySelector('.gal-grid');
    const status = wrap.querySelector('.gal-status');
    const folder = wrap.dataset.folder || 'uploads';

    let items = [];
    try { items = JSON.parse(textarea.value || '[]'); } catch { items = []; }
    if (!Array.isArray(items)) items = [];

    const sync = () => { textarea.value = JSON.stringify(items); };

    const render = () => {
      sync();
      grid.innerHTML = items.map((url, i) => `
        <div class="gal-item" data-i="${i}">
          <img src="${url.replace(/"/g, '&quot;')}" alt="" loading="lazy">
          <div class="gal-item__bar">
            <button type="button" data-act="left" title="Move left" ${i === 0 ? 'disabled' : ''}>‹</button>
            <button type="button" data-act="right" title="Move right" ${i === items.length - 1 ? 'disabled' : ''}>›</button>
            <button type="button" data-act="remove" title="Remove">✕</button>
          </div>
        </div>`).join('');
      grid.querySelectorAll('.gal-item button').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const i = +btn.closest('.gal-item').dataset.i;
          const act = btn.dataset.act;
          if (act === 'remove') items.splice(i, 1);
          else if (act === 'left' && i > 0) [items[i - 1], items[i]] = [items[i], items[i - 1]];
          else if (act === 'right' && i < items.length - 1) [items[i + 1], items[i]] = [items[i], items[i + 1]];
          render();
        });
      });
    };

    wrap.querySelector('.gal-add-lib').addEventListener('click', async () => {
      const picked = await openMediaPicker({ accept: 'image', multiple: true });
      if (picked) {
        picked.forEach((p) => items.push(p.webp || p.url));
        render();
      }
    });

    const upInput = wrap.querySelector('.gal-add-up input');
    upInput.addEventListener('change', async () => {
      const files = [...(upInput.files || [])];
      for (let i = 0; i < files.length; i++) {
        status.textContent = `Uploading ${i + 1}/${files.length}…`;
        try {
          const row = await uploadFile(files[i], folder);
          items.push(row.url);
        } catch (err) {
          console.error(err);
        }
      }
      status.textContent = '';
      upInput.value = '';
      render();
    });

    render();
  });
}

/* ------------------------------------------------------------------------ */
/* Item-list editors: JSON arrays edited as add/remove/reorder rows          */
/*   [data-list]  — array of strings (one input per row)                     */
/*   [data-pairs] — array of {label, value} objects (two inputs per row)     */
/* ------------------------------------------------------------------------ */
function initItemLists(root) {
  root.querySelectorAll('[data-list], [data-pairs]').forEach((wrap) => {
    if (wrap.dataset.ready) return;
    wrap.dataset.ready = '1';

    const isPairs = wrap.hasAttribute('data-pairs');
    const textarea = wrap.querySelector('textarea');
    const rowsEl = wrap.querySelector('.il-rows');
    const [phA, phB] = (wrap.dataset.pairLabels || 'Label|Value').split('|');

    let items = [];
    try {
      const parsed = JSON.parse(textarea.value || '[]');
      if (Array.isArray(parsed)) items = parsed;
    } catch {
      // Legacy "one per line" content.
      items = textarea.value.split('\n').map((s) => s.trim()).filter(Boolean);
    }
    if (isPairs) items = items.map((it) => (typeof it === 'object' && it !== null ? it : { label: String(it), value: '' }));

    const sync = () => { textarea.value = JSON.stringify(items, null, 0); };

    const render = () => {
      sync();
      rowsEl.innerHTML = '';
      items.forEach((item, i) => {
        const row = document.createElement('div');
        row.className = 'il-row';
        row.innerHTML = `
          ${isPairs
            ? `<input type="text" class="il-in il-a" placeholder="${phA}"><input type="text" class="il-in il-b" placeholder="${phB}">`
            : '<input type="text" class="il-in il-a">'}
          <button type="button" data-act="up" title="Move up" ${i === 0 ? 'disabled' : ''}>↑</button>
          <button type="button" data-act="down" title="Move down" ${i === items.length - 1 ? 'disabled' : ''}>↓</button>
          <button type="button" data-act="remove" title="Remove">✕</button>`;
        const a = row.querySelector('.il-a');
        a.value = isPairs ? (item.label ?? '') : item;
        a.addEventListener('input', () => { isPairs ? (items[i].label = a.value) : (items[i] = a.value); sync(); });
        if (isPairs) {
          const b = row.querySelector('.il-b');
          b.value = item.value ?? '';
          b.addEventListener('input', () => { items[i].value = b.value; sync(); });
        }
        row.querySelectorAll('button').forEach((btn) => btn.addEventListener('click', (e) => {
          e.preventDefault();
          const act = btn.dataset.act;
          if (act === 'remove') items.splice(i, 1);
          else if (act === 'up' && i > 0) [items[i - 1], items[i]] = [items[i], items[i - 1]];
          else if (act === 'down' && i < items.length - 1) [items[i + 1], items[i]] = [items[i], items[i + 1]];
          render();
        }));
        rowsEl.appendChild(row);
      });
    };

    wrap.querySelector('.il-add').addEventListener('click', (e) => {
      e.preventDefault();
      items.push(isPairs ? { label: '', value: '' } : '');
      render();
      rowsEl.querySelector('.il-row:last-child .il-a')?.focus();
    });

    render();
  });
}

/* ------------------------------------------------------------------------ */
/* Media Library page mount                                                  */
/* ------------------------------------------------------------------------ */
function initMediaPage() {
  const mount = document.getElementById('media-browser');
  if (mount) new MediaBrowser(mount, { mode: 'page' });
}

/* ------------------------------------------------------------------------ */
/* Searchable dropdowns                                                      */
/*                                                                           */
/* Progressive enhancement over native <select>: the original element stays  */
/* in the DOM (hidden) and keeps carrying the form value; the component      */
/* renders a trigger + searchable panel and syncs selection back, firing     */
/* `change` so any existing listeners keep working. Opt out per-select with  */
/* the `data-native` attribute.                                              */
/* ------------------------------------------------------------------------ */
function initSelects(root) {
  root.querySelectorAll('select:not([data-native]):not([multiple])').forEach((select) => {
    if (select.dataset.enhanced) return;
    select.dataset.enhanced = '1';

    const wrap = document.createElement('div');
    wrap.className = 'sdrop';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('sdrop__native');
    select.tabIndex = -1;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'sdrop__trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.innerHTML = '<span class="sdrop__label"></span>'
      + '<svg class="sdrop__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>';
    wrap.appendChild(trigger);

    const panel = document.createElement('div');
    panel.className = 'sdrop__panel';
    panel.hidden = true;
    const searchable = select.options.length >= 6;
    panel.innerHTML = (searchable ? '<input type="text" class="sdrop__search" placeholder="Search…" autocomplete="off">' : '')
      + '<ul class="sdrop__list" role="listbox"></ul>';
    wrap.appendChild(panel);

    const label = trigger.querySelector('.sdrop__label');
    const search = panel.querySelector('.sdrop__search');
    const list = panel.querySelector('.sdrop__list');
    let items = [];
    let highlighted = -1;

    const syncLabel = () => {
      const opt = select.options[select.selectedIndex];
      label.textContent = opt ? opt.textContent : '';
      label.classList.toggle('sdrop__label--empty', !opt || opt.value === '');
    };

    const render = (filter = '') => {
      const f = filter.trim().toLowerCase();
      list.innerHTML = '';
      items = [];
      [...select.options].forEach((opt) => {
        if (f && !opt.textContent.toLowerCase().includes(f)) return;
        const li = document.createElement('li');
        li.className = 'sdrop__item' + (opt.index === select.selectedIndex ? ' is-selected' : '');
        li.setAttribute('role', 'option');
        li.textContent = opt.textContent;
        li.addEventListener('click', () => choose(opt.index));
        list.appendChild(li);
        items.push({ li, index: opt.index });
      });
      if (!items.length) {
        list.innerHTML = '<li class="sdrop__empty">No matches</li>';
      }
      highlight(items.findIndex((i) => i.index === select.selectedIndex));
    };

    const highlight = (i) => {
      items.forEach((it) => it.li.classList.remove('is-active'));
      highlighted = i;
      if (i >= 0 && items[i]) {
        items[i].li.classList.add('is-active');
        items[i].li.scrollIntoView({ block: 'nearest' });
      }
    };

    const choose = (index) => {
      select.selectedIndex = index;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncLabel();
      close();
      trigger.focus();
    };

    const open = () => {
      panel.hidden = false;
      wrap.classList.add('is-open');
      render('');
      if (search) { search.value = ''; search.focus(); } else { trigger.focus(); }
    };
    const close = () => {
      panel.hidden = true;
      wrap.classList.remove('is-open');
    };
    const toggle = () => (panel.hidden ? open() : close());

    trigger.addEventListener('click', toggle);
    trigger.addEventListener('keydown', (e) => {
      if (['ArrowDown', 'Enter', ' '].includes(e.key) && panel.hidden) { e.preventDefault(); open(); }
    });
    search?.addEventListener('input', () => render(search.value));
    panel.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); close(); trigger.focus(); }
      else if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(highlighted + 1, items.length - 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(highlighted - 1, 0)); }
      else if (e.key === 'Enter') {
        e.preventDefault();
        if (highlighted >= 0 && items[highlighted]) choose(items[highlighted].index);
      }
    });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(); });

    syncLabel();
    // Keep the trigger in sync if code (or another listener) changes the value.
    select.addEventListener('change', syncLabel);
  });
}

/* ------------------------------------------------------------------------ */
/* Light / dark theme toggle (persisted; dark is the default)                */
/* ------------------------------------------------------------------------ */
function initTheme() {
  document.getElementById('theme-toggle')?.addEventListener('click', () => {
    const dark = document.body.classList.toggle('on-dark');
    localStorage.setItem('admin-theme', dark ? 'dark' : 'light');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initRichtext(document);
  initDropzones(document);
  initGalleries(document);
  initItemLists(document);
  initSelects(document);
  initMediaPage();
});
