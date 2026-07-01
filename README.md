# Sabay Clone - High-Performance WordPress News Theme

A custom-built WordPress theme designed for high-traffic news portals, inspired by Sabay News. Built with a focus on Core Web Vitals, Khmer typography, and editorial control.

## 🚀 Key Features

- **Tailwind CSS v3 Framework:** Minimal CSS footprint with a utility-first approach.
- **Khmer Font Integration:** Pre-configured with *Kantumruy Pro* and *Battambang*.
- **Editorial Metadata:** Native custom fields for:
    - `Trending Score` (0-100) for manual sidebar curation.
    - `Video URL` support with automated "Play" icon overlays.
    - `Sponsored` flag for advertorial content styling.
- **Dynamic Components:**
    - **Mega Menu:** 4-column grid layout for sub-categories.
    - **Breaking News Ticker:** CSS-animated scrolling marquee.
    - **Infinite Scroll:** AJAX-powered "Load More" news feed.
- **Performance Optimized:**
    - **Transient Caching:** Database query results cached for 15-60 minutes.
    - **LCP Optimization:** Eager loading for hero images.
    - **Lightweight:** Zero reliance on heavy page builders or plugins.

## 🛠 Tech Stack

- **PHP:** WordPress 6.x+
- **Styling:** Tailwind CSS v3
- **JS:** Vanilla JavaScript (ES6+) for AJAX and interactions.
- **Build Tool:** Node.js (PostCSS & Tailwind CLI)

## 📦 Installation

1. Navigate to the theme directory:
   ```bash
   cd wp-content/themes/sabay-clone
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Compile CSS:
   ```bash
   npm run build
   ```

## 💻 Development

To watch for changes and recompile CSS automatically during development:
```bash
npm run watch
```

## 📁 Project Structure

- `assets/css/src/input.css`: The Tailwind entry point.
- `inc/admin/`: wp-admin-only code — `custom-fields.php` (metadata meta box), `site-settings.php` (Options page), `dashboard-widget.php`, `i18n-admin.php` (Translation meta box).
- `inc/frontend/`: code that renders on the public site — `class-mega-menu-walker.php` (custom navigation walker), `performance.php` (transient caching helper), `seo.php` (Open Graph & SEO tags), `integrations.php` (GA4/GTM output).
- `inc/i18n.php`, `inc/icons.php`: shared by both admin and frontend, kept at the `inc/` root.
- `template-parts/`: Reusable UI components (`content-grid.php` for card layouts, `content.php`/`content-none.php` as the `index.php` fallback).
- `archive.php`, `search.php`, `404.php`: Standard template-hierarchy fallbacks for category/tag archives, search results, and not-found pages.
- `languages/`: `.pot` translation template for admin-facing `__()` strings (site content strings use the `t()` system in `i18n/strings.php` instead).

## ⚖️ License
GNU General Public License v2 or later.
