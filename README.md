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
- `inc/custom-fields.php`: Metadata logic.
- `inc/class-mega-menu-walker.php`: Custom navigation walker.
- `inc/performance.php`: Transient caching helper.
- `inc/seo.php`: Open Graph & SEO tags.
- `template-parts/`: Reusable UI components.

## ⚖️ License
GNU General Public License v2 or later.
