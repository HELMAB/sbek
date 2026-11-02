<p align="center">
  <img src="docs/brand/readme-banner.png" width="1280" alt="Sbek — the bilingual Khmer / English news theme for WordPress: hero grid, video and category blocks, 11 Khmer fonts, about 8 KB CSS and 4 KB JS">
</p>

# Sbek — bilingual news theme (Khmer / English)

A fast classic WordPress theme for a Khmer-first news site, with a Sabay-News-style layout: black top bar and sticky red menu, hero grid, video block, colored category blocks with "next" arrows, infinite-scroll archives with a list/grid switch, and a clean article page with a Khmer word-break toggle.

## Features

- **Khmer primary, English secondary** without a plugin: `/` is Khmer, `/en/` is English. Standard WordPress i18n (`__()`, `.po/.mo`) plus `switch_to_locale()`, so theme strings, dates and core strings all follow the URL. Translations are linked post-to-post (hreflang + language switcher). Category names have an English field.
- **Editorial controls:** sticky posts → hero; *Video* post format → video block; per-category accent color and homepage position; trending score; breaking-news ticker; sponsored flag; source name/URL.
- **Performance:** self-hosted Khmer font subsets, one deferred vanilla-JS file, about 8 KB gzipped CSS and 4 KB JS, ID-only transient caching with automatic invalidation, responsive `srcset` images, hero images downloaded once on every viewport.
- **Security:** nonces, capability checks and registered meta sanitizers on every write; escaped output; login lockout; hardened headers; a throttled newsletter with a honeypot.
- **Accessible:** skip link, semantic headings, focus-visible styles, carousel hidden-slide handling, reduced-motion support.

## Requirements

WordPress 6.5+, PHP 8.0+, Node 20+ (build only).

## Setup

```bash
cd wp-content/themes/wp-sbek-theme
nvm use && npm install && npm run build && npm run build:admin
wp theme activate wp-sbek-theme
wp language core install km          # Khmer core strings (dates, comments, admin)
wp option update WPLANG km
```

Then:
1. Open **Theme Option** in the admin menu and work through the tabs: site title/tagline (Khmer + English), logo and icon, brand colors and fonts (Design), homepage sections, category colors / English names / homepage order, contact details, socials, analytics.
2. Build the menus under **Appearance → Menus** (locations *Primary menu (Khmer)* and *(English)*).
3. Put ad code into the *Ad:* widget areas, if you use ads.

### Demo content

Use **Theme Option → Tools → Import demo content** (with a progress bar; **Remove demo content** undoes it). The same is available from the command line:

```bash
wp sbek demo import       # categories, ~140 posts, images, menus, widgets, settings, permalinks
wp sbek demo reset --yes  # removes exactly what import created
wp sbek demo status
```

To go back to the original colors and fonts, use **Theme Option → Design → Reset design to defaults**.

## Development

```bash
npm run watch          # front-end CSS
npm run watch:admin    # admin CSS
```

See `CLAUDE.md` for the architecture: language routing, caching, templates and conventions.

## License

GNU General Public License v2 or later. Fonts: SIL Open Font License 1.1 (see `assets/fonts/`).
