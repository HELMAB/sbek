/** @type {import('tailwindcss').Config} */
// Admin-only build. Preflight is DISABLED so Tailwind's base reset does not
// fight wp-admin's own styles. Output: assets/css/admin.css.
module.exports = {
  content: [
    "./inc/site-settings.php",
    "./assets/js/admin-site-settings.js",
  ],
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
      colors: {
        'sabay-red': '#e31e24',
        'sabay-blue': '#0056b3',
      },
    },
  },
  plugins: [],
}
