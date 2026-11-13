# CLAUDE.md

Guidance for Claude Code (claude.ai/code) when working in this repository.

## What this is

**Sbek** is a custom classic WordPress theme for a news site, Khmer first. Its front end ports the Sabay-News-style layout study in `../../../sabay-news-ui` (Next.js): measurements, breakpoints and behaviour come from there. This repo *is* the theme; WordPress core lives outside it.

**Theme vs. plugin.** The theme is meant for the WordPress.org directory, so it holds **presentation only**: templates, the Appearance → Sbek Theme settings page (design + display data), menus, widget areas, comments. Everything WordPress.org calls *plugin territory* lives in the companion plugin **Sbek Companion** — `../../plugins/sbek-companion` (its own git repo): Khmer/English URL routing, the `language` taxonomy and linked translations, English texts, the News details post meta, share buttons, the newsletter (custom post type), SEO / JSON-LD, GA4 / GTM analytics, security hardening and login lockout, login-screen branding, query caching, the dashboard checklist and the demo importer + `wp sbek demo` CLI. The theme must keep working **without** the plugin (one language, no plugin features); the plugin only loads while Sbek (or a child theme) is active. Before adding a feature, decide which side it belongs on: if it stores content or changes site behaviour rather than how things look, it goes in the plugin.

## Build & development

```bash
nvm use            # Node 22 (.nvmrc); Tailwind v4 needs Node 20+
npm install
npm run build        # input.css → assets/css/main.css, then editor.css (both minified)
npm run watch
npm run build:admin  # admin.css → assets/css/admin.css (Sbek Theme page + plugin's dashboard widget)
bin/release.sh       # checks + build + dist/sbek-<version>.zip for WordPress.org
```

- `main.css` / `admin.css` / `editor.css` are gitignored build output — rebuild after editing CSS **or** adding Tailwind utility classes to PHP.
- `input.css` uses `@import "tailwindcss" source(none)` with explicit `@source` paths (front-end templates only). Components are **plain CSS in `@layer components`** with semantic class names (`.hero-item`, `.section-tab`, `.feed-item`…); Tailwind supplies preflight + utilities. Breakpoints are Bootstrap 3's (768/992/1200) and `container` is redefined via `@utility container` (750/970/1170px). Styles for plugin output (`.share-links`, `.newsletter`, `.lang-switch`, `.article__video`) also live here.
- `editor.css` (`assets/css/src/editor.css`, loaded with `add_editor_style()`) gives the block editor the article fonts and colors; `sbek_editor_design()` adds the Design variables to the editor.
- Fonts are self-hosted Khmer subsets (`assets/fonts/`, OFL, licenses in `assets/fonts/licenses/`). `@font-face` rules live in `assets/css/src/fonts.css`, imported by `input.css`, `admin.css` and `editor.css`; `sbek_font_face_css( $family )` returns one family's rules with absolute URLs for pages without main.css (the plugin's login screen). Stacks are **Khmer family first** (its Khmer-only `unicode-range` lets Latin fall through to the Latin stack).
- JS: one deferred vanilla file, `assets/js/theme.js` — modules activate on `data-sbek-*` hooks. No bundler. A tiny inline `<head>` script (`sbek_head_boot_script()`, `inc/frontend/assets.php`) adds `sbek-js` to `<html>` and restores the saved feed view as `sbek-grid` before first paint — grid-view CSS keys off `.sbek-grid .feed`. Admin: `assets/js/admin-theme-options.js`. (The share-button, demo, translation-box and news-meta scripts are in the plugin's `assets/js/`.)
- Translations (theme, domain `sbek`): after changing strings run
  `wp i18n make-pot . languages/sbek.pot --domain=sbek --exclude=node_modules,assets/css,dist,docs`,
  `msgmerge -N -U languages/km.po languages/sbek.pot` and translate the new entries, then `wp i18n make-mo languages/km.po languages/ && wp i18n make-php languages/km.po languages/`. The plugin does the same with domain `sbek-companion` in its own `languages/` (`sbek-companion-km.po`).
- No test suite / linter is configured. Smoke-test with `php -l`, `wp eval`, and by crawling pages with `WP_DEBUG_LOG` on — **with and without the plugin active**. Run Theme Check on the release zip (see `bin/release.sh` output). `php -l` does not catch a missing `<?php` tag — check that too after rewriting a file.

## Architecture

`functions.php` requires `inc/language.php` first (its fallbacks are used everywhere), then the rest. `inc/admin/` = wp-admin screens, `inc/frontend/` = public-site code, `inc/` root = shared.

### Extension points the plugin uses (keep them stable)

| Hook | Kind | Where | Plugin uses it for |
| --- | --- | --- | --- |
| `sbek_lang()`, `sbek_is_default_lang()`, `sbek_home_url()`, `sbek_localize_url()`, `sbek_term_description()`, `sbek_site_text()` | pluggable functions | `inc/language.php` (single-language fallbacks in `function_exists()`) | URL language, English texts |
| `sbek_menu_location` | filter | `sbek_menu_location( 'primary'\|'footer' )` | `primary-en` / `footer-en` |
| `sbek_menu_tools` | action | header, after the search button | language switcher |
| `sbek_footer_about` | action | footer "About us" column | newsletter form |
| `sbek_before_article_content` / `sbek_article_footer` | actions ( `WP_Post` ) | `single.php` | video embed / source line + share buttons |
| `sbek_is_sponsored` / `sbek_has_lead_video` | filters ( `false`, `WP_Post` ) | `inc/template-tags.php` (`sbek_is_sponsored()` / `sbek_article_cover()`) | sponsored labels / skip the cover image |
| `sbek_pre_post_ids` / `sbek_post_ids_queried` | filter / action | `sbek_post_ids()` in `inc/queries.php` | transient cache of block queries |
| `sbek_term_form_fields` | filter | `inc/admin/term-meta.php` | English term name / description fields |
| `sbek_theme_option_tabs`, `sbek_theme_options_panel_{tab}`, `sbek_theme_options_enqueue`, `sbek_theme_options_save`, `sbek_theme_options_saved` | filter / actions | `inc/admin/theme-options.php` | English, Integrations, Tools tabs; Sharing, Location, Newsletter cards |

`sbek_post_ids()` without the plugin runs every query uncached. `sbek_lang()` without the plugin is the site locale's two-letter code; `sbek_is_default_lang()` is always true.

### Languages (plugin: `includes/i18n.php`, `includes/admin/translation.php`, `includes/term-names.php`) — read before touching routing, queries or links

- Languages come from `sbek_languages()` (`km` → locale `km`, `en` → `en_US`); default `SBEK_DEFAULT_LANGUAGE = 'km'`.
- **URLs:** `/` = Khmer (canonical), `/en/…` = English, `/km/…` 301s to canonical. `sbek_bootstrap_language()` runs when the plugin file is included (before WordPress parses the request): reads the prefix, sets `$GLOBALS['sbek_lang']`, strips the prefix from `$_SERVER['REQUEST_URI']` so core routes normally. The `/km/` redirect waits for `plugins_loaded` (`wp_safe_redirect()` is pluggable). `sbek_switch_to_url_locale()` calls `switch_to_locale()` on `after_setup_theme` priority 0 (the locale switcher doesn't exist earlier), so **theme, plugin and core strings follow the URL**; `theme_locale` / `plugin_locale` filters load the `sbek` / `sbek-companion` translations even without a core language pack. Only for front-end `index.php` requests (not admin/AJAX/cron/CLI/login).
- The plugin's versions of the six language helpers win because plugins load first; they are still wrapped in `function_exists()` because on the plugin-activation request WordPress includes the plugin *after* the theme.
- **UI strings:** plain `__()` / `esc_html__()`; English source. Theme domain `sbek` (`languages/km.po`), plugin domain `sbek-companion`.
- **Links:** on non-default-language pages, link filters (`post_link`, `term_link`, `get_pagenum_link`, …) add the prefix automatically — templates use `get_permalink()` / `get_term_link()` directly. Use `sbek_home_url()` instead of `home_url('/')`, and `sbek_localize_url()` only for hand-built URLs (menu walker custom links; the plugin localizes footer custom links). `sbek_redirect_canonical()` stops core's canonical redirect from looping on prefixed URLs.
- **Content:** private, hierarchical `language` taxonomy (km/en, `show_in_rest` for the editor panel). `pre_get_posts` scopes every front-end query to the current language (opt out with query var `sbek_all_languages => true`; `sbek_posts_by_ids()` sets it). A post requested in the wrong language redirects to its translation or that language's home. Posts saved without a language get the default (`wp_set_object_terms` — not `wp_set_post_terms`, which would cast slugs to 0 on a hierarchical taxonomy).
- **Translations:** `_sbek_translation_id` post meta on both posts, managed by the Translation meta box; `sbek_set_translation()` keeps pairs symmetric and releases previous partners. Drives hreflang and the header switcher. The box lists the 100 most recent other-language posts plus an admin-ajax title search (`sbek_translation_search`), and offers **Create the {language} version** (`admin-post.php?action=sbek_create_translation`, nonce per post). Posts/Pages lists have a language filter (`?sbek_language=km|en|none`).
- **Term names:** category/tag names are Khmer; English goes in term meta `sbek_name_en` (category table on the English tab, or the term screen). A `get_term` filter swaps names on English pages. English descriptions: `sbek_description_en`, read with `sbek_term_description()` (returns '' rather than Khmer on English pages).
- **English texts:** plugin option `sbek_companion` keys `name_en`, `tagline_en`, `description_en`, `address_en`, `copyright_en`; `sbek_site_text( $key )` returns them on English pages, else the theme setting. English title/tagline apply on /en/ via `option_blogname`/`option_blogdescription` filters.
- **hreflang** (`sbek_hreflang_links()`): home page, singular views with a linked translation, and category/tag archives when the term has posts in the other language (`sbek_term_has_posts_in()`).
- Adding a language: add it to `sbek_languages()`, ship translation files for both domains, and install its core language pack (menu locations follow automatically).

### Content model

- **Categories / tags** (theme `inc/admin/term-meta.php`): `sbek_color` (accent; badges, tab headers, arrows; unset = follows the brand color) and for categories `sbek_home_order` (1, 2, 3… = homepage block order; none set → first top-level categories). At most `SBEK_HOME_BLOCK_LIMIT` (6, `inc/queries.php`) blocks; empty categories are skipped. Plugins add fields via `sbek_term_form_fields`; the theme renders and saves whatever is registered.
- **Post meta** (plugin `includes/admin/post-meta.php`, "News details" box): `_sbek_trending_score` (0–100, "Popular" — only posts from the last `popular_days` compete), `_sbek_video_url` (oEmbed above the article; saving one sets the *Video* format if the post has no format), `_sbek_source_name` / `_sbek_source_url`, `_sbek_is_sponsored`, `_sbek_is_breaking` (header ticker; expires `breaking_hours` after publication). The theme only **reads** `_sbek_trending_score` / `_sbek_is_breaking` in its queries; without them Popular = latest and the ticker hides.
- **Homepage hero** = sticky posts (then latest). **Video block** = posts in the *Video* post format.
- **Logo** = core `custom_logo` theme mod (theme support `custom-logo`). `sbek_migrate_logo()` moved the old `logo` setting over once.
- **Header image** = core `custom-header` (no header text, Customizer only): `sbek_header_image_css()` (`inc/design.php`) sets it as the `.top-bar` background over `--color-top`. The top bar is hidden below 768px, so the image is desktop only.
- **Block styles / patterns** (`inc/block-editor.php`): `is-style-sbek-{fact-box,rule,large,key-points,brand,framed}` on core group/heading/quote/list/separator/image, styled in **both** `input.css` (scoped to `.entry-content`) and `src/editor.css`; patterns `sbek/{key-points,fact-box,read-also,quote,editor-note}` in the `sbek` category, built from those styles. Pattern text is translatable — rerun the i18n steps after editing it.
- **Comments:** `comments.php` (threaded list, pagination, `comment_form()` with `.btn`), on articles and pages when open or non-empty; `comment-reply` is enqueued where threading applies.
- **Sbek Theme** (theme `inc/admin/theme-options.php`, **Appearance → Sbek Theme**, `themes.php?page=sbek-theme-options`, capability `edit_theme_options`). Theme tabs — **General** (core title/tagline/site icon, only for `manage_options` users and labelled as WordPress settings; custom logo), **Design** (colors and fonts, see below), **Homepage** (`show_ticker`/`show_video`; Freshness `breaking_hours` (48) / `popular_days` (7), 0 = no limit; `show_byline`; category table for `sbek_color` / `sbek_home_order` with an "On homepage / Hidden: …" status), **Contact & Footer** (description, email, phones, address, copyright with `{year}`/`{site}`), **Social**. The plugin adds **English**, **Integrations** (GA4/GTM IDs), **Tools** (`no_save`) and cards (Sharing image on General; Location + Newsletter on Contact).
  - The theme stores **one** option, `sbek_site_settings` (WordPress.org rule), sanitized by `sbek_sanitize_site_settings()` (registered on `init`, so `update_option()` from anywhere is sanitized; missing toggle keys default to on). The plugin stores `sbek_companion` (+ `sbek_cache_version`, `sbek_companion_version`).
  - Saves via `admin-post.php?action=sbek_save_theme_options` (nonce + `edit_theme_options`): theme option, custom logo, core options (manage_options only), term meta, then the `sbek_theme_options_save` filter for plugins, then `sbek_theme_options_saved`. Values the sanitizers would drop are listed (`sbek_settings_input_problems()`, `sbek_site_icon_problem()`, plugin `sbek_companion_input_problems()`) and shown as "not saved" after the redirect (per-user transient). Category colors post a `color_auto` flag: `1` deletes `sbek_color` so the category keeps following the brand color.
  - Read with `sbek_site_setting( $key )`, `sbek_site_text( $key )`, `sbek_section_enabled( 'ticker'|'video'|'byline' )`, `sbek_copyright_text()`.
  - Admin assets: `assets/js/admin-theme-options.js` (tabs with hash + ARIA, panels with `data-sbek-no-save` hide the Save bar, repeaters with reorder, live social icons, media pickers, inline confirmations, unsaved-changes tracking against a form snapshot + `beforeunload`) and `admin.css` (`npm run build:admin`; registered as `sbek-admin` on every admin page so the plugin can enqueue it). An inline boot script hides non-active panels before the JS loads. The page has a `.wp-header-end` marker so core places notices below the header.
  - Admin colors come from CSS variables `--sbek-accent` / `--sbek-accent-dark` / `--sbek-on-accent`, set inline by `sbek_admin_accent_style()`. Don't hard-code red/blue in `admin.css`.
  - **Reset design** (`inc/admin/design-reset.php`) posts to `admin-post.php?action=sbek_reset_design` (nonce, `edit_theme_options`) and restores only the six design keys. Destructive buttons use the inline two-step `sbek_confirm_control()` instead of browser dialogs.
- **Menus**: theme locations `primary` (two levels: dropdowns open on hover on desktop and via the chevron `[data-sbek-submenu-toggle]` buttons for keyboard/touch) and `footer` (one level; until assigned, the Privacy Policy link shows in the brand column). The plugin adds `primary-en` / `footer-en`.
- **Widget areas**: `sbek-sidebar`, `sbek-ad-header` (728×90), `sbek-ad-hero` (2×300×250), `sbek-ad-video` (300×250). Empty ad areas render nothing and the layout reclaims the space. (Ad-blockers hide `.ad-area` — expected.)

### Design: colors & fonts (`inc/design.php`, Sbek Theme → Design)

- Settings `color_brand`, `color_top`, `color_footer`, `font_body`, `font_heading`, `font_latin` (in `sbek_site_settings`). `sbek_design()` returns them validated, plus a derived `brand_dark` (the default red keeps its hand-picked `#d90c16`; custom colors are mixed 15% toward black).
- **The stylesheet stays static.** `sbek_design_css()` prints one inline `:root{…}` block (attached to `sbek-main`, and to the block editor) overriding CSS variables: `--color-menu`, `--color-menu-active`, `--color-rule`, `--color-on-menu`, `--color-top`, `--color-on-top`, `--color-footer`, `--color-footer-fg`, `--color-footer-strong`, `--font-sans`, `--font-strong`, `--font-strong-weight`. Defaults for all of them also exist in `input.css`. Only validated hex values and whitelisted font families go into it.
- `sbek_readable_on()` picks text on a colored surface: white if it reaches 3:1 (WCAG AA for large/bold UI text), else `#111`. The admin preview JS mirrors this math exactly.
- **Never hard-code the brand red** in CSS or PHP: use `var(--color-menu)` / `var(--color-on-menu)` in CSS and `sbek_brand_color()` in PHP.
- Fonts come from `sbek_font_choices()` (role `body` / `heading`; `display` fonts render headings at weight 400, text fonts at 700) and `sbek_latin_font_choices()` (system stacks only). The selected body font is preloaded when `sbek_lang()` is `km`.
- **Login screen** (plugin `includes/admin/login.php` + hand-written `assets/css/login.css`): grid layout — brand panel left from 900px, form column right with core's language switcher under it; one column with a brand strip below 900px. `sbek_login_design()` sets the `--sbek-*` tokens on `body.login` from the theme's Design (button text via `sbek_readable_on()`, panel text at 4.5:1) and inlines the body font via `sbek_font_face_css()`. Site name, home link and privacy link follow the login screen's locale (`sbek_login_language()`).

### Queries (`inc/queries.php`)

`sbek_post_ids( $name, $args, $ttl )` runs an ID-only query (or returns what `sbek_pre_post_ids` supplies) and announces the result on `sbek_post_ids_queried`; `sbek_posts_by_ids()` loads the posts in one query and primes thumbnail caches. The plugin (`includes/cache.php`) caches the ID lists in transients keyed `sbek_q_{name}_{lang}_v{version}`; the version (`sbek_cache_version` option) is bumped on post save/status change, sticky changes, term edits and settings saves — there is no list of keys to flush; new queries just need a unique `$name`. Block helpers: `sbek_get_featured_posts()`, `sbek_get_video_posts()`, `sbek_get_category_posts()`, `sbek_get_popular_posts()`, `sbek_get_latest_posts()`, `sbek_get_related_posts()`, `sbek_get_breaking_posts()`, `sbek_get_home_categories()`.

### Templates

- `front-page.php` → `template-parts/home/{featured,video,category}.php`. The hero is **one** set of tiles: CSS grid ≥768px, JS carousel below.
- `archive.php`, `search.php`, `index.php` → `template-parts/feed.php` (2 lead + list; list/grid switch; infinite scroll = `theme.js` fetching the `rel=next` pagination page (same-origin) and appending its `[data-feed-item]` cards — no AJAX endpoint; plain pagination remains as the no-JS fallback). After `AUTO_PAGES` (2) automatic loads it shows a **Load more** button so the footer stays reachable; the URL follows via `history.replaceState`, an end-of-list note and a `role=status` count are shown. `archive.php` passes an `intro` (term description + subcategory links, or the author's avatar and bio) on page 1.
- `searchform.php` has two variants: the compact box (menu panel, 404) and `get_search_form( array( 'sbek_variant' => 'filter' ) )` — box, category filter and result count — used by `search.php`. Don't hand-build search forms in templates (Theme Check).
- `single.php` (breadcrumb `sbek_breadcrumb()` — same trail the plugin's BreadcrumbList JSON-LD uses via `sbek_breadcrumb_items()`; "Updated" date when modified >1h after publishing; cover image above the text via `sbek_article_cover()` — never inject markup into `the_content` (WordPress.org review); Khmer "show word breaks" toggle when content contains U+200B; byline; `sbek_article_footer` hook; tags; related; comments), `page.php` (+ comments), `404.php`, `sidebar.php` (`get_sidebar( null, [ 'boxed' => bool ] )`).
- Front-end JS modules (`theme.js`): menu (sticky bar, mobile toggle with scroll lock and outside-click close, search panel with a mobile search button, dropdowns, "priority+" overflow — items that would wrap move into the trailing `[data-sbek-menu-more]` "More" item from `sbek_menu_more_item()`; without JS the row wraps), ticker pause button, hero carousel (mobile: pause button + dots; `aria-live` is `off` while rotating, `polite` when stopped), prev/next pagers with a page counter and a status region, feeds, word-break toggle.
- Cards in `template-parts/card/` take a `WP_Post` via `$args['post']` (no global loop juggling).
- Helpers in `inc/template-tags.php`: `sbek_get_thumb()`, `sbek_get_post_image()`, `sbek_get_category_badge()`, `sbek_section_header()`, `sbek_primary_category()`, `sbek_term_color()`, `sbek_is_sponsored()`, `sbek_post_date()`, `sbek_get_logo()`, `sbek_ad_area()`. `sbek_get_*` return escaped HTML; others echo.
- Primary menu: `Sbek_Menu_Walker` (two levels; front-page item renders as the house icon; active state from core's `current-*` classes; non-Khmer labels get the Latin face). `sbek_menu_fallback()` shows Home + categories until a menu is assigned.

### Plugin-only areas (`../../plugins/sbek-companion`)

- **Security** (`includes/security.php`): security headers, no generator tag, XML-RPC off, `/wp/v2/users` hidden from anonymous visitors, login lockout (5 failures/IP → 15 min), no emoji polyfill.
- **Newsletter** (`includes/frontend/newsletter.php`, `templates/newsletter-form.php`, toggle `show_newsletter`): **double opt-in** — honeypot, same-origin check (`sbek_subscribe_same_origin()`), per-IP-hash throttle, stored `pending` (`_sbek_sub_status`) in the private `sbek_subscriber` CPT, confirmation email in the subscriber's language; `admin-post.php?action=sbek_newsletter&do=confirm|unsubscribe&id=&token=` (only a `wp_hash` of the token is stored). The public form has **no nonce on purpose** (page caches). CSV export `admin-post.php?action=sbek_export_subscribers` (nonce, `manage_options`, formula-injection guarded).
- **SEO** (`includes/frontend/seo.php`, `sbek_seo_context()`): meta description + Open Graph/Twitter tags, Organization / NewsArticle / BreadcrumbList JSON-LD; steps aside when Yoast/Rank Math/SEOPress/AIOSEO is active. Share image `og_image` and coordinates `lat`/`lng` are plugin settings.
- **Integrations** (`includes/frontend/integrations.php`): GA4 / GTM, both enqueued with `wp_enqueue_script()` (nothing loads until an ID is set). Only IDs are stored — WordPress.org does not allow fields that accept arbitrary HTML/JS, so never add a "custom code" box.
- **Demo data** (`includes/demo/class-sbek-demo-importer.php`, Tools tab, `wp sbek demo import|reset --yes|status`): resumable batches (setup → images 6/request → Khmer posts 20/request → English posts → pages → finish; removal: posts 25/request → images 10/request → cleanup); job state in `sbek_demo_job`, single-flight lock `sbek_demo_lock`, everything created recorded in `sbek_demo_manifest` after every batch so removal deletes exactly that. With "site settings" on it also sets permalinks `/article/%post_id%/`, category base `topics`, tag base `article/tag`, the Khmer locale, the Phnom Penh timezone, 8 posts/page, menus, ad-placeholder widgets, the privacy page, the custom logo, and fills **only empty** theme / plugin settings. The Tools tab drives it through admin-ajax `sbek_demo` (`task=start|next|abort`, nonce `sbek_demo`, `manage_options`) with `assets/js/admin-demo.js`.

## Conventions

- Prefix everything `sbek_` / `_sbek_` / `Sbek_`; text domain `sbek` (plugin: `sbek-companion`).
- Escape late (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); sanitize early; nonces + `current_user_can()` on every write (theme: `edit_theme_options`).
- Keep templates free of queries where a helper in `inc/queries.php` fits; keep markup semantic and styles in `input.css`.
- The theme may only add **one** option (`sbek_site_settings`), may not register post types / taxonomies / shortcodes, remove core `wp_head` output, or load anything from other sites. When in doubt, put it in the plugin behind a theme hook.
