/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.php',
    './public/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // Soft light-pink brand palette.
        brand: {
          50: '#fff5f8',
          100: '#ffe4ee',
          200: '#ffc7dc',
          300: '#ff9ec1',
          400: '#ff6fa3',
          500: '#f43f76',
          600: '#e11d62',
          700: '#be1452',
          800: '#9d1648',
          900: '#841642',
        },
      },
      fontFamily: {
        sans: ['"Segoe UI"', 'system-ui', '-apple-system', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
