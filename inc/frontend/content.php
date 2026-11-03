<?php
/**
 * Article body tweaks.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Place the featured image after the article's first paragraph (the news
 * layout this theme follows), unless the post leads with a video.
 *
 * @param string $content Rendered post content.
 */
function sbek_inject_cover_image( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();

	// A plugin that puts a video above the text (`sbek_has_lead_video`) gets
	// no second picture inside it.
	if ( ! $post || ! has_post_thumbnail( $post ) || apply_filters( 'sbek_has_lead_video', false, $post ) ) {
		return $content;
	}

	$thumbnail_id = (int) get_post_thumbnail_id( $post );
	$alt          = trim( (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) );
	$caption      = wp_get_attachment_caption( $thumbnail_id );

	$figure = '<figure class="article__cover">'
		. get_the_post_thumbnail(
			$post,
			'large',
			array(
				'alt'           => '' !== $alt ? $alt : get_the_title( $post ),
				'sizes'         => '(max-width: 767px) 100vw, 730px',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		)
		. ( $caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : '' )
		. '</figure>';

	$end_of_first_p = stripos( $content, '</p>' );

	if ( false === $end_of_first_p ) {
		return $figure . $content;
	}

	return substr_replace( $content, '</p>' . $figure, $end_of_first_p, 4 );
}
add_filter( 'the_content', 'sbek_inject_cover_image', 20 );
