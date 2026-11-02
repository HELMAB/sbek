<?php
/**
 * "Related articles": 6 cards per page; the arrow cycles through pages that
 * are shipped as inert <template>s.
 *
 * Args:
 *   posts  WP_Post[]
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_posts = (array) ( $args['posts'] ?? array() );
if ( ! $sbek_posts ) {
	return;
}

$sbek_pages = array_chunk( $sbek_posts, 6 );

$sbek_render_page = static function ( array $items ): void {
	echo '<div class="card-grid card-grid--related">';
	foreach ( $items as $item ) {
		get_template_part(
			'template-parts/card/thumb',
			null,
			array(
				'post'        => $item,
				'sizes'       => '(max-width: 991px) 50vw, 230px',
				'title_class' => 'news-thumb__title--related',
			)
		);
	}
	echo '</div>';
};
?>
<section class="related" aria-labelledby="sbek-related-title">
	<h2 id="sbek-related-title" class="rule-title"><?php esc_html_e( 'Related articles', 'sbek' ); ?></h2>
	<div class="pager" data-sbek-pager>
		<div class="pager__view" data-pager-view>
			<?php $sbek_render_page( $sbek_pages[0] ); ?>
		</div>
		<?php foreach ( array_slice( $sbek_pages, 1 ) as $sbek_page ) : ?>
			<template data-pager-page><?php $sbek_render_page( $sbek_page ); ?></template>
		<?php endforeach; ?>
		<?php if ( count( $sbek_pages ) > 1 ) : ?>
			<button type="button" class="pager__next pager__next--related" data-pager-next>
				<?php echo sbek_icon( 'chevron-right', array( 'width' => 30, 'height' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="screen-reader-text"><?php esc_html_e( 'More related articles', 'sbek' ); ?></span>
			</button>
		<?php endif; ?>
	</div>
</section>
