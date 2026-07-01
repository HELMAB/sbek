/** @type {import('tailwindcss').Config} */
// Admin-only build. Preflight is DISABLED so Tailwind's base reset does not
// fight wp-admin's own styles. Output: assets/css/admin.css.
module.exports = {
  content: [
    "./inc/admin/site-settings.php",
    "./inc/admin/dashboard-widget.php",
    "./assets/js/admin-site-settings.js",
  ],
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
      colors: {
        'sbek-red': '#e31e24',
        'sbek-blue': '#0056b3',
      },
    },
  },
  plugins: [],
}
