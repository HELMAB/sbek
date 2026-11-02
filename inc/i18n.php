<?php
/**
 * Bilingual front end (Khmer primary, English secondary) without a plugin.
 *
 * URL scheme:
 *   /...      Khmer (default language, canonical, no prefix)
 *   /en/...   English
 *   /km/...   301 → canonical URL without the prefix
 *
 * How it fits together:
 *   1. sbek_bootstrap_language() runs when this file is included (before WP
 *      parses the request): it reads the prefix, remembers the language and
 *      strips the prefix from REQUEST_URI so core routing works unchanged.
 *   2. The matching WordPress locale is activated with switch_to_locale(), so
 *      theme strings (__() with the `sbek` text domain) *and* core strings
 *      (month and day names, login screen…) follow the URL language.
 *   3. Link filters re-add the prefix to every permalink WordPress builds, so
 *      templates simply use get_permalink() / get_term_link().
 *   4. Content is split by the private `language` taxonomy; a pre_get_posts
 *      filter scopes every front-end query to the active language.
 *   5. Category/tag names are localized from `sbek_name_{lang}` term meta via
 *      a `get_term` filter (menus, badges and archive titles all pick it up).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_DEFAULT_LANGUAGE = 'km';

/**
 * Supported languages, keyed by URL slug. The first entry is not special —
 * SBEK_DEFAULT_LANGUAGE decides which one is served without a prefix.
 *
 * @return array<string,array{locale:string,hreflang:string,label:string}>
 */
function sbek_languages(): array {
	return array(
		'km' => array(
			'locale'   => 'km',
			'hreflang' => 'km',
			'label'    => 'ខ្មែរ',
		),
		'en' => array(
			'locale'   => 'en_US',
			'hreflang' => 'en',
			'label'    => 'English',
		),
	);
}

/**
 * Current front-end language slug (`km` | `en`).
 */
function sbek_lang(): string {
	return $GLOBALS['sbek_lang'] ?? SBEK_DEFAULT_LANGUAGE;
}

function sbek_is_default_lang(): bool {
	return SBEK_DEFAULT_LANGUAGE === sbek_lang();
}

/**
 * Whether this request is a routed public page view (where language prefixes,
 * locale switching and link localization apply).
 */
function sbek_is_routed_request(): bool {
	return ! empty( $GLOBALS['sbek_lang_routed'] );
}

// ─── 1. Language detection, REQUEST_URI rewrite, locale switch ─────────────────

/**
 * Detect the language from the URL prefix. Runs at include time because it
 * must rewrite REQUEST_URI before WP::parse_request() and switch the locale
 * before templates translate anything.
 */
function sbek_bootstrap_language(): void {
	$GLOBALS['sbek_lang'] = SBEK_DEFAULT_LANGUAGE;

	if (
		is_admin()
		|| wp_doing_ajax()
		|| wp_doing_cron()
		|| ( defined( 'WP_CLI' ) && WP_CLI )
		|| 'index.php' !== ( $GLOBALS['pagenow'] ?? '' )
		|| empty( $_SERVER['REQUEST_URI'] )
	) {
		return;
	}

	$GLOBALS['sbek_lang_routed'] = true;

	// Only matched against a strict pattern and re-assembled from its own
	// substrings — never echoed — so no sanitizing beyond unslash is needed.
	$uri       = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$home_path = trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );

	if ( 0 === strpos( $uri, $home_path )
		&& preg_match( '#^([a-z]{2})(?=/|\?|$)/?(.*)$#s', substr( $uri, strlen( $home_path ) ), $m )
		&& isset( sbek_languages()[ $m[1] ] )
	) {
		if ( SBEK_DEFAULT_LANGUAGE === $m[1] ) {
			wp_safe_redirect( home_url( '/' . ltrim( $m[2], '/' ) ), 301 );
			exit;
		}

		$GLOBALS['sbek_lang']   = $m[1];
		$_SERVER['REQUEST_URI'] = $home_path . $m[2];
	}

	$locale = sbek_languages()[ sbek_lang() ]['locale'];
	if ( determine_locale() !== $locale ) {
		// Fails silently when the locale's core pack isn't installed; the
		// theme_locale filter below still loads the theme's own translations.
		switch_to_locale( $locale );
	}
}
sbek_bootstrap_language();

/**
 * Always load the theme's translation for the URL language, even when the
 * matching core language pack is missing (switch_to_locale() refuses those).
 */
add_filter(
	'theme_locale',
	static function ( string $locale, string $domain ): string {
		return ( 'sbek' === $domain && sbek_is_routed_request() )
			? sbek_languages()[ sbek_lang() ]['locale']
			: $locale;
	},
	10,
	2
);

// ─── 2. URL helpers + link localization ────────────────────────────────────────

/**
 * Remove any language prefix from a site URL.
 */
function sbek_unlocalize_url( string $url ): string {
	$home = home_url( '/' );

	foreach ( array_keys( sbek_languages() ) as $lang ) {
		$prefix = $home . $lang;
		if ( 0 === strpos( $url, $prefix . '/' ) || $url === $prefix ) {
			return $home . ltrim( (string) substr( $url, strlen( $prefix ) ), '/' );
		}
	}

	return $url;
}

/**
 * Add the language prefix to a site URL (no-op for the default language and
 * for external URLs). Idempotent: an existing prefix is replaced.
 */
function sbek_localize_url( string $url, ?string $lang = null ): string {
	$lang ??= sbek_lang();
	$url    = sbek_unlocalize_url( $url );
	$home   = home_url( '/' );

	if ( SBEK_DEFAULT_LANGUAGE === $lang || 0 !== strpos( $url, $home ) ) {
		return $url;
	}

	return $home . $lang . '/' . substr( $url, strlen( $home ) );
}

/**
 * Home URL in the current (or given) language. Use instead of home_url('/')
 * in templates.
 */
function sbek_home_url( ?string $lang = null ): string {
	return sbek_localize_url( home_url( '/' ), $lang );
}

/**
 * Prefix every permalink WordPress builds on non-default-language pages.
 */
function sbek_register_link_filters(): void {
	if ( ! sbek_is_routed_request() || sbek_is_default_lang() ) {
		return;
	}

	$filters = array(
		'post_link',
		'page_link',
		'post_type_link',
		'attachment_link',
		'term_link',
		'author_link',
		'year_link',
		'month_link',
		'day_link',
		'search_link',
		'get_pagenum_link',
		'get_comments_pagenum_link',
		'post_type_archive_link',
	);

	foreach ( $filters as $filter ) {
		add_filter( $filter, 'sbek_localize_url_filter', 20 );
	}
}
add_action( 'after_setup_theme', 'sbek_register_link_filters' );

/**
 * Filter callback wrapper (link filters pass extra args we don't want).
 *
 * @param mixed $url URL from the filter.
 * @return mixed
 */
function sbek_localize_url_filter( $url ) {
	return is_string( $url ) ? sbek_localize_url( $url ) : $url;
}

/**
 * Canonical redirects compare the *stripped* REQUEST_URI with the
 * *prefixed* permalink and would loop forever on /en/ URLs. Suppress the
 * redirect when the only difference is our own prefix.
 *
 * @param string|false $redirect_url  Canonical URL WordPress wants.
 * @param string       $requested_url URL WordPress thinks was requested.
 * @return string|false
 */
function sbek_redirect_canonical( $redirect_url, $requested_url ) {
	if ( ! $redirect_url || ! sbek_is_routed_request() || sbek_is_default_lang() ) {
		return $redirect_url;
	}

	if ( untrailingslashit( sbek_localize_url( (string) $requested_url ) ) === untrailingslashit( $redirect_url ) ) {
		return false;
	}

	return $redirect_url;
}
add_filter( 'redirect_canonical', 'sbek_redirect_canonical', 10, 2 );

// ─── 3. `language` taxonomy ────────────────────────────────────────────────────

function sbek_register_language_taxonomy(): void {
	register_taxonomy(
		'language',
		array( 'post', 'page' ),
		array(
			'labels'             => array(
				'name'          => __( 'Languages', 'sbek' ),
				'singular_name' => __( 'Language', 'sbek' ),
				'menu_name'     => __( 'Languages', 'sbek' ),
			),
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // Fixed list — nothing to manage.
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,  // Block-editor sidebar panel.
			'show_admin_column'  => true,
			'hierarchical'       => true,  // Checkbox UI instead of free-text tags.
			'rewrite'            => false,
			'query_var'          => false,
			'capabilities'       => array(
				'manage_terms' => 'manage_options',
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_posts',
			),
		)
	);
}
add_action( 'init', 'sbek_register_language_taxonomy', 5 );

/**
 * Create the language terms once (on theme switch / version bump) instead of
 * running term_exists() queries on every request.
 */
function sbek_ensure_language_terms(): void {
	foreach ( sbek_languages() as $slug => $language ) {
		if ( ! term_exists( $slug, 'language' ) ) {
			wp_insert_term( $language['label'], 'language', array( 'slug' => $slug ) );
		}
	}
}

/**
 * Posts saved without a language would vanish from every front-end query
 * (see the pre_get_posts filter) — default them to the primary language.
 */
function sbek_default_post_language( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! is_object_in_taxonomy( $post->post_type, 'language' ) ) {
		return;
	}

	$terms = wp_get_post_terms( $post_id, 'language', array( 'fields' => 'ids' ) );

	if ( is_wp_error( $terms ) || ! empty( $terms ) ) {
		return;
	}

	// wp_set_object_terms() (not wp_set_post_terms()) — the taxonomy is
	// hierarchical, and wp_set_post_terms() would cast the slug to int 0.
	wp_set_object_terms( $post_id, array( SBEK_DEFAULT_LANGUAGE ), 'language' );
}
add_action( 'save_post', 'sbek_default_post_language', 20, 2 );

/**
 * Language slug of a post, or '' if untagged.
 */
function sbek_post_language( int $post_id ): string {
	$terms = get_the_terms( $post_id, 'language' );

	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
}

// ─── 4. Scope front-end queries to the current language ────────────────────────

function sbek_filter_query_by_language( WP_Query $query ): void {
	if ( is_admin() || ! sbek_is_routed_request() || $query->get( 'sbek_all_languages' ) ) {
		return;
	}

	// Only post types that carry the taxonomy; anything else (nav_menu_item,
	// attachments, custom types) would otherwise be filtered out entirely.
	$post_types = $query->get( 'post_type' );
	if ( empty( $post_types ) ) {
		$post_types = $query->is_attachment() ? 'attachment' : 'post';
	}

	if ( 'any' !== $post_types ) {
		foreach ( (array) $post_types as $post_type ) {
			if ( ! is_object_in_taxonomy( $post_type, 'language' ) ) {
				return;
			}
		}
	}

	$tax_query   = (array) $query->get( 'tax_query' );
	$tax_query[] = array(
		'taxonomy' => 'language',
		'field'    => 'slug',
		'terms'    => array( sbek_lang() ),
	);
	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'sbek_filter_query_by_language' );

/**
 * A post requested in the wrong language goes to its translation, or to that
 * language's home page when it has none.
 */
function sbek_redirect_wrong_language(): void {
	if ( ! is_singular() || is_front_page() ) {
		return;
	}

	$post_id   = get_queried_object_id();
	$post_lang = sbek_post_language( $post_id );

	if ( ! $post_lang || sbek_lang() === $post_lang ) {
		return;
	}

	$translation = sbek_get_translation( $post_id, sbek_lang() );
	$target      = $translation ? get_permalink( $translation ) : sbek_home_url();

	wp_safe_redirect( $target, 302 );
	exit;
}
add_action( 'template_redirect', 'sbek_redirect_wrong_language' );

// ─── 5. Translations (pairs linked by `_sbek_translation_id`) ──────────────────

/**
 * The linked translation of a post in the given language, if any.
 */
function sbek_get_translation( int $post_id, string $lang ): ?WP_Post {
	$translation_id = (int) get_post_meta( $post_id, '_sbek_translation_id', true );

	if ( ! $translation_id || $translation_id === $post_id ) {
		return null;
	}

	$post = get_post( $translation_id );

	if ( ! $post || 'publish' !== $post->post_status || sbek_post_language( $translation_id ) !== $lang ) {
		return null;
	}

	return $post;
}

/**
 * hreflang alternates for singular views (and the home page).
 */
function sbek_hreflang_links(): void {
	$alternates = array();

	if ( is_front_page() || is_home() ) {
		foreach ( array_keys( sbek_languages() ) as $lang ) {
			$alternates[ $lang ] = sbek_home_url( $lang );
		}
	} elseif ( is_singular() ) {
		$post_id   = get_queried_object_id();
		$post_lang = sbek_post_language( $post_id ) ?: SBEK_DEFAULT_LANGUAGE;

		$alternates[ $post_lang ] = get_permalink( $post_id );

		foreach ( array_keys( sbek_languages() ) as $lang ) {
			$translation = $lang !== $post_lang ? sbek_get_translation( $post_id, $lang ) : null;
			if ( $translation ) {
				$alternates[ $lang ] = sbek_localize_url( sbek_unlocalize_url( get_permalink( $translation ) ), $lang );
			}
		}

		if ( count( $alternates ) < 2 ) {
			return; // No translation — a lone self-reference adds nothing.
		}
	} else {
		return;
	}

	foreach ( $alternates as $lang => $url ) {
		printf(
			'<link rel="alternate" hreflang="%s" href="%s">' . "\n",
			esc_attr( sbek_languages()[ $lang ]['hreflang'] ),
			esc_url( $url )
		);
	}

	if ( isset( $alternates[ SBEK_DEFAULT_LANGUAGE ] ) ) {
		printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( $alternates[ SBEK_DEFAULT_LANGUAGE ] ) );
	}
}
add_action( 'wp_head', 'sbek_hreflang_links', 5 );

/**
 * Language switcher data: one entry per language with the best target URL —
 * the linked translation on singular views, the same URL with the other
 * prefix on archives (terms are shared across languages), else the home page.
 *
 * @return array<string,array{url:string,label:string,active:bool}>
 */
function sbek_language_switcher_items(): array {
	$items = array();

	foreach ( sbek_languages() as $lang => $language ) {
		$active = sbek_lang() === $lang;

		if ( $active ) {
			$url = sbek_localize_url( sbek_current_url(), $lang );
		} elseif ( is_singular() && ! is_front_page() ) {
			$translation = sbek_get_translation( get_queried_object_id(), $lang );
			$url         = $translation
				? sbek_localize_url( sbek_unlocalize_url( get_permalink( $translation ) ), $lang )
				: sbek_home_url( $lang );
		} elseif ( is_404() || is_search() ) {
			$url = sbek_home_url( $lang );
		} else {
			$url = sbek_localize_url( sbek_current_url(), $lang );
		}

		$items[ $lang ] = array(
			'url'    => $url,
			'label'  => $language['label'],
			'active' => $active,
		);
	}

	return $items;
}

/**
 * The current page URL without query string (unprefixed path as WordPress
 * routed it).
 */
function sbek_current_url(): string {
	global $wp;

	$path = (string) ( $wp->request ?? '' );

	return home_url( '/' . ( '' !== $path ? user_trailingslashit( $path ) : '' ) );
}

// ─── 6. Localized category / tag names ─────────────────────────────────────────

/**
 * Swap category/tag names for their `sbek_name_{lang}` translation on
 * non-default-language pages. get_term() runs for every term WordPress loads
 * (WP_Term_Query, wp_get_object_terms, nav menu items), so this one filter
 * covers menus, badges, archive titles and breadcrumbs.
 *
 * @param mixed $term Term object from get_term().
 * @return mixed
 */
function sbek_localize_term_name( $term ) {
	if (
		$term instanceof WP_Term
		&& in_array( $term->taxonomy, array( 'category', 'post_tag' ), true )
		&& sbek_is_routed_request()
		&& ! sbek_is_default_lang()
	) {
		$name = get_term_meta( $term->term_id, 'sbek_name_' . sbek_lang(), true );
		if ( is_string( $name ) && '' !== $name ) {
			$term->name = $name;
		}
	}

	return $term;
}
add_filter( 'get_term', 'sbek_localize_term_name' );

/**
 * English site title / tagline (Sbek Theme → General) on /en/ pages.
 * Affects <title>, the logo alt text, the footer and structured data.
 *
 * @param mixed  $value  Option value.
 * @param string $option Option name.
 * @return mixed
 */
function sbek_localize_site_identity( $value, string $option ) {
	if ( ! sbek_is_routed_request() || sbek_is_default_lang() || ! function_exists( 'sbek_site_setting' ) ) {
		return $value;
	}

	$key        = 'blogname' === $option ? 'name_' : 'tagline_';
	$translated = trim( (string) sbek_site_setting( $key . sbek_lang(), '' ) );

	return '' !== $translated ? $translated : $value;
}
add_filter( 'option_blogname', 'sbek_localize_site_identity', 10, 2 );
add_filter( 'option_blogdescription', 'sbek_localize_site_identity', 10, 2 );

/**
 * Read a site setting with a per-language override (e.g. `description_en`).
 */
function sbek_site_text( string $key ): string {
	if ( ! function_exists( 'sbek_site_setting' ) ) {
		return '';
	}

	if ( ! sbek_is_default_lang() ) {
		$localized = (string) sbek_site_setting( $key . '_' . sbek_lang(), '' );
		if ( '' !== trim( $localized ) ) {
			return $localized;
		}
	}

	return (string) sbek_site_setting( $key, '' );
}
