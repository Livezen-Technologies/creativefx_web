/**
 * A video player with the site's own controls rather than a third party's.
 *
 * The film used to be a YouTube embed, which brought YouTube's chrome with it:
 * a channel header, an end-card grid of other people's videos, a red progress
 * bar and a logo. On a hotel's own page that reads as somebody else's product
 * playing inside it. The file is the hotel's, so it is served from the hotel's
 * own domain and dressed in the hotel's own controls.
 *
 * Everything here is state the <video> element already keeps; this only mirrors
 * it so the markup can bind to it. The events are the authority, never this
 * object — a player that tracks its own idea of "playing" drifts the moment the
 * browser pauses for a stall, a call, or a background tab.
 */
export default () => ({
  ready: false,
  playing: false,
  muted: false,
  current: 0,
  duration: 0,
  buffered: 0,
  scrubbing: false,
  controlsVisible: true,
  fullscreen: false,
  _idle: null,

  init() {
    const v = this.$refs.v;
    if (!v) return;

    const sync = () => {
      this.playing = !v.paused && !v.ended;
      this.muted = v.muted;
    };

    v.addEventListener('loadedmetadata', () => { this.duration = v.duration || 0; this.ready = true; });
    v.addEventListener('durationchange', () => { this.duration = v.duration || 0; });
    v.addEventListener('timeupdate', () => { if (!this.scrubbing) this.current = v.currentTime; });
    v.addEventListener('progress', () => {
      // The last buffered range is the one that matters for a linear watch.
      if (v.buffered.length) this.buffered = v.buffered.end(v.buffered.length - 1);
    });
    ['play', 'pause', 'ended', 'volumechange'].forEach((e) => v.addEventListener(e, sync));
    v.addEventListener('play', () => this.armIdle());
    v.addEventListener('pause', () => this.showControls());

    document.addEventListener('fullscreenchange', () => {
      this.fullscreen = document.fullscreenElement === this.$refs.shell;
    });

    sync();
  },

  // --- transport ------------------------------------------------------------
  toggle() {
    const v = this.$refs.v;
    if (v.paused) { v.play().catch(() => {}); } else { v.pause(); }
  },

  play() { this.$refs.v?.play().catch(() => {}); },

  stop() {
    const v = this.$refs.v;
    if (!v) return;
    v.pause();
    v.currentTime = 0;
  },

  toggleMute() {
    const v = this.$refs.v;
    v.muted = !v.muted;
  },

  seek(event) {
    const v = this.$refs.v;
    const to = Number(event.target.value);
    this.current = to;
    if (Number.isFinite(to)) v.currentTime = to;
  },

  skip(by) {
    const v = this.$refs.v;
    v.currentTime = Math.min(this.duration || 0, Math.max(0, v.currentTime + by));
  },

  async toggleFullscreen() {
    const shell = this.$refs.shell;
    if (document.fullscreenElement) { await document.exitFullscreen().catch(() => {}); }
    else { await shell.requestFullscreen?.().catch(() => {}); }
  },

  // --- chrome ---------------------------------------------------------------
  // The bar hides while the film runs and comes back on any intent to use it.
  showControls() {
    this.controlsVisible = true;
    clearTimeout(this._idle);
    if (this.playing) this.armIdle();
  },

  armIdle() {
    clearTimeout(this._idle);
    this._idle = setTimeout(() => {
      if (this.playing && !this.scrubbing) this.controlsVisible = false;
    }, 2600);
  },

  // --- formatting -----------------------------------------------------------
  clock(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${String(s).padStart(2, '0')}`;
  },

  get percent() {
    return this.duration > 0 ? (this.current / this.duration) * 100 : 0;
  },

  get bufferedPercent() {
    return this.duration > 0 ? Math.min(100, (this.buffered / this.duration) * 100) : 0;
  },
});
