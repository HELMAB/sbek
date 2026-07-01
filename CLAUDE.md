# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Sbek** is a custom WordPress theme (no page builder, no i18n plugin) for a high-performance news site, inspired by Sabay News. This repository *is* the theme — it's dropped into a WordPress install at `wp-content/themes/sbek`, but WordPress core itself lives outside this repo and is not part of it.

## Build & development

```bash
npm install        # one-time
npm run build      # compile + minify Tailwind: assets/css/src/input.css → assets/css/main.css
npm run watch      # recompile on change during development
npm run build:admin  # compile the separate wp-admin-only stylesheet: assets/css/src/admin.css → assets/css/admin.css
npm run watch:admin
```

- Styling is **Tailwind CSS v4** via the standalone `@tailwindcss/cli` (no webpack/PostCSS pipeline). Requires **Node 20+** — `.nvmrc` pins the version to use.
- There is **no `tailwind.config.js`** — theme customization (colors, fonts, animations) lives directly in `@theme { ... }` blocks inside `assets/css/src/input.css` and `assets/css/src/admin.css`.
- The **`@tailwindcss/typography`** plugin is loaded via `@plugin "@tailwindcss/typography";` in `input.css` (resolved from `node_modules` by the `npx @tailwindcss/cli` package build). Single-post content uses `prose` (`single.php`), themed to the brand via inline `--tw-prose-*` variable overrides + `prose-*` modifiers; a small unlayered rule in `input.css` strips the plugin's blockquote quote glyphs.
- `admin.css` deliberately imports `tailwindcss/theme.css` + `tailwindcss/utilities.css` (not the full `tailwindcss` package) so Tailwind's preflight/base reset doesn't fight wp-admin's own styles, and scopes its content scanning with `@source`/`source(none)` to only the admin files it actually styles (`inc/admin/site-settings.php`, `inc/admin/dashboard-widget.php`, `assets/js/admin-site-settings.js`) rather than the whole theme.
- `assets/css/main.css` and `assets/css/admin.css` are gitignored build output — after editing any `.php` template or adding utility classes, rerun the relevant `npm run build*` or the classes won't exist in the stylesheet.
- JS is vanilla ES6 (`assets/js/`), no bundler.
- There is no test suite or linter configured for this theme.

## Theme architecture

`functions.php` is the entry point and `require`s the `inc/` modules in a deliberate order — **`inc/i18n.php` must load first** because the global `t()` and `sbek_lang()` helpers it defines are used throughout every other module and template. `inc/security.php` loads second (security headers/hardening apply regardless of what else initializes). `inc/` is split by where the code runs: `inc/admin/` holds wp-admin-only screens and meta boxes, `inc/frontend/` holds code that renders on the public site. `inc/i18n.php` and `inc/icons.php` stay at the `inc/` root because both are loaded by, and used from, admin and frontend code alike.

### Multilingual system (`inc/i18n.php`, `inc/admin/i18n-admin.php`) — the central design

A custom, plugin-free i18n layer. Languages are defined by the `SBEK_LANGUAGES` constant (`['en', 'jp']`, default `en`). Understand this before touching routing, queries, or templates:

- **URL scheme:** `/` = English (canonical, no prefix); `/jp/...` = Japanese; `/en/...` 301-redirects to the unprefixed canonical. On `init` priority 1, the language is detected from `REQUEST_URI`, stored in `$GLOBALS['sbek_lang']`, and the `/jp` prefix is *stripped from `$_SERVER['REQUEST_URI']`* so core WordPress parses the URL normally.
- **Content separation:** a non-public `language` taxonomy tags every post/page. A global `pre_get_posts` filter injects a `tax_query` so the front end only ever shows the current language's posts. `template_redirect` bounces single posts that don't match the active language to that language's homepage (but never the front page itself, to avoid a self-redirect loop on an untagged front page). A `save_post` hook auto-assigns `SBEK_DEFAULT_LANGUAGE` to any post/page saved without a language term — otherwise it would silently vanish from every front-end query and redirect away from its own permalink with no visible error.
- **Building URLs:** never emit a raw `get_permalink()` in templates — wrap it in `sbek_localize_url()` so the `/jp` prefix is applied for non-default languages.
- **UI strings:** use `t('key')` (or `t('key', $arg1, ...)` for sprintf), backed by `i18n/strings.php` (a nested `[lang][key] => string` array) — this *replaces* WordPress's `__()`/`_e()` for theme strings.
- **Translation linking:** posts are paired across languages via a bidirectional `_translation_id` post meta, set through the "Translation" meta box (`inc/admin/i18n-admin.php`), which updates the meta on both posts when saved. This drives `hreflang` tags and `sbek_language_switcher()`. Category/tag names are localized via `name_{lang}` term meta (read with `sbek_term_name()`, written via `created_{taxonomy}`/`edited_{taxonomy}` hooks), skipping the default language.

When adding a language: add its slug to `SBEK_LANGUAGES`, add a top-level block in `i18n/strings.php`, and extend the URL regex / hreflang logic in `inc/i18n.php`.

### Editorial metadata (`inc/admin/custom-fields.php`)

A "News Metadata" meta box on posts stores post meta: `_sbek_trending_score` (0–100, drives the Trending sidebar), `_sbek_video_url` (renders a play-icon overlay on grid cards), `_sbek_is_sponsored` (advertorial styling), and `_sbek_is_breaking` (breaking-news ticker). Saved via the `save_post` hook, nonce `sbek_save_news_meta`, gated on `edit_post` capability; checkboxes default to `0` when the POST key is absent.

### Performance / caching (`inc/frontend/performance.php`)

`sbek_get_cached_query( $args, $transient_name, $expiration )` is the standard pattern for sidebar/section queries: it caches only post **IDs** in a transient (900s / 15-min default), then runs a light `post__in` query. Transient names are **per-language** (e.g. `sbek_breaking_news_{$lang}`, `sbek_trending_sidebar_{$lang}`, `sbek_404_popular_{$lang}`, `sbek_related_{$post_id}_{$lang}`) and are flushed on `save_post` via `sbek_clear_transients_on_save()` — match that naming and flushing when adding a new cached query. Note: the related-posts cache is keyed per-post and survives a category change on that post until its TTL expires.

### Security (`inc/security.php`)

Shared by admin and frontend. Defines `DISALLOW_FILE_EDIT` at include time (must happen before capability checks run, not on a hook). Sends baseline security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`) on `send_headers`. Strips the WP generator meta tag and `?ver=` query strings from enqueued assets (unless `SCRIPT_DEBUG`). Disables XML-RPC. Implements login rate limiting via the `authenticate` filter + transients keyed by hashed IP: 5 failed attempts locks out for 15 minutes.

### Other modules

- `inc/frontend/class-mega-menu-walker.php` — `Sbek_Mega_Menu_Walker` renders a 4-column Tailwind mega-menu (sub-menus are absolutely positioned, shown via `group-hover`). Two nav menus are registered: `menu-1` (English) and `menu-1-jp` (Japanese).
- `inc/frontend/seo.php` — Open Graph/Twitter card tags on singular views, `NewsMediaOrganization` and `NewsArticle` JSON-LD (sourced from Site Settings: description, logo, socials, phones, lat/lng), and localized sitemap entries added to `robots.txt` for non-default languages.
- `inc/frontend/integrations.php` — GA4 / GTM / custom header-footer scripts on `wp_head` (priority 20) / `wp_body_open`, sourced from the Options page; GA4 only loads if no GTM ID is set; all output skipped when `is_admin()`.
- `inc/frontend/search.php` — adds an optional category facet (`?cat=`) to the front-end search query via `pre_get_posts`.
- `inc/admin/site-settings.php` — the Options page (company info, branding, analytics IDs, repeatable phone/social rows) backing `sbek_site_setting()`. Repeater rows use an `__i__` placeholder swapped by JS when adding rows. Raw header/footer `<script>` fields are capability-gated: only users with `unfiltered_html` can save them unescaped, others get them run through `wp_kses_post()`.
- `inc/admin/dashboard-widget.php` — admin dashboard widget tracking site-identity setup completeness (logo, OG image, lat+lng, description, phones, socials, GA4/GTM) by reading `sbek_site_setting()` directly; enqueues `admin.css` only on the `index.php` dashboard screen.
- `inc/icons.php` — `sbek_icon()` inline-SVG helper backed by a registry of Lucide (stroke) and Simple Icons (fill) icon markup; `sbek_social_icon_name()` maps free-text platform labels (e.g. "tik tok") to a known icon name, falling back to a generic link icon.
- `inc/frontend/comments.php` — `sbek_comment_list_item()` (the `wp_list_comments()` callback) and `sbek_comment_form_args()` (localized, Tailwind-styled `comment_form()` overrides), consumed by the root `comments.php` template, pulled in via `comments_template()` in `single.php`. Comment moderation/spam is entirely native WordPress (Discussion settings).
- `inc/admin/login.php` — brands `wp-login.php` via hooks only: `login_header` echoes a brand panel, `login_head` injects inline CSS swapping the logo, `login_message`/`login_footer` add copy and placeholder text. Since a literal `wp-login.php` request can't use the `/jp/` URL prefix, language is read from a `?lang=` query var, falling back to a `sbek_login_lang` cookie so the choice persists across lostpassword/register. Core's own field labels/buttons never go through `t()` — they're plain `__()` calls against WordPress's `default` textdomain — so this module additionally registers `gettext` filters (only when Japanese is active) that swap a known list of exact core strings. Extend that list if an untranslated core string shows up.
- AJAX "Load More" infinite scroll: handler `sbek_ajax_load_more()` in `functions.php` (actions `wp_ajax[_nopriv]_sbek_load_more`), driven by `assets/js/load-more.js` (fetch-based, no jQuery); re-applies the language `tax_query` and renders `template-parts/content-grid.php` per post. Nonce: `sbek_load_more_nonce`.
- `assets/js/motion.js` — `IntersectionObserver`-driven scroll-reveal for `.scroll-reveal` elements (supports a `data-delay` stagger attribute), a back-to-top button, and clipboard-based copy-link sharing.

### Templates

`front-page.php` (homepage grid + per-category sections), `single.php`, `archive.php`, `search.php`, `404.php`, `index.php` (fallback), `header.php`/`footer.php`, `comments.php`, and template parts in `template-parts/`: `content-grid.php` (the standard post card — reads the sponsored/video meta), `content.php` (plain listing fallback), `content-none.php` (empty state). Custom brand utilities: `text-sbek-red` (#d77655), `text-sbek-blue`, `font-khmer`, and `animate-marquee` (breaking-news ticker), all defined in the `@theme` block in `assets/css/src/input.css`.

## Conventions

- All theme functions, hooks, meta keys, and transients are prefixed `sbek_` / `_sbek_`. Text domain is `sbek`.
- Escape on output (`esc_url`, `esc_html`, `esc_attr`) and wrap permalinks in `sbek_localize_url()`.
