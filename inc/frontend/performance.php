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
