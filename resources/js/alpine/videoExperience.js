/**
 * videoExperience — the Home "launch experience" controller.
 *
 * Netflix-style multi-language AUDIO switching. Each language has its own
 * <audio> element (with a WebVTT subtitle <track>). The active audio element
 * acts as the master clock; subtitles are rendered manually from its track's
 * `cuechange` events so they work even without a <video> surface.
 *
 * If a real background <video> is present (added later via the CMS), it plays
 * muted/looping as the visual and is kept in sync with the audio clock.
 *
 * Config (from the view, sourced from the CMS `videos` tables):
 *   { defaultLocale:'en', locales:[{code,label,hasAudio,hasSubtitle}, ...] }
 */
export default function videoExperience(config = {}) {
  return {
    locales: config.locales || [],
    current: config.defaultLocale || 'en',
    started: false,
    playing: false,
    subtitlesOn: true,
    caption: '',
    hasVideo: false,
    video: null,
    audios: {},
    tracks: {},

    init() {
      this.video = this.$refs.video || null;
      this.hasVideo = !!(this.video && this.video.querySelector('source[src]'));

      this.locales.forEach((l) => {
        const a = this.$refs['audio_' + l.code];
        if (!a) return;
        a.loop = true;
        this.audios[l.code] = a;

        const tt = a.textTracks && a.textTracks[0];
        if (tt) {
          tt.mode = 'hidden'; // fires cuechange without needing a video surface
          this.tracks[l.code] = tt;
        }
        a.addEventListener('timeupdate', () => {
          if (this.audios[this.current] === a) this.syncVideo();
        });
      });

      this.bindCaptions(this.current);

      if (this.hasVideo) {
        this.video.muted = true;
        const p = this.video.play();
        if (p && p.catch) p.catch(() => {});
      }
    },

    clock() {
      return this.audios[this.current] || null;
    },

    bindCaptions(code) {
      Object.values(this.tracks).forEach((tt) => { tt.oncuechange = null; });
      this.caption = '';
      const tt = this.tracks[code];
      if (!tt) return;
      tt.mode = 'hidden';
      tt.oncuechange = () => {
        if (!this.subtitlesOn) { this.caption = ''; return; }
        const cue = tt.activeCues && tt.activeCues[0];
        this.caption = cue ? cue.text : '';
      };
    },

    // First user gesture: required by browsers before audio can play with sound.
    start() {
      const a = this.clock();
      if (!a) return;
      try { a.currentTime = 0; } catch (e) {}
      const p = a.play();
      if (p && p.catch) p.catch(() => {});
      this.started = true;
      this.playing = true;
      this.syncVideo();
    },

    togglePlay() {
      if (!this.started) { this.start(); return; }
      const a = this.clock();
      if (!a) return;
      if (this.playing) {
        a.pause();
        this.playing = false;
      } else {
        const p = a.play();
        if (p && p.catch) p.catch(() => {});
        this.playing = true;
      }
    },

    switchLanguage(code) {
      if (code === this.current) return;
      const wasPlaying = this.playing;
      const at = this.clock() ? this.clock().currentTime : 0;

      Object.values(this.audios).forEach((a) => a.pause());
      this.current = code;
      this.bindCaptions(code);

      const a = this.clock();
      if (a) {
        try { a.currentTime = at; } catch (e) {}
        if (wasPlaying && this.started) {
          const p = a.play();
          if (p && p.catch) p.catch(() => {});
        }
      }
      // Let other components (e.g. localized headline copy) react.
      this.$dispatch('experience-locale', { locale: code });
    },

    syncVideo() {
      if (!this.hasVideo) return;
      const a = this.clock();
      if (!a) return;
      const dur = this.video.duration || 0;
      try {
        this.video.currentTime = dur ? (a.currentTime % dur) : a.currentTime;
      } catch (e) {}
    },

    toggleSubtitles() {
      this.subtitlesOn = !this.subtitlesOn;
      if (!this.subtitlesOn) this.caption = '';
    },
  };
}
