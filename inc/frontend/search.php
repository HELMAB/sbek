<?php
/**
 * Search enhancements for Sbek
 * Adds an optional category facet to the front-end search results query.
 */

function sbek_filter_search_by_category( $query ) {
    if ( is_admin() || ! $query->is_search() || ! $query->is_main_query() ) {
        return;
    }

    if ( empty( $_GET['cat'] ) ) {
        return;
    }

    $cat_id = absint( $_GET['cat'] );
    $term   = $cat_id ? get_term( $cat_id, 'category' ) : null;

    if ( $term && ! is_wp_error( $term ) ) {
        $query->set( 'cat', $cat_id );
    }
}
add_action( 'pre_get_posts', 'sbek_filter_search_by_category' );
