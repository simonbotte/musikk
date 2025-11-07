/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./components/**/*.{vue,js,ts}",
    "./pages/**/*.{vue,js,ts}",
    "./app.config.{js,ts}",
  ],
  content: [],
  theme: {
    extend: {
      colors: {
        crusta: {
          50: '#FDF6EF',
          100: '#FAECD9',
          200: '#F4D5B3',
          300: '#ECB884',
          400: '#E49154',
          500: '#DF7A3F',
          600: '#CF5C2D',
          700: '#AB4725',
          800: '#893922',
          900: '#3B170D',
        },
        orange: {
          50: "#fff5ec",
          100: "#ffe8d1",
          200: "#ffcea3",
          300: "#ffb173",
          400: "#ff9444",
          500: "#ff791b",
          600: "#e66108",
          700: "#b64c06",
          800: "#853904",
          900: "#532302",
          950: "#2b1301",
        },
        'dark-gray': '#181818',
      },
      fontFamily: {
        sans: ['"Satoshi"', 'sans-serif'],
      },
    },
  },
  plugins: [],
}