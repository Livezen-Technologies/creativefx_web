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
        // Norlanka brand palette
        brand: {
          DEFAULT: '#CF2030',
          red: '#CF2030',
          'red-dark': '#A5121F',
          black: '#000000',
        },
      },
      fontFamily: {
        // K2D = primary, Avenir = secondary (Montserrat fallback — Avenir is licensed)
        sans: ['K2D', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        k2d: ['K2D', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        avenir: ['Avenir', 'Avenir Next', 'Montserrat', 'ui-sans-serif', 'sans-serif'],
      },
      letterSpacing: {
        widest: '0.2em',
      },
    },
  },
  plugins: [typography],
};
