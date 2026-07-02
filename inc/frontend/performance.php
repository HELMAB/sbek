<?php
/**
 * Performance optimizations for Sbek
 * Handles Transients and Caching logic.
 */

/**
 * Get cached query or run a new one.
 * 
 * @param array  $args           WP_Query arguments.
 * @param string $transient_name Unique name for the cache.
 * @param int    $expiration     Time in seconds.
 * @return WP_Query
 */
function sbek_get_cached_query( $args, $transient_name, $expiration = 900 ) {
    // Check if transient exists
    $cached_ids = get_transient( $transient_name );

    if ( false === $cached_ids ) {
        // Run the heavy query only to get IDs
        $args['fields'] = 'ids'; // Optimization: only fetch IDs
        $query = new WP_Query( $args );
        
        $cached_ids = $query->posts;
        
        // Save result to transient
        set_transient( $transient_name, $cached_ids, $expiration );
    }

    if ( empty( $cached_ids ) ) {
        return new WP_Query(); // Return empty query if no IDs
    }

    // Return a lightweight query using the cached IDs
    return new WP_Query( array(
        'post__in' => $cached_ids,
        'orderby'  => 'post__in',
        'posts_per_page' => count( $cached_ids ) // Ensure all are returned
    ) );
}

/**
 * Latest post in a top-level category, shown in that section's mega-menu
 * dropdown. Relies on WordPress' default behavior of including descendant
 * categories in a `cat` query, so a post tagged only with a subcategory
 * (e.g. "Asia") still surfaces under its parent ("World").
 *
 * @param int $term_id Category term ID.
 * @return WP_Query
 */
function sbek_get_megamenu_latest_post( int $term_id ): WP_Query {
    return sbek_get_cached_query(
        array(
            'posts_per_page' => 1,
            'cat'            => $term_id,
        ),
        "sbek_megamenu_latest_{$term_id}_" . sbek_lang(),
        900
    );
}

/**
 * Trending posts for the homepage/sidebar rail.
 *
 * Prefers editor-scored posts (`_sbek_trending_score`), then tops the list up
 * with the most recent posts so the rail always looks full even before any
 * scores are set. Both underlying queries are cached per language.
 *
 * @param int $limit How many posts to return.
 * @return WP_Query
 */
function sbek_get_trending_posts( int $limit = 6 ): WP_Query {
	$lang = sbek_lang();

	$scored = sbek_get_cached_query( array(
		'posts_per_page' => $limit,
		'meta_key'       => '_sbek_trending_score',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
	), "sbek_trending_home_{$lang}", 3600 );

	$ids = wp_list_pluck( $scored->posts, 'ID' );

	if ( count( $ids ) < $limit ) {
		$fill = sbek_get_cached_query( array(
			'posts_per_page' => $limit,
		), "sbek_trending_fill_{$lang}", 900 );

		foreach ( $fill->posts as $post ) {
			if ( ! in_array( $post->ID, $ids, true ) ) {
				$ids[] = $post->ID;
			}
			if ( count( $ids ) >= $limit ) {
				break;
			}
		}
	}

	if ( empty( $ids ) ) {
		return new WP_Query();
	}

	return new WP_Query( array(
		'post__in'       => $ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $ids ),
		'ignore_sticky_posts' => true,
	) );
}

/**
 * Related posts for the single template.
 *
 * Same-category posts first, topped up with the latest posts so the rail
 * never renders with one lonely card. The per-post category query uses the
 * `sbek_related_{id}_{lang}` transient flushed in
 * sbek_clear_transients_on_save(); the top-up query is shared per language.
 *
 * @param int $post_id Current post ID (excluded from results).
 * @param int $limit   How many posts to return.
 * @return WP_Query
 */
function sbek_get_related_posts( int $post_id, int $limit = 3 ): WP_Query {
	$lang = sbek_lang();
	$cats = get_the_category( $post_id );
	$ids  = [];

	if ( ! empty( $cats ) ) {
		$by_cat = sbek_get_cached_query( array(
			'posts_per_page'      => $limit,
			'cat'                 => $cats[0]->term_id,
			'post__not_in'        => array( $post_id ),
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		), "sbek_related_{$post_id}_{$lang}", 3600 );

		$ids = wp_list_pluck( $by_cat->posts, 'ID' );
	}

	if ( count( $ids ) < $limit ) {
		// The fill cache is shared per language, so the current post can't be
		// excluded in the query args (that would poison the shared transient
		// with one post's exclusion) — over-fetch and filter it out here.
		$fill = sbek_get_cached_query( array(
			'posts_per_page'      => $limit + 1,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		), "sbek_related_fill_{$lang}", 900 );

		foreach ( $fill->posts as $fill_post ) {
			if ( $fill_post->ID === $post_id || in_array( $fill_post->ID, $ids, true ) ) {
				continue;
			}
			$ids[] = $fill_post->ID;
			if ( count( $ids ) >= $limit ) {
				break;
			}
		}
	}

	if ( empty( $ids ) ) {
		return new WP_Query();
	}

	return new WP_Query( array(
		'post__in'            => $ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => count( $ids ),
		'ignore_sticky_posts' => true,
	) );
}

/**
 * Top-level categories for the footer column, cached as term IDs.
 *
 * Categories are shared across languages (names localized at render via
 * sbek_term_name()), so the cache is a single global transient, flushed on
 * save_post (post counts change hide_empty) and on category edits — see
 * sbek_clear_transients_on_save() and sbek_clear_footer_category_cache().
 *
 * @param int $limit How many categories to return.
 * @return WP_Term[]
 */
function sbek_footer_categories( int $limit = 6 ): array {
	$ids = get_transient( 'sbek_footer_cats' );

	if ( false === $ids ) {
		$ids = get_terms( array(
			'taxonomy'   => 'category',
			'parent'     => 0,
			'number'     => $limit,
			'hide_empty' => true,
			'fields'     => 'ids',
		) );

		if ( is_wp_error( $ids ) ) {
			$ids = array();
		}

		set_transient( 'sbek_footer_cats', $ids, 900 );
	}

	if ( empty( $ids ) ) {
		return array();
	}

	return array_filter( array_map( 'get_term', $ids ) );
}

/**
 * Flush the footer category cache when categories themselves change (the
 * post-driven flush in sbek_clear_transients_on_save() covers hide_empty
 * shifts from publishing/unpublishing).
 */
function sbek_clear_footer_category_cache(): void {
	delete_transient( 'sbek_footer_cats' );
}
add_action( 'created_category', 'sbek_clear_footer_category_cache' );
add_action( 'edited_category', 'sbek_clear_footer_category_cache' );
add_action( 'delete_category', 'sbek_clear_footer_category_cache' );

/**
 * Clear transients on save to ensure fresh content.
 * Clears per-language cache variants.
 */
function sbek_clear_transients_on_save( $post_id = 0 ): void {
    $languages = defined( 'SBEK_LANGUAGES' ) ? SBEK_LANGUAGES : [ 'en', 'jp' ];

    // Small, fixed set of top-level categories — cheap to clear all of them
    // on every save rather than working out which one(s) this post belongs to.
    $section_ids = get_terms( array(
        'taxonomy'   => 'category',
        'parent'     => 0,
        'fields'     => 'ids',
        'hide_empty' => false,
    ) );

    foreach ( $languages as $lang ) {
        delete_transient( "sbek_breaking_news_{$lang}" );
        delete_transient( "sbek_breaking_news_fallback_{$lang}" );
        delete_transient( "sbek_trending_sidebar_{$lang}" );
        delete_transient( "sbek_trending_home_{$lang}" );
        delete_transient( "sbek_trending_fill_{$lang}" );
        delete_transient( "sbek_404_popular_{$lang}" );
        delete_transient( "sbek_related_fill_{$lang}" );
        delete_transient( 'sbek_footer_cats' );

        if ( $post_id ) {
            // Related-posts cache is keyed per post; only this post's own key is known here —
            // other posts sharing its category will pick up changes once their 1-hour TTL expires.
            delete_transient( "sbek_related_{$post_id}_{$lang}" );
        }

        if ( ! is_wp_error( $section_ids ) ) {
            foreach ( $section_ids as $section_id ) {
                delete_transient( "sbek_megamenu_latest_{$section_id}_{$lang}" );
            }
        }
    }
}
add_action( 'save_post', 'sbek_clear_transients_on_save' );
