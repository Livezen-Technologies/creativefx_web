import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './app/Views/**/*.php',
    './modules/**/Views/**/*.php',
    // Class names are not only written in templates. The dashboard's column
    // widths are declared in a PHP library so the layout can be data rather
    // than markup, and until this glob existed Tailwind never saw them: the
    // grid compiled with no col-span rules at all and every panel collapsed
    // into a narrow column. Anything that can emit a class has to be scanned.
    './modules/**/Libraries/**/*.php',
    './modules/**/Config/**/*.php',
    './modules/**/Controllers/**/*.php',
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
        // Giant Forests uses the pairing the hotel's own site does: Playfair
        // Display for headings, Poppins for everything else.
        sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Playfair Display', 'Georgia', 'ui-serif', 'serif'],
      },
      letterSpacing: {
        widest: '0.2em',
      },
    },
  },
  plugins: [typography],
};
