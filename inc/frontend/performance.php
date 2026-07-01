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
 * Clear transients on save to ensure fresh content.
 * Clears per-language cache variants.
 */
function sbek_clear_transients_on_save( $post_id = 0 ): void {
    $languages = defined( 'SBEK_LANGUAGES' ) ? SBEK_LANGUAGES : [ 'en', 'jp' ];

    foreach ( $languages as $lang ) {
        delete_transient( "sbek_breaking_news_{$lang}" );
        delete_transient( "sbek_breaking_news_fallback_{$lang}" );
        delete_transient( "sbek_trending_sidebar_{$lang}" );
        delete_transient( "sbek_404_popular_{$lang}" );

        if ( $post_id ) {
            // Related-posts cache is keyed per post; only this post's own key is known here —
            // other posts sharing its category will pick up changes once their 1-hour TTL expires.
            delete_transient( "sbek_related_{$post_id}_{$lang}" );
        }
    }
}
add_action( 'save_post', 'sbek_clear_transients_on_save' );
