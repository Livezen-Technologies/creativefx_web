const STORE_KEY = 'tshda_assistant';
const MAX_TURNS = 24;

/**
 * The help assistant panel.
 *
 * It asks this site's own endpoint and renders what comes back. There is no
 * third-party widget, no socket and no vendor script; a question typed here
 * reaches the Authority's server and nowhere else.
 *
 * The state that survives a page load is the open flag and the thread, in
 * sessionStorage — not localStorage. Somebody asking about their own subsidy
 * application should not find the conversation still on the screen next week,
 * or on a shared machine in a regional office. Closing the tab ends it.
 */
export default function assistant(config = {}) {
  return {
    open: false,
    busy: false,
    draft: '',
    messages: [],
    labels: config.labels || { open: 'Need help?', close: 'Close' },
    strings: config.strings || {},
    endpoint: config.endpoint || '',

    init() {
      try {
        const saved = JSON.parse(sessionStorage.getItem(STORE_KEY) || '{}');
        if (Array.isArray(saved.messages)) this.messages = saved.messages.slice(-MAX_TURNS);
        this.open = saved.open === true;
      } catch (e) {
        // A private window, or storage the browser refuses. Start fresh — the
        // panel works perfectly well with no memory of the last page.
      }
      if (this.open) this.$nextTick(() => this.scroll());
    },

    persist() {
      try {
        sessionStorage.setItem(STORE_KEY, JSON.stringify({
          open: this.open,
          messages: this.messages.slice(-MAX_TURNS),
        }));
      } catch (e) { /* nothing to do, and nothing worth breaking over */ }
    },

    toggle() {
      this.open ? this.close() : this.show();
    },

    /**
     * Escape belongs to whichever dialog is on top. This panel and the language
     * chooser both listen on the window, so without this one keypress closes
     * both and the focus each returns fights the other.
     */
    escape() {
      if (document.documentElement.dataset.modalOpen) return;
      this.close();
    },

    show() {
      this.open = true;
      this.persist();
      // Focus the input, not the panel: someone who opened a help box wants to
      // type. The scroll comes after, once the panel has been laid out.
      this.$nextTick(() => {
        this.$refs.input?.focus();
        this.scroll();
      });
    },

    close() {
      if (!this.open) return;
      this.open = false;
      this.persist();
      // Focus goes back where it came from. Without this a keyboard user who
      // closes the panel is returned to the top of the document.
      this.$nextTick(() => this.$refs.launcher?.focus());
    },

    async send() {
      const question = this.draft.trim();
      if (question === '' || this.busy) return;

      this.messages.push({ from: 'you', text: question });
      this.draft = '';
      this.busy = true;
      // A placeholder turn, replaced in place when the answer lands, so the
      // thread does not jump as a "thinking" line is removed and a reply added.
      const pending = this.messages.push({ from: 'bot', text: this.strings.thinking || '…' }) - 1;
      this.scroll();

      try {
        const url = `${this.endpoint}?q=${encodeURIComponent(question)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        this.messages[pending] = this.reply(data, question);
      } catch (e) {
        this.messages[pending] = { from: 'bot', text: this.strings.error || 'Something went wrong.' };
      } finally {
        this.busy = false;
        this.persist();
        this.scroll();
        this.$nextTick(() => this.$refs.input?.focus());
      }
    },

    /** Turn the endpoint's answer into one bubble and its list of links. */
    reply(data) {
      const links = (data.results || []).map((r) => ({ title: r.title, url: r.url }));

      // An answer, with the page it came from first: the bubble is a summary
      // and somebody who wants the detail should not have to search for it.
      if (data.answer) {
        if (data.source) links.unshift({ title: this.strings.source, url: data.source });
        return {
          from: 'bot',
          text: data.answer,
          linksLabel: this.strings.related,
          links: links.slice(0, 5),
        };
      }

      // No single answer, but pages that match. The bubble carries the label,
      // so the list does not repeat it.
      if (links.length) {
        if (data.searchUrl) links.push({ title: this.strings.searchAll, url: data.searchUrl });
        return {
          from: 'bot',
          text: this.strings.related,
          linksLabel: '',
          links: links.slice(0, 5),
        };
      }

      // Nothing found. Say so plainly and offer the people, rather than
      // inventing an answer or leaving a dead end.
      return {
        from: 'bot',
        text: `${this.strings.none} ${this.strings.noneHelp}`,
        linksLabel: '',
        links: [
          data.searchUrl ? { title: this.strings.searchAll, url: data.searchUrl } : null,
          this.strings.contact ? { title: this.strings.contactLabel, url: this.strings.contact } : null,
        ].filter(Boolean),
      };
    },

    scroll() {
      this.$nextTick(() => {
        const thread = this.$refs.thread;
        if (thread) thread.scrollTop = thread.scrollHeight;
      });
    },
  };
}
