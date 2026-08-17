import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './app/Views/**/*.php',
    './modules/**/Views/**/*.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // Norlanka brand palette. The accent is driven by CSS variables so a
        // page scope (e.g. .theme-esg-green on Our Impact) can recolour every
        // brand-red utility without touching templates.
        brand: {
          DEFAULT: 'rgb(var(--accent) / <alpha-value>)',
          red: 'rgb(var(--accent) / <alpha-value>)',
          'red-dark': 'rgb(var(--accent-dark) / <alpha-value>)',
          // Foreground for text/icons sitting ON a filled accent surface.
          // White is only legible on a dark accent; the amber needs near-black.
          ink: 'rgb(var(--accent-ink) / <alpha-value>)',
          // Themeable ground: light-grey by default, near-black inside .on-dark.
          black: 'rgb(var(--bg) / <alpha-value>)',
        },
        // Themeable foreground: ink on light ground, near-white inside .on-dark.
        // (No solid `bg-white` exists in the app, so this override is safe.)
        white: 'rgb(var(--fg) / <alpha-value>)',
      },
      fontFamily: {
        // Brand Guide: K2D = primary/display, Avenir = secondary/body
        // (Montserrat fallback — Avenir is licensed, local() first when present).
        // Noto sits after the brand faces, not instead of them: Latin still
        // renders in Avenir/K2D, and only Sinhala and Tamil glyphs — which the
        // brand faces do not carry — fall through to Noto.
        sans: ['Avenir', 'Avenir Next', 'Montserrat', 'Noto Sans Sinhala', 'Noto Sans Tamil', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['K2D', 'Noto Sans Sinhala', 'Noto Sans Tamil', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      letterSpacing: {
        widest: '0.2em',
      },
    },
  },
  plugins: [typography],
};
