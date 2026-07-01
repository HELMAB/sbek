<?php
/**
 * Multilingual system — no-plugin implementation.
 *
 * URL scheme:
 *   /         → English (default, canonical)
 *   /jp/...   → Japanese
 *   /en/...   → 301 redirect to canonical (drop /en/ prefix)
 */

define( 'SBEK_LANGUAGES',        [ 'en', 'jp' ] );
define( 'SBEK_DEFAULT_LANGUAGE', 'en' );

// ─── 1. Language detection & REQUEST_URI rewrite ──────────────────────────────
// Runs at init priority 1 — before WP::parse_request() is called.

add_action( 'init', function () {
    $uri       = $_SERVER['REQUEST_URI'] ?? '/';
    $site_path = rtrim( parse_url( home_url(), PHP_URL_PATH ) ?? '', '/' );
    $relative  = '/' . ltrim( substr( $uri, strlen( $site_path ) ), '/' );

    // Strip query string for matching
    $path_only = strtok( $relative, '?' );

    if ( preg_match( '#^/(jp|en)(/|$)(.*)#', $path_only, $matches ) ) {
        $lang = $matches[1];
        $rest = $matches[3];

        // /en/... → 301 to /...  (canonical has no prefix)
        if ( 'en' === $lang ) {
            $query   = isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING']
                ? '?' . $_SERVER['QUERY_STRING']
                : '';
            $target  = $rest ? "/{$rest}" : '/';
            wp_redirect( home_url( $target . $query ), 301 );
            exit;
        }

        $GLOBALS['sbek_lang'] = $lang;

        // Strip /jp prefix so WordPress parses the URL normally
        $query_string = isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING']
            ? '?' . $_SERVER['QUERY_STRING']
            : '';
        $stripped              = $rest ? "/{$rest}" : '/';
        $_SERVER['REQUEST_URI'] = $site_path . $stripped . $query_string;

    } else {
        $GLOBALS['sbek_lang'] = SBEK_DEFAULT_LANGUAGE;
    }
}, 1 );

// ─── 2. Language helpers ──────────────────────────────────────────────────────

function sbek_lang(): string {
    return $GLOBALS['sbek_lang'] ?? SBEK_DEFAULT_LANGUAGE;
}

function sbek_is_default_lang(): bool {
    return sbek_lang() === SBEK_DEFAULT_LANGUAGE;
}

/**
 * Prefix a URL with the language segment.
 * English URLs are returned as-is (no /en/ prefix — canonical).
 */
function sbek_localize_url( string $url, ?string $lang = null ): string {
    $lang ??= sbek_lang();

    if ( $lang === SBEK_DEFAULT_LANGUAGE ) {
        return $url;
    }

    $home     = rtrim( home_url( '/' ), '/' );
    $relative = ltrim( substr( $url, strlen( $home ) ), '/' );

    return $home . "/{$lang}/" . $relative;
}

// ─── 3. Register `language` taxonomy ─────────────────────────────────────────

add_action( 'init', function () {
    register_taxonomy( 'language', [ 'post', 'page' ], [
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'hierarchical'      => false,
        'labels'            => [
            'name'          => 'Languages',
            'singular_name' => 'Language',
            'add_new_item'  => 'Add Language',
        ],
        'rewrite'           => false,
    ] );

    foreach ( SBEK_LANGUAGES as $code ) {
        if ( ! term_exists( $code, 'language' ) ) {
            wp_insert_term( strtoupper( $code ), 'language', [ 'slug' => $code ] );
        }
    }
}, 5 );

/**
 * Default untagged posts/pages to the site's default language. Without this,
 * a post saved without ticking a Language box is silently excluded from every
 * front-end query (see the tax_query filter below) and gets redirected away
 * from its own permalink — invisible with no error to explain why.
 */
add_action( 'save_post', function ( int $post_id, WP_Post $post ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! in_array( $post->post_type, [ 'post', 'page' ], true ) ) {
        return;
    }

    $terms = wp_get_post_terms( $post_id, 'language', [ 'fields' => 'slugs' ] );

    if ( is_wp_error( $terms ) || ! empty( $terms ) ) {
        return;
    }

    wp_set_post_terms( $post_id, [ SBEK_DEFAULT_LANGUAGE ], 'language' );
}, 20, 2 );

// ─── 4. Filter all frontend queries by current language ───────────────────────

add_action( 'pre_get_posts', function ( WP_Query $query ) {
    if ( is_admin() ) {
        return;
    }

    // Only scope queries for post types that actually carry the `language`
    // taxonomy (post, page). Otherwise this would wipe out queries for
    // unrelated post types (e.g. nav_menu_item), which are never tagged
    // with a language term and would be filtered out entirely.
    $post_type = $query->get( 'post_type' );
    if ( empty( $post_type ) ) {
        $post_type = 'post';
    }

    if ( 'any' !== $post_type ) {
        foreach ( (array) $post_type as $queried_post_type ) {
            if ( ! is_object_in_taxonomy( $queried_post_type, 'language' ) ) {
                return;
            }
        }
    }

    $lang = sbek_lang();

    $existing = (array) $query->get( 'tax_query' );

    $query->set( 'tax_query', array_merge(
        $existing,
        [
            [
                'taxonomy' => 'language',
                'field'    => 'slug',
                'terms'    => [ $lang ],
            ],
        ]
    ) );
} );

// ─── 5. Redirect when post belongs to wrong language ─────────────────────────

add_action( 'template_redirect', function () {
    if ( ! is_singular() || is_front_page() ) {
        return; // Never redirect the front page to itself if it's untagged.
    }

    $post_id = get_the_ID();
    $lang    = sbek_lang();
    $terms   = wp_get_post_terms( $post_id, 'language', [ 'fields' => 'slugs' ] );

    if ( is_wp_error( $terms ) || in_array( $lang, $terms, true ) ) {
        return;
    }

    // Post not in current language → send to language homepage
    $home = $lang === SBEK_DEFAULT_LANGUAGE ? home_url( '/' ) : home_url( "/{$lang}/" );
    wp_redirect( $home, 302 );
    exit;
} );

// ─── 6. hreflang meta tags ────────────────────────────────────────────────────

add_action( 'wp_head', function () {
    if ( ! is_singular() ) {
        return;
    }

    $post_id = get_the_ID();
    $terms   = wp_get_post_terms( $post_id, 'language', [ 'fields' => 'slugs' ] );

    if ( is_wp_error( $terms ) ) {
        return;
    }

    $is_en      = in_array( 'en', $terms, true );
    $en_post_id = $is_en ? $post_id : (int) get_post_meta( $post_id, '_translation_id', true );
    $jp_post_id = $is_en ? (int) get_post_meta( $post_id, '_translation_id', true ) : $post_id;

    if ( $en_post_id ) {
        $en_url = sbek_localize_url( get_permalink( $en_post_id ), 'en' );
        printf( "<link rel=\"alternate\" hreflang=\"en\" href=\"%s\">\n", esc_url( $en_url ) );
        printf( "<link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\">\n", esc_url( $en_url ) );
    }

    if ( $jp_post_id ) {
        $jp_url = sbek_localize_url( get_permalink( $jp_post_id ), 'jp' );
        printf( "<link rel=\"alternate\" hreflang=\"ja\" href=\"%s\">\n", esc_url( $jp_url ) );
    }
}, 5 );

// ─── 7. Translation string helper (replaces __()) ────────────────────────────

function t( string $key, ...$args ): string {
    static $strings = null;

    if ( null === $strings ) {
        $strings = include get_template_directory() . '/i18n/strings.php';
    }

    $lang = sbek_lang();
    $text = $strings[ $lang ][ $key ]
        ?? $strings[ SBEK_DEFAULT_LANGUAGE ][ $key ]
        ?? $key;

    return $args ? sprintf( $text, ...$args ) : $text;
}

// ─── 8. Translation counterpart helper ───────────────────────────────────────

function sbek_get_translation( int $post_id, string $target_lang ): ?WP_Post {
    $translation_id = (int) get_post_meta( $post_id, '_translation_id', true );

    if ( ! $translation_id ) {
        return null;
    }

    $post  = get_post( $translation_id );
    $terms = wp_get_post_terms( $translation_id, 'language', [ 'fields' => 'slugs' ] );

    if ( $post && ! is_wp_error( $terms ) && in_array( $target_lang, $terms, true ) ) {
        return $post;
    }

    return null;
}

// ─── 9. Language switcher HTML ────────────────────────────────────────────────

function sbek_language_switcher(): string {
    $current_lang = sbek_lang();
    $post_id      = get_the_ID() ?: 0;
    $html         = '<div class="flex items-center space-x-1 text-sm font-bold">';

    foreach ( SBEK_LANGUAGES as $lang ) {
        $url = home_url( '/' );

        if ( $post_id ) {
            if ( $lang === $current_lang ) {
                $url = get_permalink( $post_id ) ?: home_url( '/' );
            } else {
                $translation = sbek_get_translation( $post_id, $lang );
                $url         = $translation
                    ? get_permalink( $translation->ID )
                    : home_url( '/' );
            }
        }

        $localized  = sbek_localize_url( $url, $lang );
        $is_active  = $lang === $current_lang;
        $label      = strtoupper( $lang );
        $active_cls = $is_active
            ? 'bg-sbek-red text-white px-2 py-0.5 rounded-sm'
            : 'text-gray-600 hover:text-sbek-red px-2 py-0.5 transition-colors';

        $html .= sprintf(
            '<a href="%s" class="%s"%s>%s</a>',
            esc_url( $localized ),
            esc_attr( $active_cls ),
            $is_active ? ' aria-current="true"' : '',
            esc_html( $label )
        );
    }

    $html .= '</div>';

    return $html;
}

// ─── 10. Localized term name helper ──────────────────────────────────────────

function sbek_term_name( WP_Term $term ): string {
    $lang = sbek_lang();

    if ( $lang !== SBEK_DEFAULT_LANGUAGE ) {
        $localized = get_term_meta( $term->term_id, "name_{$lang}", true );

        if ( $localized ) {
            return $localized;
        }
    }

    return $term->name;
}
