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
npm run build:admin  # assets/css/src/admin.css → assets/css/admin.css (Sbek Theme page + dashboard widget)
```

- `main.css` / `admin.css` are gitignored build output — rebuild after editing CSS **or** adding Tailwind utility classes to PHP.
- `input.css` uses `@import "tailwindcss" source(none)` with explicit `@source` paths (front-end templates only). Components are **plain CSS in `@layer components`** with semantic class names (`.hero-item`, `.section-tab`, `.feed-item`…); Tailwind supplies preflight + utilities. Breakpoints are Bootstrap 3's (768/992/1200) and `container` is redefined via `@utility container` (750/970/1170px).
- Fonts are self-hosted Khmer subsets (`assets/fonts/`, OFL, licenses in `assets/fonts/licenses/`). `@font-face` rules live in `assets/css/src/fonts.css`, imported by both `input.css` and `admin.css`. Stacks are **Khmer family first** (its Khmer-only `unicode-range` lets Latin fall through to the Latin stack).
- JS: one deferred vanilla file, `assets/js/theme.js` — modules activate on `data-sbek-*` hooks. No bundler. A tiny inline `<head>` script (`sbek_head_boot_script()`, `inc/frontend/assets.php`) adds `sbek-js` to `<html>` and restores the saved feed view as `sbek-grid` before first paint — grid-view CSS keys off `.sbek-grid .feed`. Admin-only: `assets/js/admin-translation.js` (Translation meta box search) and `assets/js/admin-news-meta.js` (a Video URL switches the editor's format to Video immediately).
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
- **Translations:** `_sbek_translation_id` post meta on both posts, managed by the Translation meta box; `sbek_set_translation()` keeps pairs symmetric and releases previous partners. Drives hreflang and the header switcher. The box lists the 100 most recent other-language posts plus an admin-ajax title search (`sbek_translation_search`), and offers **Create the {language} version** (`admin-post.php?action=sbek_create_translation`, nonce per post): a linked draft copying title, content, excerpt, image, categories, tags, format and news details (not trending/breaking). Posts/Pages lists have a language filter (`?sbek_language=km|en|none`).
- **Term names:** category/tag names are Khmer; English goes in term meta `sbek_name_en`. A `get_term` filter swaps names on English pages, so menus, badges and archive titles localize automatically. Descriptions: the core description is Khmer; English goes in `sbek_description_en`, read with `sbek_term_description()` (returns '' rather than Khmer on English pages).
- **hreflang** (`sbek_hreflang_links()`): home page, singular views with a linked translation, and category/tag archives when the term has posts in the other language (`sbek_term_has_posts_in()`, transient per term + cache version).
- Adding a language: add it to `sbek_languages()`, ship `languages/{locale}.po/.mo/.l10n.php`, add `primary-{slug}` and `footer-{slug}` menu locations (`inc/setup.php`), and install its core language pack.

### Content model

- **Categories / tags** (`inc/admin/term-meta.php`): `sbek_color` (accent; badges, tab headers, arrows; unset = follows the brand color), `sbek_name_en`, and for categories `sbek_home_order` (1, 2, 3… = homepage block order; none set → first top-level categories). At most `SBEK_HOME_BLOCK_LIMIT` (6, `inc/queries.php`) blocks; empty categories are skipped.
- **Post meta** (`inc/admin/custom-fields.php`, registered with `register_post_meta`): `_sbek_trending_score` (0–100, "Popular" — only posts from the last `popular_days` compete), `_sbek_video_url` (oEmbed above the article; saving one sets the *Video* format if the post has no format; the box warns about Video-format posts with no URL and no embed, see `sbek_content_has_video()`), `_sbek_source_name` / `_sbek_source_url` (`rel="sponsored"` on sponsored posts), `_sbek_is_sponsored` (labelled on every card via `sbek_get_sponsored_label()`, not just the article), `_sbek_is_breaking` (header ticker; expires `breaking_hours` after publication — 5-minute cache).
- **Homepage hero** = sticky posts (then latest). **Video block** = posts in the *Video* post format.
- **Sbek Theme** (`inc/admin/theme-options.php`, top-level admin menu, slug `sbek-theme-options`; the old Settings → Options page URL redirects there via a hidden page's `load-` hook). One tabbed page for all site-wide data — **General** (site title/tagline in Khmer = core `blogname`/`blogdescription`, English = `name_en`/`tagline_en`; logo, core `site_icon`, OG image), **Design** (colors and fonts, see below), **Homepage** (toggles `show_ticker`/`show_video`; **Freshness** `breaking_hours` (default 48) / `popular_days` (default 7), 0 = no limit; **Article page** toggle `show_byline`; category table editing `sbek_color`/`sbek_name_en`/`sbek_home_order` term meta, with an "On homepage / Hidden: …" status per row), **Contact & Footer** (description, email, phones, address, lat/lng, `show_newsletter` + subscriber count/CSV export, copyright with `{year}`/`{site}` — text fields have `_en` variants), **Social**, **Integrations** (GA4/GTM, raw header/footer scripts; unfiltered only for `unfiltered_html` users).
  - Saves via `admin-post.php?action=sbek_save_theme_options` (nonce + `manage_options`) because one form writes the theme option, core options and term meta. Values the sanitizer would drop (bad email, lat/lng, GA4 `G-…` / GTM `GTM-…` IDs, social URLs, a non-square or <512px site icon) are listed by `sbek_settings_input_problems()` / `sbek_site_icon_problem()` and shown as a "not saved" warning after the redirect (per-user transient). Category colors post a `color_auto` flag: `1` deletes `sbek_color` so the category keeps following the brand color. The option array is sanitized by `sbek_sanitize_site_settings()` (registered on `init` with `register_setting`, so `update_option()` from anywhere is sanitized); missing toggle keys default to on, so partial updates from code are safe.
  - Read with `sbek_site_setting( $key )`, `sbek_site_text( $key )` (per-language `*_en` override), `sbek_section_enabled( 'ticker'|'video'|'newsletter'|'byline' )`, `sbek_copyright_text()`. English title/tagline apply on /en/ via `option_blogname`/`option_blogdescription` filters in `inc/i18n.php`.
  - Admin assets: `assets/js/admin-theme-options.js` (tabs with hash + ARIA, repeaters with reorder, live social icons, media pickers, unsaved-changes tracking against a form snapshot + `beforeunload`) and `admin.css` (`npm run build:admin`). An inline boot script hides non-active panels before the JS loads. The page has a `.wp-header-end` marker so core places notices below the header.
  - Admin colors come from CSS variables `--sbek-accent` / `--sbek-accent-dark` / `--sbek-on-accent`, set inline by `sbek_admin_accent_style()` from the Design brand color (Sbek Theme page and the dashboard "Theme Setup" widget). Don't hard-code red/blue in `admin.css`.
- **Menus**: `primary` / `primary-en` (two levels: dropdowns open on hover on desktop and via the chevron `[data-sbek-submenu-toggle]` buttons for keyboard/touch) and `footer` / `footer-en` (one level, row under the footer columns; until assigned, the Privacy Policy link shows in the brand column).
- **Widget areas**: `sbek-sidebar`, `sbek-ad-header` (728×90), `sbek-ad-hero` (2×300×250), `sbek-ad-video` (300×250). Empty ad areas render nothing and the layout reclaims the space. (Ad-blockers hide `.ad-area` — expected.)

### Design: colors & fonts (`inc/design.php`, Sbek Theme → Design)

- Settings `color_brand`, `color_top`, `color_footer`, `font_body`, `font_heading`, `font_latin` (in `sbek_site_settings`). `sbek_design()` returns them validated, plus a derived `brand_dark` (the default red keeps its hand-picked `#d90c16`; custom colors are mixed 15% toward black).
- **The stylesheet stays static.** `sbek_design_css()` prints one inline `:root{…}` block (attached to `sbek-main`) overriding CSS variables: `--color-menu`, `--color-menu-active`, `--color-rule`, `--color-on-menu`, `--color-top`, `--color-on-top`, `--color-footer`, `--color-footer-fg`, `--color-footer-strong`, `--font-sans`, `--font-strong`, `--font-strong-weight`. Defaults for all of them also exist in `input.css`. Only validated hex values and whitelisted font families go into it.
- `sbek_readable_on()` picks text on a colored surface: white if it reaches 3:1 (WCAG AA for large/bold UI text), else `#111`. The admin preview JS mirrors this math exactly.
- **Never hard-code the brand red** in CSS or PHP: use `var(--color-menu)` / `var(--color-on-menu)` in CSS and `sbek_brand_color()` in PHP.
- Fonts come from `sbek_font_choices()` (role `body` / `heading`; `display` fonts render headings at weight 400, text fonts at 700) and `sbek_latin_font_choices()` (system stacks only). The selected body font is preloaded on Khmer pages. The login screen picks up the brand color too.

### Queries & caching (`inc/queries.php`)

`sbek_cached_post_ids( $name, $args, $ttl )` caches **ID lists** in a transient keyed `sbek_q_{name}_{lang}_v{version}`; `sbek_posts_by_ids()` loads them in one query and primes thumbnail caches. The version (`sbek_cache_version` option) is bumped on post save/status change, sticky changes and term edits — there is no list of keys to flush; add new cached queries by just picking a unique `$name`. Block helpers: `sbek_get_featured_posts()`, `sbek_get_video_posts()`, `sbek_get_category_posts()`, `sbek_get_popular_posts()`, `sbek_get_latest_posts()`, `sbek_get_related_posts()`, `sbek_get_breaking_posts()`, `sbek_get_home_categories()`.

### Templates

- `front-page.php` → `template-parts/home/{featured,video,category}.php`. The hero is **one** set of tiles: CSS grid ≥768px, JS carousel below.
- `archive.php`, `search.php`, `index.php` → `template-parts/feed.php` (2 lead + list; list/grid switch; infinite scroll = `theme.js` fetching the `rel=next` pagination page (same-origin) and appending its `[data-feed-item]` cards — no AJAX endpoint; plain pagination remains as the no-JS fallback). After `AUTO_PAGES` (2) automatic loads it shows a **Load more** button so the footer stays reachable; the URL follows via `history.replaceState`, an end-of-list note and a `role=status` count are shown. `archive.php` passes an `intro` (term description + subcategory links, or the author's avatar and bio) on page 1.
- `single.php` (breadcrumb `sbek_breadcrumb()` — same trail as the BreadcrumbList JSON-LD via `sbek_breadcrumb_items()`; "Updated" date when modified >1h after publishing; cover image injected after the first paragraph by `inc/frontend/content.php`; Khmer "show word breaks" toggle when content contains U+200B; share bar: Facebook, Telegram, X, email, copy link, native share sheet when `navigator.share` exists), `page.php`, `404.php`, `sidebar.php` (`get_sidebar( null, [ 'boxed' => bool ] )`).
- Front-end JS modules (`theme.js`): menu (sticky bar, mobile toggle with scroll lock and outside-click close, search panel with a mobile search button, dropdowns, "priority+" overflow — items that would wrap move into the trailing `[data-sbek-menu-more]` "More" item from `sbek_menu_more_item()`; without JS the row wraps), ticker pause button, hero carousel (mobile: pause button + dots; `aria-live` is `off` while rotating, `polite` when stopped), prev/next pagers with a page counter and a status region, feeds, share/copy, word-break toggle.
- **No comments:** the theme has no comment UI, and `inc/setup.php` forces `comments_open`/`pings_open` to false (plus hides the comment feed) so nothing can be submitted that nobody would see. Existing comments stay in the database. To bring comments back, restore a `comments.php` + `comments_template()` call and drop those filters.
- Cards in `template-parts/card/` take a `WP_Post` via `$args['post']` (no global loop juggling).
- Helpers in `inc/template-tags.php`: `sbek_get_thumb()`, `sbek_get_post_image()`, `sbek_get_category_badge()`, `sbek_section_header()`, `sbek_primary_category()`, `sbek_term_color()`, `sbek_post_date()`, `sbek_get_logo()`, `sbek_ad_area()`. `sbek_get_*` return escaped HTML; others echo.
- Primary menu: `Sbek_Menu_Walker` (two levels; front-page item renders as the house icon; active state from core's `current-*` classes; non-Khmer labels get the Latin face). Locations `primary` (Khmer) and `primary-en`; `sbek_menu_fallback()` shows Home + categories until a menu is assigned.

### Security (`inc/security.php` + per module)

Security headers, no generator tag, XML-RPC off, `/wp/v2/users` hidden from anonymous visitors, login lockout (5 failures/IP → 15 min). Every form: nonce + capability check + sanitize on input (registered meta sanitizers) + escape on output — **one deliberate exception:** the public newsletter form has no nonce, because a nonce in a page-cached footer goes stale. Newsletter (`inc/frontend/newsletter.php`) is **double opt-in**: honeypot, same-origin check (`sbek_subscribe_same_origin()`), per-IP-hash throttle, then the address is stored `pending` (`_sbek_sub_status`) and a confirmation email is sent in the subscriber's language; `admin-post.php?action=sbek_newsletter&do=confirm|unsubscribe&id=&token=` (only a `wp_hash` of the token is stored) sets `confirmed` / `unsubscribed`. Rows without a status (pre-opt-in) count as confirmed. Mail only `confirmed` rows; `sbek_subscriber_unsubscribe_url( $id )` gives the one-click link for mailings. Private CPT readable only by `manage_options`, with Status/Language columns; CSV export via `admin-post.php?action=sbek_export_subscribers` (nonce, `manage_options`, formula-injection guarded, includes status). SEO (`inc/frontend/seo.php`, `sbek_seo_context()`): meta description + Open Graph/Twitter tags on posts, pages, the home page, category/tag archives and author pages; steps aside when Yoast/Rank Math/SEOPress/AIOSEO is active. Site-wide concerns (headers, lockout, analytics) would ideally live in a must-use plugin.

### Demo data (`inc/demo/class-sbek-demo-importer.php`, Sbek Theme → Tools, WP-CLI)

- `Sbek_Demo_Importer` builds/removes the demo set in **resumable batches** (setup → images 6/request → Khmer posts 20/request → English posts → pages → finish; removal: posts 25/request → images 10/request → cleanup), so no single web request can time out. Job state is server-side in the `sbek_demo_job` option; a single-flight lock (`sbek_demo_lock`, 3-minute stale timeout) stops parallel batches. Loaded only in wp-admin and WP-CLI.
- Everything created is appended to `sbek_demo_manifest` after **every** batch, so removal deletes exactly that (even after an interrupted import) and never pre-existing content. Removal keeps Sbek Theme and only clears logo / share image / site icon references that pointed at deleted images.
- With "site settings" on (the default) it also sets permalinks `/article/%post_id%/`, category base `topics`, tag base `article/tag`, the Khmer locale (downloads the core pack), the Phnom Penh timezone, 8 posts/page, the menus, the ad-placeholder widgets and the privacy page, and fills **only empty** Sbek Theme fields. Site options are not reverted on removal.
- **Admin:** the Tools tab (`inc/admin/tools.php`) drives it through admin-ajax `sbek_demo` (`task=start|next|abort`, nonce `sbek_demo`, `manage_options`). The JS loops `next` with a progress bar, and on failure offers **Continue** because the job persists.
- **CLI:** `wp sbek demo import [--skip-site-setup] [--posts=24]`, `wp sbek demo reset --yes`, `wp sbek demo status` (`inc/cli/class-sbek-demo-command.php` just loops the batches).
- **Reset design** (Design tab) posts to `admin-post.php?action=sbek_reset_design` (nonce, `manage_options`) and restores only the six design keys. Destructive buttons use the inline two-step `sbek_confirm_control()` instead of browser dialogs.

## Conventions

- Prefix everything `sbek_` / `_sbek_` / `Sbek_`; text domain `sbek`.
- Escape late (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); sanitize early; nonces + `current_user_can()` on every write.
- Keep templates free of queries where a helper in `inc/queries.php` fits; keep markup semantic and styles in `input.css`.
