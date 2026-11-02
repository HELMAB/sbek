<?php
/**
 * Cached content queries for the homepage blocks, sidebars and related posts.
 *
 * Pattern: the expensive WP_Query runs with `fields => ids` and only the ID
 * list is cached (transient, per language). Keys embed a site-wide cache
 * version that is bumped whenever content changes, so there's no list of
 * transient names to keep in sync — stale entries simply expire.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/** Maximum number of category blocks on the homepage. */
const SBEK_HOME_BLOCK_LIMIT = 6;

// ─── Cache versioning ──────────────────────────────────────────────────────────

function sbek_cache_version(): int {
	return (int) get_option( 'sbek_cache_version', 1 );
}

function sbek_bump_cache_version(): void {
	static $bumped = false; // Once per request is enough.

	if ( ! $bumped ) {
		$bumped = true;
		update_option( 'sbek_cache_version', sbek_cache_version() + 1, true );
	}
}

/**
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function sbek_bump_cache_on_save( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return;
	}

	sbek_bump_cache_version();
}
add_action( 'save_post', 'sbek_bump_cache_on_save', 99, 2 );

/**
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 */
function sbek_bump_cache_on_status( string $new_status, string $old_status, WP_Post $post ): void {
	if ( $new_status !== $old_status && 'post' === $post->post_type ) {
		sbek_bump_cache_version();
	}
}
add_action( 'transition_post_status', 'sbek_bump_cache_on_status', 10, 3 );

foreach ( array( 'deleted_post', 'update_option_sticky_posts', 'edited_category', 'delete_category', 'edited_post_tag', 'delete_post_tag' ) as $sbek_hook ) {
	add_action( $sbek_hook, 'sbek_bump_cache_version' );
}
unset( $sbek_hook );

// ─── Core helpers ──────────────────────────────────────────────────────────────

/**
 * Run (or read from cache) an ID-only post query in the current language.
 *
 * @param string              $name Unique cache name for this query shape.
 * @param array<string,mixed> $args WP_Query args.
 * @param int                 $ttl  Cache lifetime in seconds.
 * @return int[]
 */
function sbek_cached_post_ids( string $name, array $args, int $ttl = 15 * MINUTE_IN_SECONDS ): array {
	$key = sprintf( 'sbek_q_%s_%s_v%d', $name, sbek_lang(), sbek_cache_version() );
	$ids = get_transient( $key );

	if ( ! is_array( $ids ) ) {
		$query = new WP_Query(
			array_merge(
				array(
					'post_type'              => 'post',
					'post_status'            => 'publish',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				),
				$args,
				array( 'fields' => 'ids' )
			)
		);

		$ids = array_map( 'intval', $query->posts );
		set_transient( $key, $ids, $ttl );
	}

	return $ids;
}

/**
 * Load full posts for an ID list (keeping order) and prime the meta, term and
 * featured-image caches in bulk so card templates don't query per post.
 *
 * @param int[] $ids Post IDs.
 * @return WP_Post[]
 */
function sbek_posts_by_ids( array $ids ): array {
	if ( empty( $ids ) ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post__in'            => $ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => count( $ids ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'sbek_all_languages'  => true, // IDs are already language-scoped.
		)
	);

	update_post_thumbnail_cache( $query );

	return $query->posts;
}

/**
 * Top an ID list up to $limit with the latest posts (minus exclusions), so a
 * block never renders half-empty while editors haven't curated it yet.
 *
 * @param int[] $ids     Curated IDs.
 * @param int   $limit   Target count.
 * @param int[] $exclude IDs that must not be added.
 * @return int[]
 */
function sbek_fill_with_latest( array $ids, int $limit, array $exclude = array() ): array {
	if ( count( $ids ) >= $limit ) {
		return array_slice( $ids, 0, $limit );
	}

	$latest = sbek_cached_post_ids( 'latest_' . ( $limit + count( $exclude ) ), array( 'posts_per_page' => $limit + count( $exclude ) ) );

	foreach ( $latest as $id ) {
		if ( count( $ids ) >= $limit ) {
			break;
		}
		if ( ! in_array( $id, $ids, true ) && ! in_array( $id, $exclude, true ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

// ─── Blocks ────────────────────────────────────────────────────────────────────

/**
 * Homepage hero: sticky posts first (newest first), then the latest.
 *
 * @return WP_Post[]
 */
function sbek_get_featured_posts( int $limit = 4 ): array {
	$sticky = array_map( 'intval', (array) get_option( 'sticky_posts', array() ) );
	$ids    = array();

	if ( $sticky ) {
		$ids = sbek_cached_post_ids(
			'featured_' . $limit,
			array(
				'post__in'       => $sticky,
				'posts_per_page' => $limit,
			)
		);
	}

	return sbek_posts_by_ids( sbek_fill_with_latest( $ids, $limit ) );
}

/**
 * Posts using the "Video" post format.
 *
 * @return WP_Post[]
 */
function sbek_get_video_posts( int $limit = 11 ): array {
	return sbek_posts_by_ids(
		sbek_cached_post_ids(
			'video_' . $limit,
			array(
				'posts_per_page' => $limit,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'post_format',
						'field'    => 'slug',
						'terms'    => array( 'post-format-video' ),
					),
				),
			)
		)
	);
}

/**
 * Latest posts in one category (descendants included).
 *
 * @return WP_Post[]
 */
function sbek_get_category_posts( int $term_id, int $limit ): array {
	return sbek_posts_by_ids(
		sbek_cached_post_ids(
			"cat_{$term_id}_{$limit}",
			array(
				'cat'            => $term_id,
				'posts_per_page' => $limit,
			)
		)
	);
}

/**
 * Editor-ranked posts (`_sbek_trending_score`), topped up with the latest.
 *
 * @return WP_Post[]
 */
function sbek_get_popular_posts( int $limit = 6 ): array {
	$days = absint( sbek_site_setting( 'popular_days', 7 ) );
	$args = array();

	// Only recent stories compete, so a high score doesn't pin an old story forever.
	if ( $days ) {
		$args['date_query'] = array(
			array(
				'after'     => $days . ' days ago',
				'inclusive' => true,
			),
		);
	}

	$ids = sbek_cached_post_ids(
		"popular_{$limit}_{$days}",
		$args + array(
			'posts_per_page' => $limit,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'score' => array(
					'key'     => '_sbek_trending_score',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'        => array(
				'score' => 'DESC',
				'date'  => 'DESC',
			),
		),
		HOUR_IN_SECONDS
	);

	return sbek_posts_by_ids( sbek_fill_with_latest( $ids, $limit ) );
}

/**
 * @param int   $limit   How many.
 * @param int[] $exclude IDs to skip.
 * @return WP_Post[]
 */
function sbek_get_latest_posts( int $limit = 6, array $exclude = array() ): array {
	return sbek_posts_by_ids( sbek_fill_with_latest( array(), $limit, $exclude ) );
}

/**
 * Same-category posts, topped up with the latest so the block is never empty.
 *
 * @return WP_Post[]
 */
function sbek_get_related_posts( int $post_id, int $limit = 12 ): array {
	$category = sbek_primary_category( $post_id );
	$ids      = array();

	if ( $category ) {
		$ids = sbek_cached_post_ids(
			"related_{$post_id}_{$limit}",
			array(
				'cat'            => $category->term_id,
				'post__not_in'   => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
				'posts_per_page' => $limit,
			),
			HOUR_IN_SECONDS
		);
	}

	return sbek_posts_by_ids( sbek_fill_with_latest( $ids, $limit, array( $post_id ) ) );
}

/**
 * Posts flagged "Breaking" for the header ticker (none → no ticker). A flag
 * expires `breaking_hours` (Sbek Theme → Homepage) after publication, so
 * stale stories leave the ticker on their own. The 5-minute cache keeps that
 * expiry close to on time.
 *
 * @return WP_Post[]
 */
function sbek_get_breaking_posts( int $limit = 5 ): array {
	$hours = absint( sbek_site_setting( 'breaking_hours', 48 ) );
	$args  = array(
		'posts_per_page' => $limit,
		'meta_key'       => '_sbek_is_breaking', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	);

	if ( $hours ) {
		$args['date_query'] = array(
			array(
				'after'     => $hours . ' hours ago',
				'inclusive' => true,
			),
		);
	}

	return sbek_posts_by_ids( sbek_cached_post_ids( "breaking_{$limit}_{$hours}", $args, 5 * MINUTE_IN_SECONDS ) );
}

/**
 * Categories shown as homepage blocks: those with a positive "Homepage
 * position" (term meta `sbek_home_order`), in that order. If none are set,
 * the first non-empty top-level categories except the default one. Empty
 * categories are skipped either way; at most $limit are returned.
 *
 * @return WP_Term[]
 */
function sbek_get_home_categories( int $limit = SBEK_HOME_BLOCK_LIMIT ): array {
	$ordered = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'number'     => $limit,
			'meta_key'   => 'sbek_home_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'sbek_home_order',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	if ( ! is_wp_error( $ordered ) && ! empty( $ordered ) ) {
		return $ordered;
	}

	$fallback = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => $limit,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);

	return is_wp_error( $fallback ) ? array() : $fallback;
}
