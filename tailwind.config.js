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
          black: '#000000',
        },
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
