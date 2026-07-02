<?php
/**
 * Single-article enhancements: heading anchors + table-of-contents data.
 *
 * A `the_content` filter injects `id` attributes on the `<h2>`s of the main
 * single-post content, and sbek_article_headings() derives the same ids from
 * the raw post_content (block markup keeps headings literal), so the sidebar
 * TOC rendered by single.php always matches the anchors in the article body.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the anchor id for one h2 — shared by the content filter and the TOC
 * parser so both always agree. An author-set id (e.g. a Gutenberg heading
 * anchor) wins verbatim; otherwise the id is slugified from the heading text,
 * de-duplicated with a numeric suffix.
 *
 * @param string $attrs Raw attribute string of the <h2> tag.
 * @param string $inner Inner HTML of the heading.
 * @param int    $index 1-based position of the heading in the content.
 * @param array  $used  Ids already taken (passed by reference).
 * @return array{id:string,existing:bool}
 */
function sbek_article_heading_id( string $attrs, string $inner, int $index, array &$used ): array {
	if ( preg_match( '/\sid=("|\')([^"\']+)\1/i', $attrs, $m ) ) {
		$used[ $m[2] ] = true;

		return [
			'id'       => $m[2],
			'existing' => true,
		];
	}

	$text = trim( wp_strip_all_tags( $inner ) );
	$id   = $text ? sanitize_title( $text ) : '';

	if ( '' === $id ) {
		$id = 'section-' . $index;
	}

	$base = $id;
	$n    = 2;
	while ( isset( $used[ $id ] ) ) {
		$id = "{$base}-{$n}";
		$n++;
	}
	$used[ $id ] = true;

	return [
		'id'       => $id,
		'existing' => false,
	];
}

/**
 * Add anchor ids to <h2> headings in the main single-post content so the
 * sidebar TOC (and any shared deep link) has something to target.
 */
function sbek_article_heading_ids( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$used  = [];
	$index = 0;

	return (string) preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( array $m ) use ( &$used, &$index ) {
			$index++;
			$heading = sbek_article_heading_id( $m[1], $m[2], $index, $used );

			if ( $heading['existing'] ) {
				return $m[0];
			}

			return '<h2' . $m[1] . ' id="' . esc_attr( $heading['id'] ) . '">' . $m[2] . '</h2>';
		},
		$content
	);
}
add_filter( 'the_content', 'sbek_article_heading_ids', 12 );

/**
 * Parse the post's <h2> headings for the sidebar table of contents.
 *
 * Reads raw post_content — Gutenberg keeps heading markup literal, so the ids
 * computed here match what sbek_article_heading_ids() injects on render.
 *
 * @param WP_Post|int|null $post Defaults to the current post.
 * @return array<int,array{id:string,title:string}>
 */
function sbek_article_headings( $post = null ): array {
	$post = get_post( $post );

	if ( ! $post || ! preg_match_all( '/<h2([^>]*)>(.*?)<\/h2>/is', $post->post_content, $matches, PREG_SET_ORDER ) ) {
		return [];
	}

	$used     = [];
	$headings = [];

	foreach ( $matches as $index => $m ) {
		$heading = sbek_article_heading_id( $m[1], $m[2], $index + 1, $used );
		$title   = trim( wp_strip_all_tags( $m[2] ) );

		if ( '' === $title ) {
			continue; // The anchor still exists, but an empty TOC row is useless.
		}

		$headings[] = [
			'id'    => $heading['id'],
			'title' => $title,
		];
	}

	return $headings;
}
