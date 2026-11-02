# CLAUDE.md

Guidance for Claude Code (claude.ai/code) when working in this repository.

## What this is

**Sbek** is a custom classic WordPress theme for a bilingual (Khmer primary, English secondary) news site. Its front end ports the Sabay-News-style layout study in `../../../sabay-news-ui` (Next.js): measurements, breakpoints and behaviour come from there. This repo *is* the theme; WordPress core lives outside it.

## Build & development

```bash
nvm use            # Node 22 (.nvmrc); Tailwind v4 needs Node 20+
npm install
npm run build      # assets/css/src/input.css → assets/css/main.css (minified)
npm run watch
npm run build:admin  # assets/css/src/admin.css → assets/css/admin.css (Options page + dashboard widget)
```

- `main.css` / `admin.css` are gitignored build output — rebuild after editing CSS **or** adding Tailwind utility classes to PHP.
- `input.css` uses `@import "tailwindcss" source(none)` with explicit `@source` paths (front-end templates only). Components are **plain CSS in `@layer components`** with semantic class names (`.hero-item`, `.section-tab`, `.feed-item`…); Tailwind supplies preflight + utilities. Breakpoints are Bootstrap 3's (768/992/1200) and `container` is redefined via `@utility container` (750/970/1170px).
- Fonts are self-hosted Khmer subsets (`assets/fonts/`, OFL). Latin text intentionally renders in Arial.
- JS: one deferred vanilla file, `assets/js/theme.js` — modules activate on `data-sbek-*` hooks. No bundler.
- Translations: after changing strings run
  `wp i18n make-pot . languages/sbek.pot --domain=sbek --exclude=node_modules,assets/css,inc/cli`,
  update `languages/km.po`, then `wp i18n make-mo languages/km.po languages/ && wp i18n make-php languages/km.po languages/`.
- Demo data: `wp sbek demo import` / `wp sbek demo reset --yes` (see below).
- No test suite / linter is configured. Smoke-test with `php -l`, `wp eval`, and by crawling pages with `WP_DEBUG_LOG` on.

## Architecture

`functions.php` requires modules in order: **`inc/i18n.php` first** (it routes the URL language and switches the locale at include time), then `inc/security.php`, then the rest. `inc/admin/` = wp-admin screens/meta boxes, `inc/frontend/` = public-site code, `inc/` root = shared. `inc/cli/` loads only under WP-CLI.

### Languages (`inc/i18n.php`, `inc/admin/translation.php`) — read before touching routing, queries or links

- Languages come from `sbek_languages()` (`km` → locale `km`, `en` → `en_US`); default `SBEK_DEFAULT_LANGUAGE = 'km'`.
- **URLs:** `/` = Khmer (canonical), `/en/…` = English, `/km/…` 301s to canonical. `sbek_bootstrap_language()` runs at include time: reads the prefix, sets `$GLOBALS['sbek_lang']`, strips the prefix from `$_SERVER['REQUEST_URI']` so core routes normally, and calls `switch_to_locale()` so **standard `__()` strings and core strings (dates, comment form) follow the URL**. Only runs for front-end `index.php` requests (not admin/AJAX/cron/CLI/login).
- **UI strings:** plain `__()` / `esc_html__()` with text domain `sbek`; English source, Khmer in `languages/km.po`. There is no custom `t()` helper anymore.
- **Links:** on non-default-language pages, link filters (`post_link`, `term_link`, `get_pagenum_link`, …) add the prefix automatically — templates use `get_permalink()` / `get_term_link()` directly. Use `sbek_home_url()` instead of `home_url('/')`, and `sbek_localize_url()` only for hand-built URLs. `sbek_redirect_canonical()` stops core's canonical redirect from looping on prefixed URLs.
- **Content:** private, hierarchical `language` taxonomy (km/en, `show_in_rest` for the editor panel). `pre_get_posts` scopes every front-end query to the current language (opt out with query var `sbek_all_languages => true`). A post requested in the wrong language redirects to its translation or that language's home. Posts saved without a language get the default (`wp_set_object_terms` — not `wp_set_post_terms`, which would cast slugs to 0 on a hierarchical taxonomy).
- **Translations:** `_sbek_translation_id` post meta on both posts, managed by the Translation meta box; `sbek_set_translation()` keeps pairs symmetric and releases previous partners. Drives hreflang and the header switcher.
- **Term names:** category/tag names are Khmer; English goes in term meta `sbek_name_en`. A `get_term` filter swaps names on English pages, so menus, badges and archive titles localize automatically.
- Adding a language: add it to `sbek_languages()`, ship `languages/{locale}.po/.mo/.l10n.php`, add a `primary-{slug}` menu location (`inc/setup.php`), and install its core language pack.

### Content model

- **Categories / tags** (`inc/admin/term-meta.php`): `sbek_color` (accent; badges, tab headers, arrows), `sbek_name_en`, and for categories `sbek_home_order` (1, 2, 3… = homepage block order; none set → all top-level categories).
- **Post meta** (`inc/admin/custom-fields.php`, registered with `register_post_meta`): `_sbek_trending_score` (0–100, "Popular"), `_sbek_video_url` (oEmbed above the article), `_sbek_source_name` / `_sbek_source_url`, `_sbek_is_sponsored`, `_sbek_is_breaking` (header ticker).
- **Homepage hero** = sticky posts (then latest). **Video block** = posts in the *Video* post format.
- **Site settings** (`inc/admin/site-settings.php`, option `sbek_site_settings`, read with `sbek_site_setting()` / `sbek_site_text()` for per-language `*_en` overrides): logo, OG image, description(+_en), address(+_en), email, phones, socials, GA4/GTM, raw header/footer scripts (unfiltered only for `unfiltered_html` users).
- **Widget areas**: `sbek-sidebar`, `sbek-ad-header` (728×90), `sbek-ad-hero` (2×300×250), `sbek-ad-video` (300×250). Empty ad areas render nothing and the layout reclaims the space. (Ad-blockers hide `.ad-area` — expected.)

### Queries & caching (`inc/queries.php`)

`sbek_cached_post_ids( $name, $args, $ttl )` caches **ID lists** in a transient keyed `sbek_q_{name}_{lang}_v{version}`; `sbek_posts_by_ids()` loads them in one query and primes thumbnail caches. The version (`sbek_cache_version` option) is bumped on post save/status change, sticky changes and term edits — there is no list of keys to flush; add new cached queries by just picking a unique `$name`. Block helpers: `sbek_get_featured_posts()`, `sbek_get_video_posts()`, `sbek_get_category_posts()`, `sbek_get_popular_posts()`, `sbek_get_latest_posts()`, `sbek_get_related_posts()`, `sbek_get_breaking_posts()`, `sbek_get_home_categories()`.

### Templates

- `front-page.php` → `template-parts/home/{featured,video,category}.php`. The hero is **one** set of tiles: CSS grid ≥768px, JS carousel below.
- `archive.php`, `search.php`, `index.php` → `template-parts/feed.php` (2 lead + list; list/grid switch; infinite scroll = `theme.js` fetching the `rel=next` pagination page (same-origin) and appending its `[data-feed-item]` cards — no AJAX endpoint; plain pagination remains as the no-JS fallback).
- `single.php` (cover image injected after the first paragraph by `inc/frontend/content.php`; Khmer "show word breaks" toggle when content contains U+200B), `page.php`, `404.php`, `sidebar.php` (`get_sidebar( null, [ 'boxed' => bool ] )`).
- **No comments:** the theme has no comment UI, and `inc/setup.php` forces `comments_open`/`pings_open` to false (plus hides the comment feed) so nothing can be submitted that nobody would see. Existing comments stay in the database. To bring comments back, restore a `comments.php` + `comments_template()` call and drop those filters.
- Cards in `template-parts/card/` take a `WP_Post` via `$args['post']` (no global loop juggling).
- Helpers in `inc/template-tags.php`: `sbek_get_thumb()`, `sbek_get_post_image()`, `sbek_get_category_badge()`, `sbek_section_header()`, `sbek_primary_category()`, `sbek_term_color()`, `sbek_post_date()`, `sbek_get_logo()`, `sbek_ad_area()`. `sbek_get_*` return escaped HTML; others echo.
- Primary menu: `Sbek_Menu_Walker` (flat; front-page item renders as the house icon; active state from core's `current-*` classes; non-Khmer labels get the Latin face). Locations `primary` (Khmer) and `primary-en`; `sbek_menu_fallback()` shows Home + categories until a menu is assigned.

### Security (`inc/security.php` + per module)

Security headers, no generator tag, XML-RPC off, `/wp/v2/users` hidden from anonymous visitors, login lockout (5 failures/IP → 15 min). Every form: nonce + capability check + sanitize on input (registered meta sanitizers) + escape on output. Newsletter (`inc/frontend/newsletter.php`): honeypot, nonce, per-IP-hash throttle, private CPT readable only by `manage_options`. SEO tags (`inc/frontend/seo.php`) step aside when Yoast/Rank Math/SEOPress/AIOSEO is active. Site-wide concerns (headers, lockout, analytics) would ideally live in a must-use plugin.

### Demo data (`inc/cli/class-sbek-demo-command.php`)

`wp sbek demo import [--skip-site-setup] [--posts=24]` creates categories/tags (colors, English names, homepage order), GD-generated images, ~120 Khmer + 20 English posts (sticky, video format, breaking, trending, translations linked; comments closed), pages, both menus, ad placeholder widgets and Options-page settings; unless `--skip-site-setup`, it also sets permalinks `/article/%post_id%/`, category base `topics`, tag base `article/tag`, Khmer locale (downloads the core pack), Phnom Penh timezone and 8 posts/page. Everything created is recorded in the `sbek_demo_manifest` option; `wp sbek demo reset --yes` deletes exactly that (site options are left as they are).

## Conventions

- Prefix everything `sbek_` / `_sbek_` / `Sbek_`; text domain `sbek`.
- Escape late (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); sanitize early; nonces + `current_user_can()` on every write.
- Keep templates free of queries where a helper in `inc/queries.php` fits; keep markup semantic and styles in `input.css`.
