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
          // Themeable ground: light-grey by default, near-black inside .on-dark.
          black: 'rgb(var(--bg) / <alpha-value>)',
        },
        // Themeable foreground: ink on light ground, near-white inside .on-dark.
        // (No solid `bg-white` exists in the app, so this override is safe.)
        white: 'rgb(var(--fg) / <alpha-value>)',
      },
      fontFamily: {
        // Archivo = display/headings, Inter = body/UI.
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Archivo', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      letterSpacing: {
        widest: '0.2em',
      },
    },
  },
  plugins: [typography],
};
