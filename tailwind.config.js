/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        'sabay-red': '#e31e24',
        'sabay-blue': '#0056b3',
      },
      fontFamily: {
        'khmer': ['Kantumruy Pro', 'Battambang', 'system-ui', 'sans-serif'],
      },
      animation: {
        'marquee': 'marquee 25s linear infinite',
      },
      keyframes: {
        marquee: {
          '0%': { transform: 'translateX(0%)' },
          '100%': { transform: 'translateX(-100%)' },
        }
      },
    },
  },
  plugins: [],
}
